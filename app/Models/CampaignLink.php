<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CampaignLink extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'destination',
        'source',
        'medium',
        'campaign',
        'utm_content',
        'notes',
        'is_active',
        'visit_count',
        'last_visited_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'visit_count' => 'integer',
            'last_visited_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
