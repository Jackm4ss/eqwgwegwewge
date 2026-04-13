<?php

namespace App\Services\Admin;

use App\Services\PublicReportService;
use App\Services\TrafficVisitService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminDashboardSnapshotService
{
    private const SNAPSHOT_CACHE_KEY_PREFIX = 'admin:dashboard:page-snapshot:v1';
    private const SNAPSHOT_REFRESH_LOCK_KEY_PREFIX = 'admin:dashboard:page-refreshing:v1';
    private const SNAPSHOT_PATH_PREFIX = 'admin-cache/dashboard-page-v1';

    public function __construct(
        private readonly AdminPanelService $adminPanel,
        private readonly CampaignLinkService $campaignLinkService,
        private readonly PublicReportService $publicReportService,
        private readonly TrafficVisitService $trafficVisitService,
    ) {}

    public function pageData(array $filters = [], bool $showAttendanceAnalytics = false): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);

        if (! $this->snapshotEnabled() || ! $this->shouldUseSnapshot($normalizedFilters)) {
            return $this->buildPagePayload($normalizedFilters, $showAttendanceAnalytics);
        }

        $snapshot = $this->cachedSnapshot($showAttendanceAnalytics);

        if ($this->validSnapshot($snapshot, $showAttendanceAnalytics)) {
            if ($this->snapshotIsStale($snapshot)) {
                $this->scheduleRefreshAfterResponse($showAttendanceAnalytics);
            }

            return (array) ($snapshot['payload'] ?? []);
        }

        $storedSnapshot = $this->storedSnapshot($showAttendanceAnalytics);

        if ($this->validSnapshot($storedSnapshot, $showAttendanceAnalytics)) {
            $this->storeSnapshotInCache($storedSnapshot, $showAttendanceAnalytics);
            $this->scheduleRefreshAfterResponse($showAttendanceAnalytics);

            return (array) ($storedSnapshot['payload'] ?? []);
        }

        return $this->refreshDefaultSnapshot($showAttendanceAnalytics);
    }

    public function warmDefaultSnapshot(bool $showAttendanceAnalytics = false): array
    {
        return $this->refreshDefaultSnapshot($showAttendanceAnalytics);
    }

    public function flushDefaultSnapshot(bool $showAttendanceAnalytics = false): void
    {
        Cache::forget($this->snapshotCacheKey($showAttendanceAnalytics));
        Cache::forget($this->snapshotRefreshLockKey($showAttendanceAnalytics));
        Storage::disk('local')->delete($this->snapshotPath($showAttendanceAnalytics));
    }

    private function refreshDefaultSnapshot(bool $showAttendanceAnalytics): array
    {
        $payload = $this->buildPagePayload([], $showAttendanceAnalytics);
        $snapshot = [
            'payload' => $payload,
            'generated_at' => now('UTC')->toIso8601String(),
            'show_attendance_analytics' => $showAttendanceAnalytics,
        ];

        $this->storeSnapshotInCache($snapshot, $showAttendanceAnalytics);
        $this->storeSnapshotOnDisk($snapshot, $showAttendanceAnalytics);

        return $payload;
    }

    private function buildPagePayload(array $filters, bool $showAttendanceAnalytics): array
    {
        return [
            'dashboard' => $showAttendanceAnalytics ? $this->adminPanel->dashboardData($filters) : [],
            'firestoreAvailable' => $this->adminPanel->firestoreAvailable(),
            'campaignLinkSummary' => $this->campaignLinkService->dashboardSummary(),
            'publicReportSummary' => $this->publicReportService->summary(),
            'trafficVisitSummary' => $this->trafficVisitService->dashboardSummary(),
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'from' => trim((string) ($filters['from'] ?? '')),
            'to' => trim((string) ($filters['to'] ?? '')),
        ];
    }

    private function shouldUseSnapshot(array $filters): bool
    {
        return ($filters['from'] ?? '') === '' && ($filters['to'] ?? '') === '';
    }

    private function validSnapshot(mixed $snapshot, bool $showAttendanceAnalytics): bool
    {
        if (! is_array($snapshot)) {
            return false;
        }

        if (($snapshot['show_attendance_analytics'] ?? null) !== $showAttendanceAnalytics) {
            return false;
        }

        $payload = $snapshot['payload'] ?? null;

        return is_array($payload)
            && array_key_exists('dashboard', $payload)
            && array_key_exists('firestoreAvailable', $payload)
            && array_key_exists('campaignLinkSummary', $payload)
            && array_key_exists('publicReportSummary', $payload)
            && array_key_exists('trafficVisitSummary', $payload)
            && filled($snapshot['generated_at'] ?? null);
    }

    private function snapshotIsStale(array $snapshot): bool
    {
        try {
            $generatedAt = CarbonImmutable::parse((string) ($snapshot['generated_at'] ?? ''), 'UTC');
        } catch (\Throwable) {
            return true;
        }

        return $generatedAt->diffInSeconds(now('UTC')) >= $this->snapshotFreshSeconds();
    }

    private function cachedSnapshot(bool $showAttendanceAnalytics): mixed
    {
        return Cache::get($this->snapshotCacheKey($showAttendanceAnalytics));
    }

    private function storedSnapshot(bool $showAttendanceAnalytics): mixed
    {
        $path = $this->snapshotPath($showAttendanceAnalytics);

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        try {
            $decoded = json_decode((string) Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $exception) {
            Log::warning('Unable to read the admin dashboard page snapshot fallback.', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function storeSnapshotInCache(array $snapshot, bool $showAttendanceAnalytics): void
    {
        Cache::put(
            $this->snapshotCacheKey($showAttendanceAnalytics),
            $snapshot,
            now()->addSeconds($this->snapshotTtlSeconds()),
        );
    }

    private function storeSnapshotOnDisk(array $snapshot, bool $showAttendanceAnalytics): void
    {
        try {
            Storage::disk('local')->put(
                $this->snapshotPath($showAttendanceAnalytics),
                json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            );
        } catch (\Throwable $exception) {
            Log::warning('Unable to persist the admin dashboard page snapshot fallback.', [
                'path' => $this->snapshotPath($showAttendanceAnalytics),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function scheduleRefreshAfterResponse(bool $showAttendanceAnalytics): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $refreshLockKey = $this->snapshotRefreshLockKey($showAttendanceAnalytics);

        if (! Cache::add(
            $refreshLockKey,
            now('UTC')->toIso8601String(),
            now()->addSeconds($this->snapshotRefreshLockSeconds()),
        )) {
            return;
        }

        app()->terminating(function () use ($refreshLockKey, $showAttendanceAnalytics): void {
            try {
                $this->refreshDefaultSnapshot($showAttendanceAnalytics);
            } catch (\Throwable $exception) {
                Log::warning('Failed to refresh the admin dashboard page snapshot after response.', [
                    'message' => $exception->getMessage(),
                    'show_attendance_analytics' => $showAttendanceAnalytics,
                ]);
            } finally {
                Cache::forget($refreshLockKey);
            }
        });
    }

    private function snapshotCacheKey(bool $showAttendanceAnalytics): string
    {
        return self::SNAPSHOT_CACHE_KEY_PREFIX.':'.($showAttendanceAnalytics ? 'attendance-on' : 'attendance-off');
    }

    private function snapshotRefreshLockKey(bool $showAttendanceAnalytics): string
    {
        return self::SNAPSHOT_REFRESH_LOCK_KEY_PREFIX.':'.($showAttendanceAnalytics ? 'attendance-on' : 'attendance-off');
    }

    private function snapshotPath(bool $showAttendanceAnalytics): string
    {
        return self::SNAPSHOT_PATH_PREFIX.'/'.($showAttendanceAnalytics ? 'attendance-on' : 'attendance-off').'.json';
    }

    private function snapshotEnabled(): bool
    {
        return (bool) config('admin.dashboard.snapshot_enabled', true);
    }

    private function snapshotFreshSeconds(): int
    {
        return max(5, (int) config('admin.dashboard.snapshot_fresh_seconds', 30));
    }

    private function snapshotTtlSeconds(): int
    {
        return max(
            $this->snapshotFreshSeconds() + 1,
            (int) config('admin.dashboard.snapshot_ttl_seconds', 900),
        );
    }

    private function snapshotRefreshLockSeconds(): int
    {
        return max(10, (int) config('admin.dashboard.snapshot_refresh_lock_seconds', 180));
    }
}
