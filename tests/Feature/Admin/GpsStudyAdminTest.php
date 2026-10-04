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
