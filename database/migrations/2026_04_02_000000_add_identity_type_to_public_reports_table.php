<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_reports') || Schema::hasColumn('public_reports', 'identity_type')) {
            return;
        }

        Schema::table('public_reports', function (Blueprint $table) {
            $table->string('identity_type', 20)->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('public_reports') || ! Schema::hasColumn('public_reports', 'identity_type')) {
            return;
        }

        Schema::table('public_reports', function (Blueprint $table) {
            $table->dropColumn('identity_type');
        });
    }
};
