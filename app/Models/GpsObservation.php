<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_point_id',
        'captured_by',
        'captured_latitude',
        'captured_longitude',
        'device_accuracy_meters',
        'computed_error_meters',
        'device_timestamp',
        'server_received_at',
    ];

    protected $casts = [
        'captured_latitude' => 'decimal:7',
        'captured_longitude' => 'decimal:7',
        'device_accuracy_meters' => 'decimal:2',
        'computed_error_meters' => 'decimal:2',
        'device_timestamp' => 'datetime',
        'server_received_at' => 'datetime',
    ];

    public function referencePoint(): BelongsTo
    {
        return $this->belongsTo(ReferencePoint::class);
    }

    public function capturedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }
}
