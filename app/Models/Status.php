<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_final'];

    protected $casts = [
        'is_final' => 'boolean',
    ];

    public function transitionsFrom(): HasMany
    {
        return $this->hasMany(StatusTransition::class, 'from_status_id');
    }

    public function transitionsTo(): HasMany
    {
        return $this->hasMany(StatusTransition::class, 'to_status_id');
    }
}