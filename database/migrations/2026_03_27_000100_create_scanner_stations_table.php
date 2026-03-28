<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scanner_stations', function (Blueprint $table) {
            $table->id();
            $table->uuid('station_id')->unique();
            $table->string('scanner_id')->unique();
            $table->string('scanner_name');
            $table->string('gate_id');
            $table->string('gate_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scanner_stations');
    }
};
