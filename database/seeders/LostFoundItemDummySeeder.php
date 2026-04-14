<?php

namespace Database\Seeders;

use App\Models\LostFoundItem;
use App\Services\LostFoundItemService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LostFoundItemDummySeeder extends Seeder
{
    private const DEMO_TITLE_PREFIX = '[Demo] ';

    private const IMAGE_DIRECTORY = 'lost-found/items';

    /**
     * @var array<int, array{primary: array{0:int,1:int,2:int}, secondary: array{0:int,1:int,2:int}}>
     */
    private array $palette = [
        ['primary' => [14, 116, 144], 'secondary' => [34, 197, 94]],
        ['primary' => [30, 64, 175], 'secondary' => [6, 182, 212]],
        ['primary' => [91, 33, 182], 'secondary' => [236, 72, 153]],
        ['primary' => [153, 27, 27], 'secondary' => [249, 115, 22]],
        ['primary' => [22, 101, 52], 'secondary' => [132, 204, 22]],
        ['primary' => [67, 56, 202], 'secondary' => [59, 130, 246]],
        ['primary' => [15, 118, 110], 'secondary' => [16, 185, 129]],
        ['primary' => [124, 45, 18], 'secondary' => [251, 191, 36]],
    ];

    public function run(): void
    {
        if (! Schema::hasTable(LostFoundItemService::TABLE)) {
            $this->command?->warn('Skipping LostFoundItemDummySeeder because the lost_found_items table does not exist yet.');

            return;
        }

        $this->deleteExistingDemoItems();

        $items = $this->dummyItems();

        foreach ($items as $index => $item) {
            $paths = $this->createImagePair((string) $item['title'], $index);

            LostFoundItem::query()->create([
                'title' => self::DEMO_TITLE_PREFIX.$item['title'],
                'description' => $item['description'],
                'location_found' => $item['location_found'],
                'found_date' => $item['found_date'],
                'contact_info' => $item['contact_info'],
                'status' => LostFoundItem::STATUS_AVAILABLE,
                'image_path' => $paths['image_path'],
                'image_thumbnail_path' => $paths['image_thumbnail_path'],
            ]);
        }

        app(LostFoundItemService::class)->flushPublicCache();

        $this->command?->info('Injected 8 dummy Lost & Found items.');
    }

    /**
     * @return array<int, array{
     *     title: string,
     *     description: string,
     *     location_found: string,
     *     found_date: string,
     *     contact_info: string
     * }>
     */
    private function dummyItems(): array
    {
        return [
            [
                'title' => 'Black Leather Wallet',
                'description' => 'Wallet with several bank cards, a folded receipt, and a small family photo inside.',
                'location_found' => 'Main stage seating area, row B12',
                'found_date' => CarbonImmutable::now()->subDay()->toDateString(),
                'contact_info' => '+62 812-0000-1001',
            ],
            [
                'title' => 'Blue iPhone With Clear Case',
                'description' => 'Phone found with a transparent case and a faded sticker on the back.',
                'location_found' => 'Food court near Thai Tea stall',
                'found_date' => CarbonImmutable::now()->subDays(2)->toDateString(),
                'contact_info' => '+62 812-0000-1002',
            ],
            [
                'title' => 'Silver House Keys',
                'description' => 'Set of three keys on a red fabric keychain with a small cartoon charm.',
                'location_found' => 'Registration desk queue lane 3',
                'found_date' => CarbonImmutable::now()->subDays(3)->toDateString(),
                'contact_info' => '+62 812-0000-1003',
            ],
            [
                'title' => 'Brown Sunglasses Case',
                'description' => 'Hard sunglasses case containing dark round-frame glasses and a cleaning cloth.',
                'location_found' => 'Splash zone locker area',
                'found_date' => CarbonImmutable::now()->subDays(4)->toDateString(),
                'contact_info' => '+62 812-0000-1004',
            ],
            [
                'title' => 'White Power Bank 10000mAh',
                'description' => 'Compact power bank with one USB-C cable attached and minor scratches on the corner.',
                'location_found' => 'VIP tent charging station',
                'found_date' => CarbonImmutable::now()->subDays(5)->toDateString(),
                'contact_info' => '+62 812-0000-1005',
            ],
            [
                'title' => 'Stainless Water Bottle',
                'description' => 'Silver insulated bottle with black lid and a festival wristband looped around it.',
                'location_found' => 'Workshop area near merchandise booth',
                'found_date' => CarbonImmutable::now()->subDays(6)->toDateString(),
                'contact_info' => '+62 812-0000-1006',
            ],
            [
                'title' => 'Compact Makeup Pouch',
                'description' => 'Pink zipper pouch containing cosmetics, mirror, and travel-size skincare items.',
                'location_found' => 'Women restroom waiting bench',
                'found_date' => CarbonImmutable::now()->subDays(7)->toDateString(),
                'contact_info' => '+62 812-0000-1007',
            ],
            [
                'title' => 'Toyota Car Remote',
                'description' => 'Black key remote with a worn rubber button and a small silver ring.',
                'location_found' => 'Parking shuttle drop-off point',
                'found_date' => CarbonImmutable::now()->subDays(8)->toDateString(),
                'contact_info' => '+62 812-0000-1008',
            ],
        ];
    }

    private function deleteExistingDemoItems(): void
    {
        $existingItems = LostFoundItem::query()
            ->get(['id', 'title', 'image_path', 'image_thumbnail_path']);

        $demoItems = $existingItems->filter(
            fn (LostFoundItem $item): bool => str_starts_with((string) $item->title, self::DEMO_TITLE_PREFIX)
        );

        foreach ($demoItems as $item) {
            Storage::disk('public')->delete(array_values(array_filter([
                $item->image_path,
                $item->image_thumbnail_path,
            ])));
        }

        $demoIds = $demoItems
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        if ($demoIds !== []) {
            LostFoundItem::query()
                ->whereIn('id', $demoIds)
                ->delete();
        }
    }

    /**
     * @return array{image_path:string,image_thumbnail_path:string}
     */
    private function createImagePair(string $title, int $index): array
    {
        $directory = trim(self::IMAGE_DIRECTORY.'/'.now()->format('Y/m'), '/');
        $fileName = Str::uuid()->toString();
        $imagePath = "{$directory}/{$fileName}.jpg";
        $thumbnailPath = "{$directory}/{$fileName}-thumb.jpg";

        Storage::disk('public')->put($imagePath, $this->renderPlaceholderImage(1600, 1200, $title, $index));
        Storage::disk('public')->put($thumbnailPath, $this->renderPlaceholderImage(480, 360, $title, $index));

        return [
            'image_path' => $imagePath,
            'image_thumbnail_path' => $thumbnailPath,
        ];
    }

    private function renderPlaceholderImage(int $width, int $height, string $title, int $index): string
    {
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new \RuntimeException('Unable to create placeholder image for Lost & Found demo data.');
        }

        $palette = $this->palette[$index % count($this->palette)];
        $primary = imagecolorallocate($image, ...$palette['primary']);
        $secondary = imagecolorallocate($image, ...$palette['secondary']);
        $white = imagecolorallocate($image, 255, 255, 255);
        $overlay = imagecolorallocatealpha($image, 255, 255, 255, 90);

        imagefilledrectangle($image, 0, 0, $width, $height, $primary);
        imagefilledellipse($image, (int) ($width * 0.8), (int) ($height * 0.24), (int) ($width * 0.7), (int) ($height * 0.52), $secondary);
        imagefilledellipse($image, (int) ($width * 0.18), (int) ($height * 0.82), (int) ($width * 0.55), (int) ($height * 0.38), $secondary);
        imagefilledrectangle($image, (int) ($width * 0.08), (int) ($height * 0.12), (int) ($width * 0.92), (int) ($height * 0.88), $overlay);

        $header = 'LOST & FOUND DEMO';
        $wrappedTitle = explode("\n", wordwrap(Str::upper(Str::limit($title, 42, '')), 18, "\n", true));

        imagestring($image, 5, (int) ($width * 0.1), (int) ($height * 0.16), $header, $white);

        $lineY = (int) ($height * 0.36);

        foreach ($wrappedTitle as $line) {
            imagestring($image, 5, (int) ($width * 0.1), $lineY, $line, $white);
            $lineY += 24;
        }

        imagestring($image, 4, (int) ($width * 0.1), (int) ($height * 0.72), 'Songkran Festival 2026', $white);
        imagestring($image, 3, (int) ($width * 0.1), (int) ($height * 0.79), 'Dummy item for admin and public page testing', $white);

        ob_start();
        imagejpeg($image, null, 82);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary;
    }
}
