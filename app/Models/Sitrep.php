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
        'latitude' => 'float',        // CHANGED: was 'decimal:7'
        'longitude' => 'float',       // CHANGED: was 'decimal:7'
        'gps_accuracy_meters' => 'float', // CHANGED: was 'decimal:2'
        'location_captured_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<ReportType, $this>
     */
    public function reportType(): BelongsTo
    {
        return $this->belongsTo(ReportType::class);
    }

    /**
     * @return BelongsTo<Status, $this>
     */
    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'current_status_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<SitrepPersonnel, $this>
     */
    public function personnel(): HasMany
    {
        return $this->hasMany(SitrepPersonnel::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
