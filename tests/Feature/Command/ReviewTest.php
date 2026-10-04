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

function makeSubmittedSitrep(): array
{
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    static $seq = 900;

    $sitrep = Sitrep::create([
        'reference_number' => 'SITREP-R-'.$seq++,
        'unit_id' => $officer->unit_id,
        'report_type_id' => ReportType::where('name', 'Checkpoint')->first()->id,
        'current_status_id' => Status::where('name', 'Submitted')->first()->id,
        'created_by' => $officer->id,
        'dynamic_fields' => ['what' => 'Something'],
        'submitted_at' => now(),
    ]);

    return [$officer, $sitrep];
}

function commandReviewer(): User
{
    return User::factory()->create()->assignRole('command-center');
}

it('shows the review form to command center on a submitted report', function () {
    [, $sitrep] = makeSubmittedSitrep();

    $this->actingAs(commandReviewer())
        ->get(route('command.sitreps.show', $sitrep))
        ->assertOk()
        ->assertSee('Review Decision')
        ->assertSee('Validate Report');
});

it('does not show the review form to the author', function () {
    [$officer, $sitrep] = makeSubmittedSitrep();

    $this->actingAs($officer)
        ->get(route('reporting.sitreps.show', $sitrep))
        ->assertOk()
        ->assertDontSee('Review Decision');
});

it('validates a submitted report end to end', function () {
    $reviewer = commandReviewer();
    [, $sitrep] = makeSubmittedSitrep();

    $this->actingAs($reviewer)->post(route('command.sitreps.decide', $sitrep), [
        'decision' => 'validated',
        'remarks' => null,
    ])->assertRedirect(route('command.incoming'));

    $sitrep->refresh();
    expect($sitrep->currentStatus->name)->toBe('Validated');
    expect($sitrep->reviewed_by)->toBe($reviewer->id);
    expect($sitrep->reviewed_at)->not->toBeNull();

    expect(AuditLog::pluck('new_status')->all())->toContain('Validated');
});

it('returns a report and stores the reviewers reason', function () {
    [, $sitrep] = makeSubmittedSitrep();

    $this->actingAs(commandReviewer())->post(route('command.sitreps.decide', $sitrep), [
        'decision' => 'returned',
        'remarks' => 'Incomplete narrative; include time of apprehension.',
    ])->assertRedirect(route('command.incoming'));

    $sitrep->refresh();
    expect($sitrep->currentStatus->name)->toBe('Returned for Correction');
    expect($sitrep->review_remarks)->toBe('Incomplete narrative; include time of apprehension.');
});

it('requires remarks when returning', function () {
    [, $sitrep] = makeSubmittedSitrep();

    $this->actingAs(commandReviewer())->post(route('command.sitreps.decide', $sitrep), [
        'decision' => 'returned',
    ])->assertSessionHasErrors(['remarks']);
});

it('forbids reporting personnel from deciding', function () {
    [$officer, $sitrep] = makeSubmittedSitrep();

    $this->actingAs($officer)->post(route('command.sitreps.decide', $sitrep), [
        'decision' => 'validated',
    ])->assertForbidden();
});

it('cannot re-review a validated report', function () {
    [, $sitrep] = makeSubmittedSitrep();
    $sitrep->update(['current_status_id' => Status::where('name', 'Validated')->first()->id]);

    $this->actingAs(commandReviewer())->post(route('command.sitreps.decide', $sitrep), [
        'decision' => 'returned',
        'remarks' => 'Too late',
    ])->assertForbidden();
});

it('completes the full loop: return, correct, resubmit, validate', function () {
    Storage::fake('sitrep_photos');

    [$officer, $sitrep] = makeSubmittedSitrep();
    $reviewer = commandReviewer();

    // 1. reviewer returns it (claim Under Review, then Returned)
    $this->actingAs($reviewer)->post(route('command.sitreps.decide', $sitrep), [
        'decision' => 'returned',
        'remarks' => 'Add the who details.',
    ]);
    expect($sitrep->refresh()->currentStatus->name)->toBe('Returned for Correction');

    // 2. officer corrects and resubmits (submit rules apply: pickers + 3 photos)
    $this->actingAs($officer)->put(route('reporting.sitreps.update', $sitrep), [
        'report_type_id' => $sitrep->report_type_id,
        'action' => 'submit',
        'dynamic_fields' => [
            'what' => 'Done', 'where' => 'There',
            'when_date' => '2026-09-27', 'when_time' => '22:00',
            'how' => 'Properly',
        ],
        'photos' => [
            UploadedFile::fake()->image('a.jpg', 100, 100)->size(50),
            UploadedFile::fake()->image('b.jpg', 100, 100)->size(50),
            UploadedFile::fake()->image('c.jpg', 100, 100)->size(50),
        ],
    ]);
    expect($sitrep->refresh()->currentStatus->name)->toBe('Submitted');

    // 3. reviewer validates the corrected report (claim Under Review, then Validated)
    $this->actingAs($reviewer)->post(route('command.sitreps.decide', $sitrep), [
        'decision' => 'validated',
    ]);
    expect($sitrep->refresh()->currentStatus->name)->toBe('Validated');

    // the audit trail tells the whole story — only status CHANGES
    expect(AuditLog::where('action', 'status_changed')->orderBy('id')->pluck('new_status')->all())
        ->toBe(['Under Review', 'Returned for Correction', 'Submitted', 'Under Review', 'Validated']);
});
