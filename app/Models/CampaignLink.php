<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignLink extends Model
{
    protected $fillable = [
        'name',
        'destination',
        'source',
        'medium',
        'campaign',
        'utm_content',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
