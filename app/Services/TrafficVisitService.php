<?php

namespace App\Services;

use App\Models\TrafficVisit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class TrafficVisitService
{
    private const TABLE = 'traffic_visits';

    private const SOCIAL_SOURCES = [
        'instagram',
        'whatsapp',
        'facebook',
        'tiktok',
        'threads',
        'x',
        'linkedin',
        'youtube',
    ];

    public function storageReady(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    public function recordVisit(array $attributes, string $ipAddress, string $userAgent = ''): void
    {
        if (! $this->storageReady()) {
            return;
        }

        $visitedAt = $this->resolveVisitedAt($attributes['traffic_captured_at'] ?? null);
        $trafficSource = $this->normalizeTrafficSource(
            $attributes['traffic_source'] ?? $attributes['traffic_source_detail'] ?? ''
        );
        $trafficMedium = $this->normalizeTrafficToken($attributes['traffic_medium'] ?? '');
        $trafficSourceDetail = $this->normalizeOptionalValue($attributes['traffic_source_detail'] ?? '');

        TrafficVisit::query()->create([
            'ip_address' => $this->normalizeOptionalValue($ipAddress),
            'source_group' => $this->resolveSourceGroup($trafficSource, $trafficSourceDetail, $trafficMedium),
            'traffic_source' => $trafficSource !== '' ? $trafficSource : null,
            'traffic_source_detail' => $trafficSourceDetail,
            'traffic_medium' => $trafficMedium !== '' ? $trafficMedium : null,
            'traffic_campaign' => $this->normalizeOptionalValue($attributes['traffic_campaign'] ?? ''),
            'traffic_referrer_host' => $this->normalizeOptionalLowercaseValue($attributes['traffic_referrer_host'] ?? ''),
            'traffic_landing_path' => $this->normalizeOptionalValue($attributes['traffic_landing_path'] ?? ''),
            'visit_date' => $visitedAt->toDateString(),
            'visited_at' => $visitedAt,
            'user_agent' => $this->normalizeOptionalValue($userAgent),
        ]);
    }

    public function dashboardSummary(): array
    {
        if (! $this->storageReady()) {
            return [
                'storage_ready' => false,
                'total_unique_ips' => 0,
                'google_search_unique_ips' => 0,
                'direct_unique_ips' => 0,
                'social_media_unique_ips' => 0,
            ];
        }

        $baseQuery = TrafficVisit::query()
            ->whereNotNull('ip_address')
            ->where('ip_address', '!=', '');

        return [
            'storage_ready' => true,
            'total_unique_ips' => $this->countDistinctIps(clone $baseQuery),
            'google_search_unique_ips' => $this->countDistinctIps(
                (clone $baseQuery)->where('source_group', 'google_search')
            ),
            'direct_unique_ips' => $this->countDistinctIps(
                (clone $baseQuery)->where('source_group', 'direct')
            ),
            'social_media_unique_ips' => $this->countDistinctIps(
                (clone $baseQuery)->where('source_group', 'social_media')
            ),
        ];
    }

    private function countDistinctIps(Builder $query): int
    {
        return (int) $query->distinct('ip_address')->count('ip_address');
    }

    private function resolveVisitedAt(mixed $value): CarbonImmutable
    {
        $timezone = (string) config('app.timezone', 'UTC');

        try {
            $stringValue = trim((string) $value);

            if ($stringValue !== '') {
                return CarbonImmutable::parse($stringValue, $timezone);
            }
        } catch (\Throwable) {
            // Fall back to current timestamp when the incoming payload is invalid.
        }

        return CarbonImmutable::now($timezone);
    }

    private function resolveSourceGroup(string $trafficSource, ?string $trafficSourceDetail, string $trafficMedium): string
    {
        $detail = $this->normalizeTrafficToken($trafficSourceDetail ?? '');

        if (
            $trafficSource === 'google'
            || $trafficMedium === 'search'
            || str_contains($detail, 'google')
        ) {
            return 'google_search';
        }

        if (
            $trafficSource === ''
            || $trafficSource === 'direct'
            || $trafficMedium === 'direct'
            || $detail === 'direct'
        ) {
            return 'direct';
        }

        if (
            in_array($trafficSource, self::SOCIAL_SOURCES, true)
            || $trafficMedium === 'social'
            || $this->looksLikeSocialSource($detail)
        ) {
            return 'social_media';
        }

        return 'other';
    }

    private function looksLikeSocialSource(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        foreach (self::SOCIAL_SOURCES as $source) {
            if (str_contains($value, $source)) {
                return true;
            }
        }

        return in_array($value, ['fb', 'ig', 'wa', 'tt', 'tweet', 'twitter'], true);
    }

    private function normalizeTrafficSource(mixed $value): string
    {
        $normalized = $this->normalizeTrafficToken($value);

        if ($normalized === '' || $normalized === 'direct') {
            return 'direct';
        }

        return match (true) {
            $normalized === 'ig',
            $normalized === 'insta',
            str_contains($normalized, 'instagram'),
            str_contains($normalized, 'ig-story') => 'instagram',
            $normalized === 'wa',
            str_contains($normalized, 'whatsapp'),
            str_contains($normalized, 'wa-broadcast') => 'whatsapp',
            $normalized === 'fb',
            str_contains($normalized, 'facebook') => 'facebook',
            $normalized === 'tt',
            str_contains($normalized, 'tiktok') => 'tiktok',
            $normalized === 'thread',
            str_contains($normalized, 'threads') => 'threads',
            $normalized === 'twitter',
            $normalized === 'tweet',
            $normalized === 'x',
            str_contains($normalized, 'twitter') => 'x',
            str_contains($normalized, 'linkedin') => 'linkedin',
            str_contains($normalized, 'youtube') => 'youtube',
            str_contains($normalized, 'google') => 'google',
            default => $normalized,
        };
    }

    private function normalizeTrafficToken(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';

        return trim($normalized, '-');
    }

    private function normalizeOptionalValue(mixed $value): ?string
    {
        $normalized = trim((string) preg_replace('/\s+/u', ' ', (string) $value));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeOptionalLowercaseValue(mixed $value): ?string
    {
        $normalized = $this->normalizeOptionalValue($value);

        return $normalized !== null ? strtolower($normalized) : null;
    }
}
