<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    public function templateFields(): HasMany
    {
        return $this->hasMany(TemplateField::class)->orderBy('sort_order');
    }
}
