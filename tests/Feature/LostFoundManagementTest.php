<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\LostFoundItem;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LostFoundManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        config([
            'admin.bootstrap_password' => 'LocalAdmin123!',
            'admin.seed_count' => 1,
        ]);

        $this->seed(AdminSeeder::class);
    }

    public function test_public_found_page_loads_the_spa_shell(): void
    {
        $this->get('/found')
            ->assertOk()
            ->assertViewIs('welcome');
    }

    public function test_public_found_items_api_returns_only_available_items_in_descending_found_date_order(): void
    {
        LostFoundItem::query()->create([
            'title' => 'Blue Tumbler',
            'description' => 'Found near the hydration station.',
            'location_found' => 'Hydration Station',
            'found_date' => '2026-04-10',
            'contact_info' => '+60 12-222 3333',
            'status' => LostFoundItem::STATUS_AVAILABLE,
            'image_path' => 'lost-found/items/2026/04/blue.jpg',
            'image_thumbnail_path' => 'lost-found/items/2026/04/blue-thumb.jpg',
        ]);

        LostFoundItem::query()->create([
            'title' => 'Black Wallet',
            'description' => 'Found close to the main stage barrier.',
            'location_found' => 'Main Stage',
            'found_date' => '2026-04-13',
            'contact_info' => '+60 12-111 2222',
            'status' => LostFoundItem::STATUS_AVAILABLE,
            'image_path' => 'lost-found/items/2026/04/wallet.jpg',
            'image_thumbnail_path' => 'lost-found/items/2026/04/wallet-thumb.jpg',
        ]);

        LostFoundItem::query()->create([
            'title' => 'Claimed Phone',
            'description' => 'Already returned to the owner.',
            'location_found' => 'VIP Entrance',
            'found_date' => '2026-04-14',
            'contact_info' => 'Help Desk Counter',
            'status' => LostFoundItem::STATUS_CLAIMED,
            'image_path' => 'lost-found/items/2026/04/phone.jpg',
            'image_thumbnail_path' => 'lost-found/items/2026/04/phone-thumb.jpg',
        ]);

        $this->getJson('/api/found-items')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Black Wallet')
            ->assertJsonPath('data.0.contact_info', '+60 12-111 2222')
            ->assertJsonPath('data.1.title', 'Blue Tumbler')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonMissing(['title' => 'Claimed Phone']);
    }

    public function test_admin_must_be_authenticated_to_access_lost_found_management_page(): void
    {
        $this->get('/admin/lost-found')
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_lost_found_management_page(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/lost-found')
            ->assertOk()
            ->assertSeeText('Lost & Found Management')
            ->assertSeeText('Create Lost & Found Item')
            ->assertSeeText('Found Item Library');
    }

    public function test_admin_can_create_update_and_delete_lost_found_items_with_optimized_images(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $image = UploadedFile::fake()->image('wallet.png', 2400, 1800)->size(3500);

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->post('/admin/lost-found', [
                '_token' => 'csrf-token',
                'title' => 'Black Wallet',
                'description' => 'Found close to the main stage barricade.',
                'location_found' => 'Main Stage Barricade',
                'found_date' => '2026-04-13',
                'contact_country_code' => '+60',
                'contact_phone_number' => '121112222',
                'status' => LostFoundItem::STATUS_AVAILABLE,
                'image' => $image,
            ])
            ->assertRedirect(route('admin.lost-found.index'))
            ->assertSessionHas('status', 'Lost & found item created successfully.');

        $item = LostFoundItem::query()->firstOrFail();

        $this->assertDatabaseHas('lost_found_items', [
            'id' => $item->id,
            'title' => 'Black Wallet',
            'status' => LostFoundItem::STATUS_AVAILABLE,
            'location_found' => 'Main Stage Barricade',
            'contact_info' => '+60 121112222',
        ]);

        Storage::disk('public')->assertExists($item->image_path);
        Storage::disk('public')->assertExists($item->image_thumbnail_path);

        [$mainWidth, $mainHeight] = getimagesize(Storage::disk('public')->path($item->image_path));
        [$thumbnailWidth, $thumbnailHeight] = getimagesize(Storage::disk('public')->path($item->image_thumbnail_path));

        $this->assertLessThanOrEqual(1600, max($mainWidth, $mainHeight));
        $this->assertLessThanOrEqual(480, max($thumbnailWidth, $thumbnailHeight));

        $originalImagePath = $item->image_path;
        $originalThumbnailPath = $item->image_thumbnail_path;

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->put("/admin/lost-found/{$item->id}", [
                '_token' => 'csrf-token',
                'lost_found_item_id' => (string) $item->id,
                'title' => 'Black Wallet Updated',
                'description' => 'Found and secured by the help desk team.',
                'location_found' => 'Help Desk Counter',
                'found_date' => '2026-04-14',
                'contact_country_code' => '+60',
                'contact_phone_number' => '127778888',
                'status' => LostFoundItem::STATUS_CLAIMED,
            ])
            ->assertRedirect(route('admin.lost-found.index'))
            ->assertSessionHas('status', 'Lost & found item updated successfully.');

        $item->refresh();

        $this->assertSame($originalImagePath, $item->image_path);
        $this->assertSame($originalThumbnailPath, $item->image_thumbnail_path);
        $this->assertSame('Black Wallet Updated', $item->title);
        $this->assertSame(LostFoundItem::STATUS_CLAIMED, $item->status);

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->delete("/admin/lost-found/{$item->id}", [
                '_token' => 'csrf-token',
            ])
            ->assertRedirect(route('admin.lost-found.index'))
            ->assertSessionHas('status', 'Lost & found item deleted successfully.');

        $this->assertDatabaseMissing('lost_found_items', [
            'id' => $item->id,
        ]);

        Storage::disk('public')->assertMissing($originalImagePath);
        Storage::disk('public')->assertMissing($originalThumbnailPath);
    }

    public function test_create_validation_requires_image_and_required_fields(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.lost-found.create'))
            ->post(route('admin.lost-found.store'), [
                'title' => '',
                'description' => '',
                'location_found' => '',
                'found_date' => '',
                'contact_country_code' => '+60',
                'contact_phone_number' => '',
                'status' => 'unknown',
            ])
            ->assertRedirect(route('admin.lost-found.create'))
            ->assertSessionHasErrors([
                'title',
                'description',
                'location_found',
                'found_date',
                'contact_phone_number',
                'status',
                'image',
            ]);
    }

    public function test_public_found_items_cache_is_invalidated_after_admin_create_update_and_delete(): void
    {
        Storage::fake('public');

        $admin = $this->admin();

        $this->getJson('/api/found-items')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->post('/admin/lost-found', [
                '_token' => 'csrf-token',
                'title' => 'Blue Phone',
                'description' => 'Found beside the DJ booth.',
                'location_found' => 'DJ Booth',
                'found_date' => '2026-04-11',
                'contact_country_code' => '+60',
                'contact_phone_number' => '129990000',
                'status' => LostFoundItem::STATUS_AVAILABLE,
                'image' => UploadedFile::fake()->image('phone.jpg', 1800, 1200),
            ])
            ->assertRedirect(route('admin.lost-found.index'));

        $item = LostFoundItem::query()->firstOrFail();

        $this->getJson('/api/found-items')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Blue Phone');

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->put("/admin/lost-found/{$item->id}", [
                '_token' => 'csrf-token',
                'lost_found_item_id' => (string) $item->id,
                'title' => 'Blue Phone',
                'description' => 'Found beside the DJ booth.',
                'location_found' => 'DJ Booth',
                'found_date' => '2026-04-11',
                'contact_country_code' => '+60',
                'contact_phone_number' => '129990000',
                'status' => LostFoundItem::STATUS_CLAIMED,
            ])
            ->assertRedirect(route('admin.lost-found.index'));

        $this->getJson('/api/found-items')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->put("/admin/lost-found/{$item->id}", [
                '_token' => 'csrf-token',
                'lost_found_item_id' => (string) $item->id,
                'title' => 'Blue Phone',
                'description' => 'Found beside the DJ booth.',
                'location_found' => 'DJ Booth',
                'found_date' => '2026-04-11',
                'contact_country_code' => '+60',
                'contact_phone_number' => '129990000',
                'status' => LostFoundItem::STATUS_AVAILABLE,
            ])
            ->assertRedirect(route('admin.lost-found.index'));

        $this->getJson('/api/found-items')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->actingAs($admin, 'admin')
            ->withSession(['_token' => 'csrf-token'])
            ->delete("/admin/lost-found/{$item->id}", [
                '_token' => 'csrf-token',
            ])
            ->assertRedirect(route('admin.lost-found.index'));

        $this->getJson('/api/found-items')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    private function admin(): Admin
    {
        return Admin::query()->firstOrFail();
    }
}
