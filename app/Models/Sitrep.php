<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sitrep extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'unit_id',
        'report_type_id',
        'current_status_id',
        'created_by',
        'reviewed_by',
        'dynamic_fields',
        'latitude',
        'longitude',
        'location_description',
        'gps_accuracy_meters',
        'location_captured_at',
        'submitted_at',
        'reviewed_at',
        'review_remarks',
    ];

    protected $casts = [
        'dynamic_fields' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'gps_accuracy_meters' => 'decimal:2',
        'location_captured_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function reportType(): BelongsTo
    {
        return $this->belongsTo(ReportType::class);
    }

    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'current_status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function personnel(): HasMany
    {
        return $this->hasMany(SitrepPersonnel::class);
    }
}