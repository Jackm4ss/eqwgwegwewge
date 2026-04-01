<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_reports', function (Blueprint $table) {
            $table->id();
            $table->string('case_id')->unique();
            $table->string('report_type', 50);
            $table->string('case_prefix', 10);
            $table->unsignedInteger('case_sequence');
            $table->string('name', 120);
            $table->string('email', 120)->nullable();
            $table->string('phone', 30);
            $table->string('identity_number', 80)->nullable();
            $table->date('incident_date');
            $table->time('incident_time');
            $table->text('chronology');
            $table->string('staff_name', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('reported_at');
            $table->timestamps();

            $table->index(['report_type', 'case_sequence']);
            $table->index(['incident_date', 'incident_time']);
            $table->index('reported_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_reports');
    }
};
