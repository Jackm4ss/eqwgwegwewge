<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_visits', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('source_group', 40)->default('direct')->index();
            $table->string('traffic_source', 120)->nullable()->index();
            $table->string('traffic_source_detail', 120)->nullable();
            $table->string('traffic_medium', 40)->nullable();
            $table->string('traffic_campaign', 120)->nullable();
            $table->string('traffic_referrer_host', 120)->nullable();
            $table->string('traffic_landing_path', 500)->nullable();
            $table->date('visit_date')->index();
            $table->timestamp('visited_at')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_visits');
    }
};
