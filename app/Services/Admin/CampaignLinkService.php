<?php

namespace App\Services\Admin;

use App\Models\CampaignLink;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Schema;

class CampaignLinkService
{
    private const TABLE = 'campaign_links';

    private const DESTINATION_OPTIONS = [
        'homepage' => [
            'label' => 'Homepage',
            'description' => 'Perfect when you want people to browse first, then click Register Now.',
        ],
        'register' => [
            'label' => 'Register Page',
            'description' => 'Best when you want direct conversion from ads, bios, or partner posts.',
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
        'direct' => 'Direct',
    ];

    private const SOURCE_SUGGESTIONS = [
        'instagram',
        'tiktok',
        'threads',
        'facebook',
        'whatsapp',
        'x',
        'linkedin',
        'youtube',
        'google',
        'media-partner',
        'website',
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
        return Schema::hasTable(self::TABLE);
    }

    public function storageNotReadyMessage(): string
    {
        return 'Campaign Links is not ready because the campaign_links table has not been created yet. Run php artisan migrate first.';
    }

    /**
     * @return EloquentCollection<int, CampaignLink>
     */
    public function manageableLinks(): EloquentCollection
    {
        if (! $this->storageReady()) {
            return new EloquentCollection;
        }

        return CampaignLink::query()
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->orderBy('name')
            ->get();
    }

    public function findManageableLinkById(int|string|null $id): ?CampaignLink
    {
        if (! $this->storageReady() || ! is_numeric((string) $id)) {
            return null;
        }

        return CampaignLink::query()->find((int) $id);
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
            ];
        }

        return [
            'storage_ready' => true,
            'total' => CampaignLink::query()->count(),
            'active' => CampaignLink::query()->where('is_active', true)->count(),
            'homepage' => CampaignLink::query()->where('destination', 'homepage')->count(),
            'register' => CampaignLink::query()->where('destination', 'register')->count(),
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

    public function normalizeName(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
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

    public function resolveDestinationUrl(string $destination): string
    {
        $normalizedDestination = $this->normalizeDestination($destination);

        if ($normalizedDestination === 'register') {
            $configuredRegisterUrl = trim((string) config('admin.future_urls.register'));

            return $configuredRegisterUrl !== ''
                ? rtrim($configuredRegisterUrl, '/')
                : route('register.form');
        }

        $configuredLandingUrl = trim((string) config('admin.future_urls.landing', config('app.url')));

        return $configuredLandingUrl !== ''
            ? rtrim($configuredLandingUrl, '/')
            : rtrim((string) config('app.url'), '/');
    }

    public function generatedUrl(array|CampaignLink $attributes): string
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

    public function present(array|CampaignLink $attributes): array
    {
        $destination = $this->normalizeDestination(data_get($attributes, 'destination'));
        $source = $this->normalizeToken(data_get($attributes, 'source'));
        $medium = $this->normalizeToken(data_get($attributes, 'medium'));
        $campaign = $this->normalizeToken(data_get($attributes, 'campaign'));
        $utmContent = $this->normalizeToken(data_get($attributes, 'utm_content'));
        $notes = $this->normalizeNotes(data_get($attributes, 'notes'));

        return [
            'id' => data_get($attributes, 'id'),
            'name' => $this->normalizeName(data_get($attributes, 'name')),
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
            'generated_url' => $this->generatedUrl([
                'destination' => $destination,
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
                'utm_content' => $utmContent,
            ]),
            'updated_at' => data_get($attributes, 'updated_at'),
        ];
    }

    private function humanizeToken(string $value): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $value));
    }
}
