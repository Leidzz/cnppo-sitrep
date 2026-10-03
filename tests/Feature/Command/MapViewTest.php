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

function mapSitrep(User $creator, array $overrides = []): Sitrep
{
    static $seq = 800;

    return Sitrep::create(array_merge([
        'reference_number' => 'SITREP-M-'.$seq++,
        'unit_id' => $creator->unit_id,
        'report_type_id' => ReportType::where('name', 'Checkpoint')->first()->id,
        'current_status_id' => Status::where('name', 'Submitted')->first()->id,
        'created_by' => $creator->id,
        'dynamic_fields' => [],
        'latitude' => 14.12,
        'longitude' => 122.95,
        'location_captured_at' => now(),
        'submitted_at' => now(),
    ], $overrides));
}

function mapReviewer(): User
{
    return User::factory()->create()->assignRole('command-center');
}

it('forbids reporting personnel from the map', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');

    $this->actingAs($officer)->get(route('command.map'))->assertForbidden();
    $this->actingAs($officer)->get(route('command.map.data'))->assertForbidden();
});

it('returns only location-captured reports as markers', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');
    $withGps = mapSitrep($officer);
    $withoutGps = mapSitrep($officer, ['latitude' => null, 'longitude' => null]);
    $draft = mapSitrep($officer, ['current_status_id' => Status::where('name', 'Draft')->first()->id, 'submitted_at' => null]);

    $response = $this->actingAs(mapReviewer())->getJson(route('command.map.data'));

    $response->assertOk();
    $refs = collect($response->json('markers'))->pluck('reference');
    expect($refs)->toContain($withGps->reference_number)
        ->not->toContain($withoutGps->reference_number)
        ->not->toContain($draft->reference_number);

    $marker = collect($response->json('markers'))->first();
    expect($marker)->toHaveKeys(['reference', 'unit', 'type', 'status', 'lat', 'lng', 'captured_at', 'url']);
});

it('filters markers by status', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])->assignRole('reporting-personnel');
    $submitted = mapSitrep($officer);
    $validated = mapSitrep($officer, ['current_status_id' => Status::where('name', 'Validated')->first()->id]);

    $response = $this->actingAs(mapReviewer())
        ->getJson(route('command.map.data', ['status' => 'Validated']));

    $refs = collect($response->json('markers'))->pluck('reference');
    expect($refs)->toContain($validated->reference_number)->not->toContain($submitted->reference_number);
});

it('renders the map page for reviewers', function () {
    $this->actingAs(mapReviewer())
        ->get(route('command.map'))
        ->assertOk()
        ->assertSee('ops-map', false);   // the map container div
});
