<?php

use App\Models\AuditLog;
use App\Models\ReportType;
use App\Models\Sitrep;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\ReportTypeSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, StatusSeeder::class, ReportTypeSeeder::class, UnitSeeder::class]);
});

function makeReporter(): User
{
    return User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');
}

it('shows report types and their dynamic fields on the create page', function () {
    $this->actingAs(makeReporter())
        ->get(route('reporting.sitreps.create'))
        ->assertOk()
        ->assertSee('Police Visibility')
        ->assertSee('Checkpoint')
        ->assertSee('Location')            // Police Visibility field label
        ->assertSee('Activity Conducted')
        ->assertSee('Remarks');            // Checkpoint optional field
});

it('saves a draft with a reference number, Draft status, and one audit entry', function () {
    $user = makeReporter();

    $checkpoint = ReportType::where('name', 'Checkpoint')->first();

    $this->actingAs($user)->post(route('reporting.sitreps.store'), [
        'report_type_id' => $checkpoint->id,
        'action' => 'draft',
        'dynamic_fields' => ['what' => 'Checkpoint operation conducted'],
    ])->assertRedirect(route('reporting.dashboard'));

    $sitrep = Sitrep::sole();
    expect($sitrep->reference_number)->toStartWith('SITREP-'.now()->format('Y').'-');
    expect($sitrep->currentStatus->name)->toBe('Draft');
    expect($sitrep->unit_id)->toBe($user->unit_id);
    expect($sitrep->dynamic_fields['what'])->toBe('Checkpoint operation conducted');
    expect($sitrep->submitted_at)->toBeNull();

    expect(AuditLog::count())->toBe(1);
    expect(AuditLog::sole()->action)->toBe('created');
    expect(AuditLog::sole()->new_status)->toBe('Draft');
});

it('allows an incomplete draft (required fields are enforced on submit, not draft)', function () {
    $checkpoint = ReportType::where('name', 'Checkpoint')->first();

    $this->actingAs(makeReporter())->post(route('reporting.sitreps.store'), [
        'report_type_id' => $checkpoint->id,
        'action' => 'draft',
        'dynamic_fields' => [],   // nothing filled — legal for a draft
    ])->assertSessionDoesntHaveErrors();
});

it('rejects a submission missing required fields', function () {
    $checkpoint = ReportType::where('name', 'Checkpoint')->first();

    $this->actingAs(makeReporter())->post(route('reporting.sitreps.store'), [
        'report_type_id' => $checkpoint->id,
        'action' => 'submit',
        'dynamic_fields' => ['what' => 'Only one field filled'],
    ])->assertSessionHasErrors(['dynamic_fields.where', 'dynamic_fields.when', 'dynamic_fields.how']);
});

it('submits: status Submitted, timestamp set, two audit entries', function () {
    $user = makeReporter();
    $checkpoint = ReportType::where('name', 'Checkpoint')->first();

    $this->actingAs($user)->post(route('reporting.sitreps.store'), [
        'report_type_id' => $checkpoint->id,
        'action' => 'submit',
        'dynamic_fields' => [
            'what' => 'Checkpoint', 'where' => 'Brgy. Poblacion',
            'when' => 'Oct 2, 8PM-10PM', 'how' => 'Standard checkpoint ops',
        ],
    ])->assertRedirect(route('reporting.dashboard'));

    $sitrep = Sitrep::sole();
    expect($sitrep->currentStatus->name)->toBe('Submitted');
    expect($sitrep->submitted_at)->not->toBeNull();

    expect(AuditLog::count())->toBe(2);
    expect(AuditLog::orderBy('id')->pluck('new_status')->all())->toBe(['Draft', 'Submitted']);
});

it('forbids command-center users from storing a sitrep', function () {
    $user = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('command-center');
    $checkpoint = ReportType::where('name', 'Checkpoint')->first();

    $this->actingAs($user)->post(route('reporting.sitreps.store'), [
        'report_type_id' => $checkpoint->id,
        'action' => 'draft',
        'dynamic_fields' => [],
    ])->assertForbidden();
});

it('stores GPS location fields on submit', function () {
    $user = makeReporter();
    $checkpoint = ReportType::where('name', 'Checkpoint')->first();

    $this->actingAs($user)->post(route('reporting.sitreps.store'), [
        'report_type_id' => $checkpoint->id,
        'action' => 'submit',
        'dynamic_fields' => [
            'what' => 'Checkpoint', 'where' => 'Brgy. Poblacion',
            'when' => 'Oct 2, 8PM-10PM', 'how' => 'Standard checkpoint ops',
        ],
        'latitude' => 13.6212847,
        'longitude' => 122.9561234,
        'gps_accuracy_meters' => 4.5,
        'location_captured_at' => '2026-10-02T01:30:00.000000Z',
        'location_description' => 'Brgy. Poblacion, national highway',
    ])->assertRedirect(route('reporting.dashboard'));

    $sitrep = Sitrep::sole();
    expect($sitrep->latitude)->toBe(13.6212847);
    expect($sitrep->longitude)->toBe(122.9561234);
    expect($sitrep->gps_accuracy_meters)->toBe(4.5);
    expect($sitrep->location_description)->toBe('Brgy. Poblacion, national highway');
    expect($sitrep->location_captured_at)->not->toBeNull();
});

it('rejects out-of-range GPS coordinates', function () {
    $checkpoint = ReportType::where('name', 'Checkpoint')->first();

    $this->actingAs(makeReporter())->post(route('reporting.sitreps.store'), [
        'report_type_id' => $checkpoint->id,
        'action' => 'draft',
        'dynamic_fields' => [],
        'latitude' => 91.5,       // impossible — max is 90
        'longitude' => 200,       // impossible — max is 180
    ])->assertSessionHasErrors(['latitude', 'longitude']);
});
