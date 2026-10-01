<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferencePoint extends Model
{
    use HasFactory;

    protected $fillable = ['label', 'latitude', 'longitude'];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function observations(): HasMany
    {
        return $this->hasMany(GpsObservation::class);
    }
}