<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Services\Scanner\ScannerGateService;

class ScannerGate extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $flushCache = static function (): void {
            Cache::forget(ScannerGateService::CACHE_KEY);
        };

        static::saved($flushCache);
        static::deleted($flushCache);
    }
}
