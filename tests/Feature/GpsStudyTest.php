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

function researcher(): User
{
    return User::factory()->create(['unit_id' => Unit::first()->id]);
}

function pointAt(float $lat, float $lon): ReferencePoint
{
    return ReferencePoint::create(['label' => 'Test Point', 'latitude' => $lat, 'longitude' => $lon]);
}

it('computes zero error for a capture at the exact reference point', function () {
    $point = pointAt(14.1040563, 122.9538577);
    $service = app(GpsObservationService::class);

    $obs = $service->record($point, researcher(), [
        'latitude' => 14.1040563, 'longitude' => 122.9538577,
        'accuracy' => 4.5, 'device_timestamp' => now(),
    ]);

    expect($obs->computed_error_meters)->toBeBetween(0, 0.5);
});

it('computes about 111 meters for one-hundredth of a degree north', function () {
    $point = pointAt(14.1000000, 122.9500000);
    $service = app(GpsObservationService::class);

    $obs = $service->record($point, researcher(), [
        'latitude' => 14.1010000, 'longitude' => 122.9500000,  // 0.001° north ≈ 111 m
        'accuracy' => 5.0, 'device_timestamp' => now(),
    ]);

    expect($obs->computed_error_meters)->toBeBetween(110.0, 112.0);
});

it('produces descriptive stats for a reference point', function () {
    $point = pointAt(14.1000000, 122.9500000);
    $service = app(GpsObservationService::class);

    foreach ([5.0, 7.0, 9.0] as $err) {
        $service->record($point, researcher(), [
            'latitude' => 14.1000000 + ($err / 111000),
            'longitude' => 122.9500000,
            'accuracy' => 6.0, 'device_timestamp' => now(),
        ]);
    }

    $stats = $service->stats($point);
    expect($stats['count'])->toBe(3);
    expect($stats['mean'])->toBeBetween(6.9, 7.1);
    expect($stats['min'])->toBeBetween(4.9, 5.1);
    expect($stats['max'])->toBeBetween(8.9, 9.1);
});
