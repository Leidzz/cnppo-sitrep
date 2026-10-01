<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SitrepPersonnel extends Model
{
    use HasFactory;

    protected $table = 'sitrep_personnel';

    protected $fillable = ['sitrep_id', 'name', 'position'];

    public function sitrep(): BelongsTo
    {
        return $this->belongsTo(Sitrep::class);
    }
}