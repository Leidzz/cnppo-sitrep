<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'sitrep_id',
        'action',
        'previous_status',
        'new_status',
        'remarks',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Audit logs are append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Audit logs are append-only and cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sitrep(): BelongsTo
    {
        return $this->belongsTo(Sitrep::class);
    }
}
