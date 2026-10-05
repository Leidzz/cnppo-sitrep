<?php

use App\Models\ReferencePoint;
use App\Models\Unit;
use App\Models\User;
use App\Services\GpsObservationService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RoleSeeder::class, UnitSeeder::class]);
});

function admin(): User
{
    return User::factory()->create()->assignRole('administrator');
}

it('lets admins create a reference point', function () {
    $this->actingAs(admin())->post(route('admin.gps.points.store'), [
        'label' => 'Basud Municipal Hall flagpole',
        'description' => 'Pinned from satellite imagery',
        'latitude' => 14.0706205,
        'longitude' => 122.9561234,
    ])->assertRedirect(route('admin.gps.index'));

    expect(ReferencePoint::sole()->label)->toBe('Basud Municipal Hall flagpole');
});

it('rejects invalid coordinates', function () {
    $this->actingAs(admin())->post(route('admin.gps.points.store'), [
        'label' => 'Bad point',
        'latitude' => 91,
        'longitude' => 200,
    ])->assertSessionHasErrors(['latitude', 'longitude']);
});

it('forbids non-admins from the study screens', function () {
    $officer = User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel');

    $this->actingAs($officer)->get(route('admin.gps.index'))->assertForbidden();
});

it('refuses to delete a point that has observations', function () {
    $point = ReferencePoint::create(['label' => 'P', 'latitude' => 14.1, 'longitude' => 122.9]);

    $service = app(GpsObservationService::class);
    $service->record($point, admin(), [
        'latitude' => 14.1, 'longitude' => 122.9,
        'device_timestamp' => now(),
    ]);

    $this->actingAs(admin())->delete(route('admin.gps.points.destroy', $point))
        ->assertStatus(422);
});

it('lets admins record an observation via the JSON endpoint', function () {
    $point = ReferencePoint::create(['label' => 'P1', 'latitude' => 14.1, 'longitude' => 122.9]);

    $res = $this->actingAs(admin())->postJson(route('admin.gps.observations.store', $point), [
        'latitude' => 14.1, 'longitude' => 122.9,
        'accuracy' => 5.5, 'device_timestamp' => now()->toIso8601String(),
        'device_label' => 'Test Phone', 'conditions' => 'outdoor',
    ]);

    $res->assertOk()->assertJsonPath('error', 0);
    expect($point->observations()->sole()->device_label)->toBe('Test Phone');
});

it('forbids non-admins from recording observations', function () {
    $point = ReferencePoint::create(['label' => 'P1', 'latitude' => 14.1, 'longitude' => 122.9]);

    $this->actingAs(User::factory()->create(['unit_id' => Unit::first()->id])
        ->assignRole('reporting-personnel'))
        ->postJson(route('admin.gps.observations.store', $point), [
            'latitude' => 14.1, 'longitude' => 122.9, 'device_timestamp' => now()->toIso8601String(),
        ])
        ->assertForbidden();
});

it('validates observation coordinates', function () {
    $point = ReferencePoint::create(['label' => 'P1', 'latitude' => 14.1, 'longitude' => 122.9]);

    $this->actingAs(admin())->postJson(route('admin.gps.observations.store', $point), [
        'latitude' => 999, 'longitude' => 14.1, 'device_timestamp' => now()->toIso8601String(),
    ])->assertUnprocessable();
});

it('shows the capture screen with live stats', function () {
    $point = ReferencePoint::create(['label' => 'Flagpole', 'latitude' => 14.1, 'longitude' => 122.9]);

    $this->actingAs(admin())
        ->get(route('admin.gps.capture', $point))
        ->assertOk()
        ->assertSee('Flagpole')
        ->assertSee('Capture & Record');
});
