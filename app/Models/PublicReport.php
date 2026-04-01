<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublicReport extends Model
{
    protected $fillable = [
        'case_id',
        'report_type',
        'case_prefix',
        'case_sequence',
        'name',
        'email',
        'phone',
        'identity_number',
        'incident_date',
        'incident_time',
        'chronology',
        'staff_name',
        'ip_address',
        'user_agent',
        'reported_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'reported_at' => 'datetime',
    ];

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $nested) use ($term) {
            $nested
                ->where('case_id', 'like', "%{$term}%")
                ->orWhere('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('identity_number', 'like', "%{$term}%")
                ->orWhere('chronology', 'like', "%{$term}%");
        });
    }
}
