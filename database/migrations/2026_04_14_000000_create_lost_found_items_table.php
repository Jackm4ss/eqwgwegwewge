<?php

use App\Models\LostFoundItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_items', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->text('description');
            $table->string('location_found', 255);
            $table->date('found_date');
            $table->text('contact_info');
            $table->string('status', 20)->default(LostFoundItem::STATUS_AVAILABLE);
            $table->string('image_path', 255);
            $table->string('image_thumbnail_path', 255);
            $table->timestamps();

            $table->index('status');
            $table->index('found_date');
            $table->index(['status', 'found_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_items');
    }
};
