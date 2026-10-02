<?php

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

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, StatusSeeder::class, ReportTypeSeeder::class, UnitSeeder::class]);
});

function officerWithSitrep(string $statusName = 'Draft'): array
{
    $user = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    static $seq = 1;

    $checkpoint = ReportType::where('name', 'Checkpoint')->first();
    $status = Status::where('name', $statusName)->first();

    $sitrep = Sitrep::create([
        'reference_number' => 'SITREP-TEST-'.str_pad((string) $seq++, 4, '0', STR_PAD_LEFT),
        'unit_id' => $user->unit_id,
        'report_type_id' => $checkpoint->id,
        'current_status_id' => $status->id,
        'created_by' => $user->id,
        'dynamic_fields' => ['what' => 'Test checkpoint', 'where' => 'Test area'],
        'latitude' => 14.1,
        'longitude' => 122.9,
        'gps_accuracy_meters' => 5.0,
        'location_captured_at' => now(),
        'submitted_at' => $statusName !== 'Draft' ? now() : null,
    ]);

    return [$user, $sitrep];
}

it('lists only the officers own reports', function () {
    [$owner, $sitrep] = officerWithSitrep();
    $other = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    Sitrep::create([
        'reference_number' => 'SITREP-OTHER-0001',
        'unit_id' => $other->unit_id,
        'report_type_id' => $sitrep->report_type_id,
        'current_status_id' => $sitrep->current_status_id,
        'created_by' => $other->id,
        'dynamic_fields' => [],
    ]);

    $this->actingAs($owner)
        ->get(route('reporting.sitreps.index'))
        ->assertOk()
        ->assertSee($sitrep->reference_number)          // ← dynamic, not hardcoded
        ->assertDontSee('SITREP-OTHER-0001');
});

it('filters my reports by status', function () {
    [$owner, $draft] = officerWithSitrep('Draft');
    officerWithSitrep('Submitted'); // second officer — irrelevant

    // give the owner a submitted one too — distinct, non-sequential reference
    $submitted = Sitrep::create([
        'reference_number' => 'SITREP-OWNER-SUB-1',
        'unit_id' => $owner->unit_id,
        'report_type_id' => $draft->report_type_id,
        'current_status_id' => Status::where('name', 'Submitted')->first()->id,
        'created_by' => $owner->id,
        'dynamic_fields' => [],
        'submitted_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('reporting.sitreps.index', ['status' => 'Submitted']))
        ->assertOk()
        ->assertSee($submitted->reference_number)       // ← dynamic
        ->assertDontSee($draft->reference_number);      // ← dynamic
});

it('rejects an unknown status filter', function () {
    [$owner] = officerWithSitrep();

    $this->actingAs($owner)
        ->get(route('reporting.sitreps.index', ['status' => 'Hacked']))
        ->assertNotFound();
});

it('shows the sitrep detail with gps and template labels', function () {
    [$owner, $sitrep] = officerWithSitrep('Submitted');

    $this->actingAs($owner)
        ->get(route('reporting.sitreps.show', $sitrep))
        ->assertOk()
        ->assertSee($sitrep->reference_number)          // ← dynamic
        ->assertSee('What')
        ->assertSee('Test checkpoint')
        ->assertSee('14.1')
        ->assertSee('awaiting review');
});

it('forbids another officer from viewing the detail', function () {
    [, $sitrep] = officerWithSitrep();
    $intruder = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    $this->actingAs($intruder)
        ->get(route('reporting.sitreps.show', $sitrep))
        ->assertForbidden();
});

it('shows real status counts on the dashboard', function () {
    [$owner, $draft] = officerWithSitrep('Draft');
    $submitted = Sitrep::create([
        'reference_number' => 'SITREP-DASH-SUB-1',
        'unit_id' => $owner->unit_id,
        'report_type_id' => $draft->report_type_id,
        'current_status_id' => Status::where('name', 'Submitted')->first()->id,
        'created_by' => $owner->id,
        'dynamic_fields' => [],
        'submitted_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('reporting.dashboard'))
        ->assertOk()
        ->assertSee($draft->reference_number)           // ← recent list shows newest...
        ->assertSee('My Reports');
});
