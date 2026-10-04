<?php

use App\Models\AuditLog;
use App\Models\ReportType;
use App\Models\Sitrep;
use App\Models\Status;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\ReportTypeSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, StatusSeeder::class, ReportTypeSeeder::class, UnitSeeder::class]);
});

function makeEditableSitrep(string $statusName = 'Draft'): array
{
    $user = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    static $seq = 100;   // different range from the other test file's helper

    $checkpoint = ReportType::where('name', 'Checkpoint')->first();
    $status = Status::where('name', $statusName)->first();

    $sitrep = Sitrep::create([
        'reference_number' => 'SITREP-EDIT-'.$seq++,
        'unit_id' => $user->unit_id,
        'report_type_id' => $checkpoint->id,
        'current_status_id' => $status->id,
        'created_by' => $user->id,
        'dynamic_fields' => ['what' => 'Original what', 'where' => 'Original where'],
    ]);

    return [$user, $sitrep];
}

function threePhotosEdit(): array
{
    return [
        UploadedFile::fake()->image('a.jpg', 100, 100)->size(50),
        UploadedFile::fake()->image('b.jpg', 100, 100)->size(50),
        UploadedFile::fake()->image('c.jpg', 100, 100)->size(50),
    ];
}

it('shows the edit form to the owner of a draft', function () {
    [$owner, $sitrep] = makeEditableSitrep();

    $this->actingAs($owner)
        ->get(route('reporting.sitreps.edit', $sitrep))
        ->assertOk()
        ->assertSee('Original what');   // prefilled value visible
});

it('forbids editing a submitted sitrep', function () {
    [$owner, $sitrep] = makeEditableSitrep('Submitted');

    $this->actingAs($owner)
        ->get(route('reporting.sitreps.edit', $sitrep))
        ->assertForbidden();
});

it('forbids another officer from editing a draft', function () {
    [, $sitrep] = makeEditableSitrep();
    $intruder = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    $this->actingAs($intruder)
        ->get(route('reporting.sitreps.edit', $sitrep))
        ->assertForbidden();
});

it('updates a drafts content and logs the edit', function () {
    [$owner, $sitrep] = makeEditableSitrep();

    $this->actingAs($owner)->put(route('reporting.sitreps.update', $sitrep), [
        'report_type_id' => $sitrep->report_type_id,
        'action' => 'draft',
        'dynamic_fields' => [
            'what' => 'Updated what', 'where' => 'Original where',
            'when_date' => '2026-09-27', 'when_time' => '22:00', 'how' => 'Updated',
        ],
    ])->assertRedirect(route('reporting.sitreps.show', $sitrep));

    $sitrep->refresh();
    expect($sitrep->dynamic_fields['what'])->toBe('Updated what');
    expect($sitrep->currentStatus->name)->toBe('Draft');

    expect(AuditLog::where('action', 'updated')->get())->toHaveCount(1);
});

it('submits a finished draft through the update path', function () {
    Storage::fake('sitrep_photos');

    [$owner, $sitrep] = makeEditableSitrep();

    $this->actingAs($owner)->put(route('reporting.sitreps.update', $sitrep), [
        'report_type_id' => $sitrep->report_type_id,
        'action' => 'submit',
        'dynamic_fields' => [
            'what' => 'Done', 'where' => 'There',
            'when_date' => '2026-09-27', 'when_time' => '22:00', 'how' => 'Properly',
        ],
        'photos' => threePhotosEdit(),
    ])->assertRedirect(route('reporting.sitreps.show', $sitrep));

    $sitrep->refresh();
    expect($sitrep->currentStatus->name)->toBe('Submitted');
    expect($sitrep->submitted_at)->not->toBeNull();

    // audit: created? no — this sitrep was inserted directly. Only 'updated' + 'status_changed'
    expect(AuditLog::pluck('action')->all())->toBe(['updated', 'status_changed']);
    expect(AuditLog::pluck('new_status')->all())->toBe(['Draft', 'Submitted']);
});

it('resubmits a returned report after correction', function () {
    Storage::fake('sitrep_photos');

    [$owner, $sitrep] = makeEditableSitrep('Returned for Correction');

    $this->actingAs($owner)->put(route('reporting.sitreps.update', $sitrep), [
        'report_type_id' => $sitrep->report_type_id,
        'action' => 'submit',
        'dynamic_fields' => [
            'what' => 'Corrected', 'where' => 'There',
            'when_date' => '2026-09-27', 'when_time' => '22:00', 'how' => 'Properly',
        ],
        'photos' => threePhotosEdit(),
    ])->assertRedirect(route('reporting.sitreps.show', $sitrep));

    $sitrep->refresh();
    expect($sitrep->currentStatus->name)->toBe('Submitted');
    expect(AuditLog::where('remarks', 'Corrected and resubmitted by reporting personnel.')->get())->toHaveCount(1);
});

it('attaches photos when updating a draft', function () {
    Storage::fake('sitrep_photos');
    [$owner, $sitrep] = makeEditableSitrep();

    $this->actingAs($owner)->put(route('reporting.sitreps.update', $sitrep), [
        'report_type_id' => $sitrep->report_type_id,
        'action' => 'draft',
        'dynamic_fields' => ['what' => 'With photo'],
        'photos' => [UploadedFile::fake()->image('draft-photo.jpg', 600, 400)->size(200)],
    ])->assertRedirect(route('reporting.sitreps.show', $sitrep));

    expect($sitrep->refresh()->attachments)->toHaveCount(1);
});
