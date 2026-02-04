<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'prompt_text',
        'language',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function siteProjects(): HasMany
    {
        return $this->hasMany(SiteProject::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
