<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * SITREPs filed under this unit. NOTE: reports may be filed for a unit
     * other than the filer's own (client-confirmed station selection), so
     * this is attribution, not just "reports by this unit's officers."
     *
     * @return HasMany<Sitrep, $this>
     */
    public function sitreps(): HasMany
    {
        return $this->hasMany(Sitrep::class);
    }
}
