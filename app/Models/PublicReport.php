<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublicReport extends Model
{
    public const ACTION_STATUS_PENDING = 'pending';
    public const ACTION_STATUS_IN_PROGRESS = 'in_progress';
    public const ACTION_STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'case_id',
        'report_type',
        'case_prefix',
        'case_sequence',
        'name',
        'email',
        'phone',
        'identity_type',
        'identity_number',
        'incident_date',
        'incident_time',
        'chronology',
        'action_status',
        'admin_note',
        'staff_name',
        'ip_address',
        'user_agent',
        'reported_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'reported_at' => 'datetime',
    ];

    /**
     * @return array<int, string>
     */
    public static function actionStatuses(): array
    {
        return [
            self::ACTION_STATUS_PENDING,
            self::ACTION_STATUS_IN_PROGRESS,
            self::ACTION_STATUS_RESOLVED,
        ];
    }

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
                ->orWhere('identity_type', 'like', "%{$term}%")
                ->orWhere('identity_number', 'like', "%{$term}%")
                ->orWhere('action_status', 'like', "%{$term}%")
                ->orWhere('admin_note', 'like', "%{$term}%")
                ->orWhere('chronology', 'like', "%{$term}%");
        });
    }
}
