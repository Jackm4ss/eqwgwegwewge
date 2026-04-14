<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LostFoundItem extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title',
        'description',
        'location_found',
        'found_date',
        'contact_info',
        'status',
        'image_path',
        'image_thumbnail_path',
    ];

    protected function casts(): array
    {
        return [
            'found_date' => 'date',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_AVAILABLE,
            self::STATUS_CLAIMED,
            self::STATUS_ARCHIVED,
        ];
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $nested) use ($term): void {
            $nested
                ->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('location_found', 'like', "%{$term}%")
                ->orWhere('contact_info', 'like', "%{$term}%")
                ->orWhere('status', 'like', "%{$term}%");
        });
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }
}
