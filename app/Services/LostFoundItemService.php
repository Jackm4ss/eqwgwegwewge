<?php

namespace App\Services;

use App\Models\LostFoundItem;
use App\Support\LostFoundContactFormatter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class LostFoundItemService
{
    public const TABLE = 'lost_found_items';
    public const PUBLIC_CACHE_VERSION_KEY = 'lost-found:public:version:v1';
    private const PUBLIC_CACHE_KEY_PREFIX = 'lost-found:public:list:v1';
    private const PUBLIC_CACHE_TTL_SECONDS = 60;
    private const IMAGE_DIRECTORY = 'lost-found/items';
    private const IMAGE_QUALITY = 82;
    private const IMAGE_MAX_EDGE = 1600;
    private const THUMBNAIL_MAX_EDGE = 480;

    public function storageReady(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    public function storageNotReadyMessage(): string
    {
        return 'Lost & Found Management is not ready because the lost_found_items table has not been created yet. Run php artisan migrate first.';
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{q:string,status:string,per_page:int}
     */
    public function sanitizeAdminFilters(array $query): array
    {
        $status = strtolower(trim((string) ($query['status'] ?? 'all')));

        return [
            'q' => trim((string) ($query['q'] ?? '')),
            'status' => in_array($status, ['all', ...LostFoundItem::statuses()], true) ? $status : 'all',
            'per_page' => in_array((int) ($query['per_page'] ?? 10), [10, 25, 50, 100], true)
                ? (int) ($query['per_page'] ?? 10)
                : 10,
        ];
    }

    public function findManageableItemById(int|string|null $id): ?LostFoundItem
    {
        if (! $this->storageReady() || ! is_numeric((string) $id)) {
            return null;
        }

        return LostFoundItem::query()->find((int) $id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        if (! $this->storageReady()) {
            return $this->emptyAdminPaginator((int) ($filters['per_page'] ?? 10));
        }

        $perPage = in_array((int) ($filters['per_page'] ?? 10), [10, 25, 50, 100], true)
            ? (int) ($filters['per_page'] ?? 10)
            : 10;

        return $this->adminBaseQuery($filters)
            ->orderByDesc('found_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    public function statusOptions(): array
    {
        return [
            ['value' => LostFoundItem::STATUS_AVAILABLE, 'label' => 'Available'],
            ['value' => LostFoundItem::STATUS_CLAIMED, 'label' => 'Claimed'],
            ['value' => LostFoundItem::STATUS_ARCHIVED, 'label' => 'Archived'],
        ];
    }

    public function statusLabel(string $value): string
    {
        return match ($value) {
            LostFoundItem::STATUS_CLAIMED => 'Claimed',
            LostFoundItem::STATUS_ARCHIVED => 'Archived',
            default => 'Available',
        };
    }

    public function statusBadgeClass(string $value): string
    {
        return match ($value) {
            LostFoundItem::STATUS_CLAIMED => 'bg-label-warning',
            LostFoundItem::STATUS_ARCHIVED => 'bg-label-secondary',
            default => 'bg-label-success',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function store(array $payload): LostFoundItem
    {
        if (! $this->storageReady()) {
            throw new RuntimeException($this->storageNotReadyMessage());
        }

        $imagePaths = $this->storeOptimizedImage($payload['image']);

        try {
            $item = DB::transaction(function () use ($imagePaths, $payload): LostFoundItem {
                return LostFoundItem::query()->create([
                    'title' => (string) $payload['title'],
                    'description' => (string) $payload['description'],
                    'location_found' => (string) $payload['location_found'],
                    'found_date' => (string) $payload['found_date'],
                    'contact_info' => (string) $payload['contact_info'],
                    'status' => (string) $payload['status'],
                    'image_path' => $imagePaths['image_path'],
                    'image_thumbnail_path' => $imagePaths['image_thumbnail_path'],
                ]);
            });
        } catch (Throwable $throwable) {
            $this->deleteStoredFiles($imagePaths);

            throw $throwable;
        }

        $this->flushPublicCache();

        return $item->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(LostFoundItem $item, array $payload): LostFoundItem
    {
        if (! $this->storageReady()) {
            throw new RuntimeException($this->storageNotReadyMessage());
        }

        $existingPaths = [
            'image_path' => (string) $item->image_path,
            'image_thumbnail_path' => (string) $item->image_thumbnail_path,
        ];

        $newImagePaths = null;

        if (($payload['image'] ?? null) instanceof UploadedFile) {
            $newImagePaths = $this->storeOptimizedImage($payload['image']);
        }

        try {
            $item->fill([
                'title' => (string) $payload['title'],
                'description' => (string) $payload['description'],
                'location_found' => (string) $payload['location_found'],
                'found_date' => (string) $payload['found_date'],
                'contact_info' => (string) $payload['contact_info'],
                'status' => (string) $payload['status'],
                'image_path' => $newImagePaths['image_path'] ?? $existingPaths['image_path'],
                'image_thumbnail_path' => $newImagePaths['image_thumbnail_path'] ?? $existingPaths['image_thumbnail_path'],
            ]);

            $item->save();
        } catch (Throwable $throwable) {
            if (is_array($newImagePaths)) {
                $this->deleteStoredFiles($newImagePaths);
            }

            throw $throwable;
        }

        if (is_array($newImagePaths)) {
            $this->deleteStoredFiles($existingPaths);
        }

        $this->flushPublicCache();

        return $item->refresh();
    }

    /**
     * @return array<string, string>
     */
    public function delete(LostFoundItem $item): array
    {
        $snapshot = [
            'id' => (string) $item->getKey(),
            'title' => (string) $item->title,
            'status' => (string) $item->status,
            'image_path' => (string) $item->image_path,
            'image_thumbnail_path' => (string) $item->image_thumbnail_path,
        ];

        $paths = [
            'image_path' => (string) $item->image_path,
            'image_thumbnail_path' => (string) $item->image_thumbnail_path,
        ];

        $item->delete();
        $this->deleteStoredFiles($paths);
        $this->flushPublicCache();

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     meta: array<string, int>
     * }
     */
    public function publicList(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(max((int) ($filters['per_page'] ?? 12), 1), 48);

        if (! $this->storageReady()) {
            return $this->emptyPublicPayload($page, $perPage);
        }

        return Cache::remember(
            $this->publicCacheKey($page, $perPage),
            now()->addSeconds(self::PUBLIC_CACHE_TTL_SECONDS),
            function () use ($page, $perPage): array {
                $paginator = LostFoundItem::query()
                    ->available()
                    ->orderByDesc('found_date')
                    ->orderByDesc('id')
                    ->paginate($perPage, ['*'], 'page', $page);

                return [
                    'data' => $paginator->getCollection()
                        ->map(fn (LostFoundItem $item): array => $this->presentForPublic($item))
                        ->values()
                        ->all(),
                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                    ],
                ];
            },
        );
    }

    public function publicImageUrl(?string $path): ?string
    {
        $normalizedPath = trim((string) $path);

        if ($normalizedPath === '') {
            return null;
        }

        return Storage::disk('public')->url($normalizedPath);
    }

    public function adminContactValue(?string $value): string
    {
        return LostFoundContactFormatter::adminDisplayValue($value);
    }

    public function adminContactCountryCode(?string $value): string
    {
        return LostFoundContactFormatter::adminCountryCode($value);
    }

    public function adminContactPhoneNumber(?string $value): string
    {
        return LostFoundContactFormatter::adminPhoneNumber($value);
    }

    public function formatPublicContactInfo(?string $value): string
    {
        return LostFoundContactFormatter::formatForPublic($value);
    }

    public function flushPublicCache(): void
    {
        $currentVersion = max(1, (int) Cache::get(self::PUBLIC_CACHE_VERSION_KEY, 1));

        Cache::forever(self::PUBLIC_CACHE_VERSION_KEY, $currentVersion + 1);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function adminBaseQuery(array $filters): Builder
    {
        return LostFoundItem::query()
            ->search((string) ($filters['q'] ?? ''))
            ->when(
                filled($filters['status'] ?? null) && ($filters['status'] ?? 'all') !== 'all',
                fn (Builder $query): Builder => $query->where('status', (string) $filters['status'])
            );
    }

    private function emptyAdminPaginator(int $perPage): LengthAwarePaginator
    {
        return new PaginationLengthAwarePaginator(
            new EloquentCollection,
            0,
            $perPage,
            1,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function storeOptimizedImage(UploadedFile $image): array
    {
        $sourceMimeType = (string) $image->getMimeType();
        $sourcePath = $image->getRealPath();

        if ($sourcePath === false || $sourcePath === '') {
            throw new RuntimeException('The uploaded image could not be read.');
        }

        $source = $this->createImageResource($sourcePath, $sourceMimeType);
        $source = $this->applyExifOrientation($source, $sourcePath, $sourceMimeType);

        $mainImage = $this->resizeToJpegCanvas($source, self::IMAGE_MAX_EDGE);
        $thumbnailImage = $this->resizeToJpegCanvas($source, self::THUMBNAIL_MAX_EDGE);
        $fileName = Str::uuid()->toString();
        $directory = trim(self::IMAGE_DIRECTORY.'/'.now()->format('Y/m'), '/');
        $imagePath = "{$directory}/{$fileName}.jpg";
        $thumbnailPath = "{$directory}/{$fileName}-thumb.jpg";

        Storage::disk('public')->put($imagePath, $this->encodeJpeg($mainImage, self::IMAGE_QUALITY));
        Storage::disk('public')->put($thumbnailPath, $this->encodeJpeg($thumbnailImage, self::IMAGE_QUALITY));

        imagedestroy($source);
        imagedestroy($mainImage);
        imagedestroy($thumbnailImage);

        return [
            'image_path' => $imagePath,
            'image_thumbnail_path' => $thumbnailPath,
        ];
    }

    /**
     * @param  resource|\GdImage  $resource
     * @return resource|\GdImage
     */
    private function createImageResource(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            default => throw new RuntimeException('Unsupported image format. Please upload a JPG or PNG file.'),
        };
    }

    /**
     * @param  resource|\GdImage  $resource
     * @return resource|\GdImage
     */
    private function applyExifOrientation($resource, string $path, string $mimeType)
    {
        if ($mimeType !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $resource;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $updatedResource = match ($orientation) {
            3 => imagerotate($resource, 180, 0),
            6 => imagerotate($resource, 270, 0),
            8 => imagerotate($resource, 90, 0),
            default => $resource,
        };

        if ($updatedResource !== $resource) {
            imagedestroy($resource);
        }

        return $updatedResource;
    }

    /**
     * @param  resource|\GdImage  $resource
     * @return resource|\GdImage
     */
    private function resizeToJpegCanvas($resource, int $maxEdge)
    {
        $width = imagesx($resource);
        $height = imagesy($resource);
        $largestEdge = max($width, $height);
        $ratio = $largestEdge > $maxEdge ? ($maxEdge / $largestEdge) : 1;
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($canvas === false) {
            throw new RuntimeException('Unable to prepare the image canvas.');
        }

        $background = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $background);
        imagecopyresampled($canvas, $resource, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $canvas;
    }

    /**
     * @param  resource|\GdImage  $resource
     */
    private function encodeJpeg($resource, int $quality): string
    {
        ob_start();

        imagejpeg($resource, null, $quality);

        return (string) ob_get_clean();
    }

    /**
     * @param  array<string, string|null>  $paths
     */
    private function deleteStoredFiles(array $paths): void
    {
        $pathsToDelete = array_values(array_filter(array_map(
            static fn (?string $path): string => trim((string) $path),
            $paths,
        )));

        if ($pathsToDelete === []) {
            return;
        }

        Storage::disk('public')->delete($pathsToDelete);
    }

    private function publicCacheKey(int $page, int $perPage): string
    {
        $version = max(1, (int) Cache::get(self::PUBLIC_CACHE_VERSION_KEY, 1));

        return implode(':', [
            self::PUBLIC_CACHE_KEY_PREFIX,
            $version,
            $page,
            $perPage,
        ]);
    }

    /**
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     meta: array<string, int>
     * }
     */
    private function emptyPublicPayload(int $page, int $perPage): array
    {
        return [
            'data' => [],
            'meta' => [
                'current_page' => $page,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentForPublic(LostFoundItem $item): array
    {
        return [
            'id' => (int) $item->getKey(),
            'title' => (string) $item->title,
            'description' => (string) $item->description,
            'description_excerpt' => Str::limit((string) $item->description, 180),
            'location_found' => (string) $item->location_found,
            'found_date' => (string) optional($item->found_date)->format('Y-m-d'),
            'found_date_label' => (string) optional($item->found_date)->format('d M Y'),
            'contact_info' => $this->formatPublicContactInfo($item->contact_info),
            'status' => (string) $item->status,
            'status_label' => $this->statusLabel((string) $item->status),
            'image_url' => $this->publicImageUrl($item->image_path),
            'thumbnail_url' => $this->publicImageUrl($item->image_thumbnail_path ?: $item->image_path),
        ];
    }
}
