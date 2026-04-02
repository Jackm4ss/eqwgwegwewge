<?php

use App\Models\PublicReport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_reports')) {
            return;
        }

        Schema::table('public_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('public_reports', 'action_status')) {
                $table->string('action_status', 30)->default(PublicReport::ACTION_STATUS_PENDING);
            }

            if (! Schema::hasColumn('public_reports', 'admin_note')) {
                $table->text('admin_note')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('public_reports')) {
            return;
        }

        Schema::table('public_reports', function (Blueprint $table) {
            if (Schema::hasColumn('public_reports', 'admin_note')) {
                $table->dropColumn('admin_note');
            }

            if (Schema::hasColumn('public_reports', 'action_status')) {
                $table->dropColumn('action_status');
            }
        });
    }
};
