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
        'device_label',
        'conditions',
        'captured_latitude',
        'captured_longitude',
        'device_accuracy_meters',
        'computed_error_meters',
        'device_timestamp',
        'server_received_at',
    ];

    protected $casts = [
        'captured_latitude' => 'float',        // float, NOT decimal:7 — stats math needs numbers
        'captured_longitude' => 'float',
        'device_accuracy_meters' => 'float',
        'computed_error_meters' => 'float',
        'device_timestamp' => 'datetime',
        'server_received_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<ReferencePoint, $this>
     */
    public function referencePoint(): BelongsTo
    {
        return $this->belongsTo(ReferencePoint::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function capturedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }
}
