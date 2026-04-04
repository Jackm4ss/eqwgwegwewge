<?php

namespace App\Services\Staff;

final class StaffScannerDashboardCache
{
    private const VERSION = 'v1';

    public static function snapshotKey(string $scannerPost, string $scopeDate): string
    {
        return 'staff:scanner:dashboard:snapshot:'.self::VERSION.':'.self::dateSegment($scopeDate).':'.self::gateSegment($scannerPost);
    }

    public static function staleKey(string $scannerPost, string $scopeDate): string
    {
        return 'staff:scanner:dashboard:stale:'.self::VERSION.':'.self::dateSegment($scopeDate).':'.self::gateSegment($scannerPost);
    }

    public static function refreshLockKey(string $scannerPost, string $scopeDate): string
    {
        return 'staff:scanner:dashboard:refreshing:'.self::VERSION.':'.self::dateSegment($scopeDate).':'.self::gateSegment($scannerPost);
    }

    private static function dateSegment(string $scopeDate): string
    {
        $normalized = preg_replace('/[^0-9-]/', '', trim($scopeDate)) ?? '';

        return $normalized !== '' ? $normalized : 'unknown-date';
    }

    private static function gateSegment(string $scannerPost): string
    {
        $normalized = trim($scannerPost);

        return $normalized !== ''
            ? hash('sha1', $normalized)
            : 'unknown-gate';
    }
}
