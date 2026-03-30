<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_links', function (Blueprint $table) {
            $table->string('slug', 120)->nullable();
            $table->unsignedBigInteger('visit_count')->default(0);
            $table->timestamp('last_visited_at')->nullable();
        });

        DB::table('campaign_links')
            ->select(['id', 'slug'])
            ->orderBy('id')
            ->get()
            ->each(function (object $campaignLink): void {
                if (filled($campaignLink->slug)) {
                    return;
                }

                DB::table('campaign_links')
                    ->where('id', $campaignLink->id)
                    ->update([
                        'slug' => 'legacy-'.$campaignLink->id,
                    ]);
            });

        Schema::table('campaign_links', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_links', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'visit_count', 'last_visited_at']);
        });
    }
};
