<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferencePoint extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'description', 'latitude', 'longitude'];

    protected $casts = [
        'latitude' => 'float',     // float, NOT decimal:7 — we compute distances on these
        'longitude' => 'float',
    ];

    /**
     * @return HasMany<GpsObservation, $this>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(GpsObservation::class);
    }
}
