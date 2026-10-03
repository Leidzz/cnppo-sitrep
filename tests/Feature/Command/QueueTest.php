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

function makeSitrepFor(User $creator, string $statusName, ?int $unitId = null): Sitrep
{
    static $seq = 500;

    return Sitrep::create([
        'reference_number' => 'SITREP-Q-'.$seq++,
        'unit_id' => $unitId ?? $creator->unit_id,
        'report_type_id' => ReportType::where('name', 'Checkpoint')->first()->id,
        'current_status_id' => Status::where('name', $statusName)->first()->id,
        'created_by' => $creator->id,
        'dynamic_fields' => [],
        'submitted_at' => $statusName !== 'Draft' ? now() : null,
    ]);
}

function reviewer(): User
{
    return User::factory()->create()->assignRole('command-center');
}

it('forbids reporting personnel from the command section', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    $this->actingAs($officer)->get(route('command.dashboard'))->assertForbidden();
    $this->actingAs($officer)->get(route('command.incoming'))->assertForbidden();
});

it('shows province-wide counts to the reviewer', function () {
    $unitA = Unit::first();
    $unitB = Unit::skip(1)->first();
    $officerA = User::factory()->create(['unit_id' => $unitA->id])->assignRole('reporting-personnel');
    $officerB = User::factory()->create(['unit_id' => $unitB->id])->assignRole('reporting-personnel');

    $submitted = makeSitrepFor($officerA, 'Submitted');
    makeSitrepFor($officerB, 'Submitted');
    makeSitrepFor($officerB, 'Draft');           // drafts must NOT count
    makeSitrepFor($officerA, 'Validated');

    $this->actingAs(reviewer())
        ->get(route('command.dashboard'))
        ->assertOk()
        ->assertSee('2')   // two submitted
        ->assertSee($submitted->reference_number);
});

it('lists submitted reports from all units and never shows drafts', function () {
    $unitB = Unit::skip(1)->first();
    $officer = User::factory()->create(['unit_id' => $unitB->id])->assignRole('reporting-personnel');

    $sub = makeSitrepFor($officer, 'Submitted');
    $draft = makeSitrepFor($officer, 'Draft');

    $this->actingAs(reviewer())
        ->get(route('command.incoming'))
        ->assertOk()
        ->assertSee($sub->reference_number)
        ->assertDontSee($draft->reference_number);
});

it('filters the queue by unit', function () {
    $unitA = Unit::first();
    $unitB = Unit::skip(1)->first();
    $officerA = User::factory()->create(['unit_id' => $unitA->id])->assignRole('reporting-personnel');
    $officerB = User::factory()->create(['unit_id' => $unitB->id])->assignRole('reporting-personnel');

    $a = makeSitrepFor($officerA, 'Submitted');
    $b = makeSitrepFor($officerB, 'Submitted');

    $this->actingAs(reviewer())
        ->get(route('command.incoming', ['unit_id' => $unitB->id]))
        ->assertOk()
        ->assertSee($b->reference_number)
        ->assertDontSee($a->reference_number);
});

it('lets the reviewer open the detail and forbids officers from the command detail route', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');
    $sitrep = makeSitrepFor($officer, 'Submitted');

    $this->actingAs(reviewer())
        ->get(route('command.sitreps.show', $sitrep))
        ->assertOk()
        ->assertSee($sitrep->reference_number);

    $this->actingAs($officer)
        ->get(route('command.sitreps.show', $sitrep))
        ->assertForbidden();
});

it('defaults the queue to newly submitted only, with all as an explicit tab', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');

    $sub = makeSitrepFor($officer, 'Submitted');
    $val = makeSitrepFor($officer, 'Validated');

    // No status param = triage view: only Newly Submitted
    $this->actingAs(reviewer())
        ->get(route('command.incoming'))
        ->assertOk()
        ->assertSee($sub->reference_number)
        ->assertDontSee($val->reference_number);

    // Explicit all tab = everything in the pipeline
    $this->actingAs(reviewer())
        ->get(route('command.incoming', ['status' => 'all']))
        ->assertOk()
        ->assertSee($val->reference_number);
});
