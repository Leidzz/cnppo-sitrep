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

function archiveSitrep(User $creator, array $overrides = []): Sitrep
{
    static $seq = 600;

    return Sitrep::create(array_merge([
        'reference_number' => 'SITREP-ARC-'.$seq++,
        'unit_id' => $creator->unit_id,
        'report_type_id' => ReportType::where('name', 'Checkpoint')->first()->id,
        'current_status_id' => Status::where('name', 'Submitted')->first()->id,
        'created_by' => $creator->id,
        'dynamic_fields' => ['what' => 'Routine checkpoint'],
        'submitted_at' => now(),
    ], $overrides));
}

function archiveReviewer(): User
{
    return User::factory()->create()->assignRole('command-center');
}

it('forbids reporting personnel from the archive', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');

    $this->actingAs($officer)->get(route('command.reports.index'))->assertForbidden();
});

it('searches by reference number and by content', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');
    $a = archiveSitrep($officer, ['reference_number' => 'SITREP-UNIQUE-XYZ', 'dynamic_fields' => ['what' => 'apple']]);
    $b = archiveSitrep($officer, ['dynamic_fields' => ['what' => 'banana']]);

    $this->actingAs(archiveReviewer())
        ->get(route('command.reports.index', ['q' => 'UNIQUE-XYZ']))
        ->assertOk()
        ->assertSee($a->reference_number)
        ->assertDontSee($b->reference_number);

    $this->actingAs(archiveReviewer())
        ->get(route('command.reports.index', ['q' => 'banana']))
        ->assertOk()
        ->assertSee($b->reference_number)
        ->assertDontSee($a->reference_number);
});

it('filters by date range and never shows drafts', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');

    $old = archiveSitrep($officer, ['submitted_at' => now()->subDays(10)]);
    $recent = archiveSitrep($officer, ['submitted_at' => now()->subDay()]);
    $draft = archiveSitrep($officer, ['current_status_id' => Status::where('name', 'Draft')->first()->id, 'submitted_at' => null]);

    $this->actingAs(archiveReviewer())
        ->get(route('command.reports.index', ['date_from' => now()->subDays(3)->toDateString()]))
        ->assertOk()
        ->assertSee($recent->reference_number)
        ->assertDontSee($old->reference_number)
        ->assertDontSee($draft->reference_number);
});

it('rejects an invalid sort direction', function () {
    $this->actingAs(archiveReviewer())
        ->get(route('command.reports.index', ['sort' => '1;DROP TABLE sitreps;--']))
        ->assertNotFound();
});
