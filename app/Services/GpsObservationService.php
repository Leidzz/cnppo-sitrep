<?php

namespace App\Services;

use App\Models\GpsObservation;
use App\Models\ReferencePoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GpsObservationService
{
    /**
     * Record one observation + compute its positional error against the
     * reference point, IN THE SAME TRANSACTION (rule: measured data must
     * never exist without its error value).
     */
    public function record(ReferencePoint $point, User $user, array $fix): GpsObservation
    {
        return DB::transaction(function () use ($point, $user, $fix) {
            $error = $this->distanceMeters(
                (float) $fix['latitude'], (float) $fix['longitude'],
                (float) $point->latitude, (float) $point->longitude,
            );

            return $point->observations()->create([
                'captured_by' => $user->id,
                'captured_latitude' => $fix['latitude'],
                'captured_longitude' => $fix['longitude'],
                'device_accuracy_meters' => $fix['accuracy'] ?? null,
                'device_timestamp' => $fix['device_timestamp'],
                'server_received_at' => now(),
                'device_label' => $fix['device_label'] ?? null,
                'conditions' => $fix['conditions'] ?? null,
                'computed_error_meters' => round($error, 2),
            ]);
        });
    }

    /**
     * Descriptive statistics per reference point — the thesis results table.
     */
    public function stats(ReferencePoint $point): array
    {
        $errors = $point->observations()->pluck('computed_error_meters')->filter();

        if ($errors->isEmpty()) {
            return ['count' => 0];
        }

        $mean = $errors->avg();
        $variance = $errors->map(fn ($e) => ($e - $mean) ** 2)->avg();

        return [
            'count' => $errors->count(),
            'mean' => round($mean, 2),
            'std_dev' => round(sqrt($variance), 2),
            'min' => round($errors->min(), 2),
            'max' => round($errors->max(), 2),
            'mean_device_accuracy' => round((float) $point->observations()->avg('device_accuracy_meters'), 2),
        ];
    }

    /**
     * Great-circle distance in meters (Haversine). Earth's mean radius
     * 6,371,000 m — accurate to ~0.5% for these short distances;
     * Vincenty would be overkill at capstone scale.
     */
    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
