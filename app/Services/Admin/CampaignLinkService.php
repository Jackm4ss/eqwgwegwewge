<?php

namespace App\Services\Admin;

use App\Models\CampaignLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CampaignLinkService
{
    private const TABLE = 'campaign_links';

    private const REQUIRED_COLUMNS = [
        'id',
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
        'created_at',
        'updated_at',
    ];

    private const DESTINATION_OPTIONS = [
        'homepage' => [
            'label' => 'Homepage',
            'description' => 'Send visitors to the public landing page first.',
        ],
        'register' => [
            'label' => 'Register Page',
            'description' => 'Send visitors straight to the public registration page.',
        ],
    ];

    private const SOURCE_LABELS = [
        'instagram' => 'Instagram',
        'whatsapp' => 'WhatsApp',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'threads' => 'Threads',
        'x' => 'X',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'google' => 'Google',
        'website' => 'Website',
        'media-partner' => 'Media Partner',
        'wob' => 'Wob',
        'noodou' => 'Noodou',
        'ilovemalaysiafood' => 'Ilovemalaysiafood',
        'fooddiver' => 'Fooddiver',
        'edmhub' => 'Edmhub',
        'ig' => 'Ig',
        'fb' => 'Fb',
        'tya' => 'Tya',
        'direct' => 'Direct',
    ];

    private const SOURCE_SUGGESTIONS = [
        'Instagram',
        'TikTok',
        'Threads',
        'Facebook',
        'WhatsApp',
        'X',
        'LinkedIn',
        'YouTube',
        'Google',
        'Media Partner',
        'Website',
        'Wob',
        'Noodou',
        'Ilovemalaysiafood',
        'Fooddiver',
        'Edmhub',
        'Ig',
        'Fb',
        'Tya',
    ];

    private const MEDIUM_SUGGESTIONS = [
        'bio',
        'story',
        'post',
        'reel',
        'ads',
        'broadcast',
        'banner',
        'community',
        'referral',
    ];

    public function storageReady(): bool
    {
        return $this->missingRequiredColumns() === [];
    }

    public function storageNotReadyMessage(): string
    {
        if (! Schema::hasTable(self::TABLE)) {
            return 'Campaign Links is not ready because the campaign_links table has not been created yet. Run php artisan migrate first.';
        }

        return 'Campaign Links is not ready because the campaign_links table schema is outdated. Run php artisan migrate first.';
    }

    /**
     * @return EloquentCollection<int, CampaignLink>
     */
    public function manageableLinks(array $filters = []): EloquentCollection
    {
        if (! $this->storageReady()) {
            return new EloquentCollection;
        }

        return $this->manageableLinkQuery($filters)->get();
    }

    public function findManageableLinkById(int|string|null $id): ?CampaignLink
    {
        if (! $this->storageReady() || ! is_numeric((string) $id)) {
            return null;
        }

        return CampaignLink::query()->find((int) $id);
    }

    public function findBySlug(string $slug): ?CampaignLink
    {
        if (! $this->storageReady()) {
            return null;
        }

        $normalizedSlug = $this->normalizeSlug($slug);

        if ($normalizedSlug === '') {
            return null;
        }

        return CampaignLink::query()
            ->where('slug', $normalizedSlug)
            ->first();
    }

    public function dashboardSummary(): array
    {
        if (! $this->storageReady()) {
            return [
                'storage_ready' => false,
                'total' => 0,
                'active' => 0,
                'homepage' => 0,
                'register' => 0,
                'visits' => 0,
            ];
        }

        return [
            'storage_ready' => true,
            'total' => CampaignLink::query()->count(),
            'active' => CampaignLink::query()->where('is_active', true)->count(),
            'homepage' => CampaignLink::query()->where('destination', 'homepage')->count(),
            'register' => CampaignLink::query()->where('destination', 'register')->count(),
            'visits' => (int) CampaignLink::query()->sum('visit_count'),
        ];
    }

    public function analyticsSummary(array $filters = []): array
    {
        if (! $this->storageReady()) {
            return [
                'filtered_total' => 0,
                'filtered_active' => 0,
                'filtered_inactive' => 0,
                'filtered_visits' => 0,
            ];
        }

        $query = $this->manageableLinkQuery($filters);

        return [
            'filtered_total' => (clone $query)->count(),
            'filtered_active' => (clone $query)->where('is_active', true)->count(),
            'filtered_inactive' => (clone $query)->where('is_active', false)->count(),
            'filtered_visits' => (int) (clone $query)->sum('visit_count'),
        ];
    }

    public function sourceVisitBreakdown(array $filters = []): array
    {
        if (! $this->storageReady()) {
            return [
                'has_data' => false,
                'total_visits' => 0,
                'source_count' => 0,
                'top_source_label' => '-',
                'top_source_visits' => 0,
                'labels' => [],
                'series' => [],
                'rows' => [],
            ];
        }

        $rows = $this->manageableLinks($filters)
            ->groupBy(function (CampaignLink $campaignLink): string {
                $normalizedSource = $this->normalizeToken($campaignLink->source);

                return $normalizedSource !== '' ? $normalizedSource : 'direct';
            })
            ->map(function (EloquentCollection $campaignLinks, string $source): array {
                return [
                    'source' => $source,
                    'label' => $this->sourceLabel($source),
                    'visits' => (int) $campaignLinks->sum('visit_count'),
                    'links' => $campaignLinks->count(),
                ];
            })
            ->filter(fn (array $row): bool => $row['visits'] > 0)
            ->sortByDesc('visits')
            ->values();

        $totalVisits = (int) $rows->sum('visits');

        $rows = $rows
            ->map(function (array $row) use ($totalVisits): array {
                return array_merge($row, [
                    'percentage' => $totalVisits > 0
                        ? round(($row['visits'] / $totalVisits) * 100, 1)
                        : 0.0,
                ]);
            })
            ->values()
            ->all();

        return [
            'has_data' => $totalVisits > 0 && $rows !== [],
            'total_visits' => $totalVisits,
            'source_count' => count($rows),
            'top_source_label' => data_get($rows, '0.label', '-'),
            'top_source_visits' => (int) data_get($rows, '0.visits', 0),
            'labels' => array_map(
                static fn (array $row): string => (string) data_get($row, 'label', ''),
                $rows
            ),
            'series' => array_map(
                static fn (array $row): int => (int) data_get($row, 'visits', 0),
                $rows
            ),
            'rows' => $rows,
        ];
    }

    public function destinationOptions(): array
    {
        return self::DESTINATION_OPTIONS;
    }

    public function sourceSuggestions(): array
    {
        return self::SOURCE_SUGGESTIONS;
    }

    public function mediumSuggestions(): array
    {
        return self::MEDIUM_SUGGESTIONS;
    }

    public function sourceFilterOptions(): array
    {
        $options = collect(self::SOURCE_SUGGESTIONS);

        if ($this->storageReady()) {
            $options = $options->merge(
                CampaignLink::query()
                    ->whereNotNull('source')
                    ->pluck('source')
                    ->all()
            );
        }

        return $options
            ->map(fn (mixed $source): string => $this->normalizeToken($source))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function sanitizeFilters(array $filters): array
    {
        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        $destination = strtolower(trim((string) ($filters['destination'] ?? 'all')));

        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'status' => in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all',
            'destination' => $destination === 'all' || array_key_exists($destination, self::DESTINATION_OPTIONS)
                ? $destination
                : 'all',
            'source' => ($filters['source'] ?? 'all') === 'all'
                ? 'all'
                : $this->normalizeToken($filters['source'] ?? ''),
        ];
    }

    public function normalizeName(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    public function normalizeSlug(mixed $value): string
    {
        return Str::slug((string) $value);
    }

    public function normalizeDestination(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return array_key_exists($normalized, self::DESTINATION_OPTIONS) ? $normalized : 'homepage';
    }

    public function normalizeToken(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';

        return trim($normalized, '-');
    }

    public function normalizeNotes(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }

    public function destinationLabel(string $destination): string
    {
        return self::DESTINATION_OPTIONS[$this->normalizeDestination($destination)]['label']
            ?? self::DESTINATION_OPTIONS['homepage']['label'];
    }

    public function sourceLabel(string $source): string
    {
        $normalized = $this->normalizeToken($source);

        if ($normalized === '') {
            return 'Not set';
        }

        return self::SOURCE_LABELS[$normalized] ?? $this->humanizeToken($normalized);
    }

    public function mediumLabel(string $medium): string
    {
        $normalized = $this->normalizeToken($medium);

        return $normalized !== '' ? $this->humanizeToken($normalized) : 'Not set';
    }

    public function homepageUrl(): string
    {
        $configuredHomepageUrl = trim((string) config('app.frontend_homepage_url', config('app.url')));

        if ($configuredHomepageUrl === '') {
            return rtrim((string) config('app.url'), '/');
        }

        return rtrim($configuredHomepageUrl, '/');
    }

    public function registerUrl(): string
    {
        $configuredRegisterUrl = trim((string) config('admin.future_urls.register'));

        if ($configuredRegisterUrl !== '') {
            return rtrim($configuredRegisterUrl, '/');
        }

        return $this->homepageUrl().'/register';
    }

    public function resolveDestinationUrl(string $destination): string
    {
        return $this->normalizeDestination($destination) === 'register'
            ? $this->registerUrl()
            : $this->homepageUrl();
    }

    public function shortUrl(array|CampaignLink $attributes): string
    {
        $slug = $this->normalizeSlug(data_get($attributes, 'slug'));
        $baseUrl = $this->homepageUrl();

        return $slug === '' ? $baseUrl : $baseUrl.'/'.$slug;
    }

    public function finalUrl(array|CampaignLink $attributes): string
    {
        $destination = $this->normalizeDestination(data_get($attributes, 'destination'));
        $baseUrl = $this->resolveDestinationUrl($destination);
        $params = array_filter([
            'utm_source' => $this->normalizeToken(data_get($attributes, 'source')),
            'utm_medium' => $this->normalizeToken(data_get($attributes, 'medium')),
            'utm_campaign' => $this->normalizeToken(data_get($attributes, 'campaign')),
            'utm_content' => $this->normalizeToken(data_get($attributes, 'utm_content')),
        ]);

        if ($params === []) {
            return $baseUrl;
        }

        return $baseUrl.(str_contains($baseUrl, '?') ? '&' : '?').http_build_query($params);
    }

    public function generatedUrl(array|CampaignLink $attributes): string
    {
        return $this->finalUrl($attributes);
    }

    public function recordVisit(CampaignLink $campaignLink): void
    {
        CampaignLink::withoutTimestamps(function () use ($campaignLink): void {
            $campaignLink->increment('visit_count', 1, [
                'last_visited_at' => now(),
            ]);
        });
    }

    public function reservedSlugs(): array
    {
        $reserved = [
            'admin',
            'api',
            'email',
            'forgot-password',
            'forgot-qr',
            'login',
            'pwa',
            'register',
            'reset-password',
            'reset-verify',
            'reset-sukses',
            'staff',
            'ticket',
        ];

        $adminPath = trim((string) config('admin.path', 'admin'));
        $staffPath = trim((string) config('scanner.path', 'staff'));

        if ($adminPath !== '') {
            $reserved[] = strtolower($adminPath);
        }

        if ($staffPath !== '') {
            $reserved[] = strtolower($staffPath);
        }

        return collect($reserved)
            ->map(fn (string $slug): string => strtolower(trim($slug)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function exportRows(array $filters = []): array
    {
        return $this->manageableLinks($filters)
            ->map(fn (CampaignLink $campaignLink): array => $this->present($campaignLink))
            ->values()
            ->all();
    }

    public function present(array|CampaignLink $attributes): array
    {
        $destination = $this->normalizeDestination(data_get($attributes, 'destination'));
        $slug = $this->normalizeSlug(data_get($attributes, 'slug'));
        $source = $this->normalizeToken(data_get($attributes, 'source'));
        $medium = $this->normalizeToken(data_get($attributes, 'medium'));
        $campaign = $this->normalizeToken(data_get($attributes, 'campaign'));
        $utmContent = $this->normalizeToken(data_get($attributes, 'utm_content'));
        $notes = $this->normalizeNotes(data_get($attributes, 'notes'));

        return [
            'id' => data_get($attributes, 'id'),
            'name' => $this->normalizeName(data_get($attributes, 'name')),
            'slug' => $slug,
            'slug_with_prefix' => $slug !== '' ? '/'.$slug : '/',
            'destination' => $destination,
            'destination_label' => $this->destinationLabel($destination),
            'source' => $source,
            'source_label' => $this->sourceLabel($source),
            'medium' => $medium,
            'medium_label' => $this->mediumLabel($medium),
            'campaign' => $campaign,
            'campaign_label' => $campaign !== '' ? $this->humanizeToken($campaign) : 'Not set',
            'utm_content' => $utmContent,
            'utm_content_label' => $utmContent !== '' ? $this->humanizeToken($utmContent) : '-',
            'notes' => $notes,
            'is_active' => (bool) data_get($attributes, 'is_active', true),
            'base_url' => $this->resolveDestinationUrl($destination),
            'short_url' => $this->shortUrl([
                'slug' => $slug,
            ]),
            'final_url' => $this->finalUrl([
                'destination' => $destination,
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
                'utm_content' => $utmContent,
            ]),
            'generated_url' => $this->finalUrl([
                'destination' => $destination,
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
                'utm_content' => $utmContent,
            ]),
            'visit_count' => (int) data_get($attributes, 'visit_count', 0),
            'last_visited_at' => data_get($attributes, 'last_visited_at'),
            'updated_at' => data_get($attributes, 'updated_at'),
        ];
    }

    private function manageableLinkQuery(array $filters = []): Builder
    {
        $sanitizedFilters = $this->sanitizeFilters($filters);

        return CampaignLink::query()
            ->when(
                $sanitizedFilters['q'] !== '',
                function (Builder $query) use ($sanitizedFilters): void {
                    $search = '%'.$sanitizedFilters['q'].'%';

                    $query->where(function (Builder $nestedQuery) use ($search): void {
                        $nestedQuery
                            ->where('name', 'like', $search)
                            ->orWhere('slug', 'like', $search)
                            ->orWhere('source', 'like', $search)
                            ->orWhere('medium', 'like', $search)
                            ->orWhere('campaign', 'like', $search)
                            ->orWhere('utm_content', 'like', $search)
                            ->orWhere('notes', 'like', $search);
                    });
                }
            )
            ->when(
                $sanitizedFilters['status'] === 'active',
                fn (Builder $query): Builder => $query->where('is_active', true)
            )
            ->when(
                $sanitizedFilters['status'] === 'inactive',
                fn (Builder $query): Builder => $query->where('is_active', false)
            )
            ->when(
                $sanitizedFilters['destination'] !== 'all',
                fn (Builder $query): Builder => $query->where('destination', $sanitizedFilters['destination'])
            )
            ->when(
                $sanitizedFilters['source'] !== 'all',
                fn (Builder $query): Builder => $query->where('source', $sanitizedFilters['source'])
            )
            ->orderByDesc('is_active')
            ->orderByDesc('visit_count')
            ->orderByDesc('updated_at')
            ->orderBy('name');
    }

    private function humanizeToken(string $value): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $value));
    }

    /**
     * @return array<int, string>
     */
    private function missingRequiredColumns(): array
    {
        if (! Schema::hasTable(self::TABLE)) {
            return self::REQUIRED_COLUMNS;
        }

        $existingColumns = collect(Schema::getColumnListing(self::TABLE));

        return collect(self::REQUIRED_COLUMNS)
            ->reject(fn (string $column): bool => $existingColumns->contains($column))
            ->values()
            ->all();
    }
}
