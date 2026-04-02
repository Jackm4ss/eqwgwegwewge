<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrafficVisit extends Model
{
    protected $fillable = [
        'ip_address',
        'source_group',
        'traffic_source',
        'traffic_source_detail',
        'traffic_medium',
        'traffic_campaign',
        'traffic_referrer_host',
        'traffic_landing_path',
        'visit_date',
        'visited_at',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'visited_at' => 'datetime',
        ];
    }
}
