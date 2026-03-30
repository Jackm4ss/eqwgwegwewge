<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scanner_gates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $configuredGates = array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            config('scanner.posts', ['Gate A', 'Gate B']),
        )));

        $seedGates = $configuredGates !== [] ? $configuredGates : ['Gate A'];
        $timestamp = now();

        DB::table('scanner_gates')->insert(array_map(
            static fn (string $name, int $index): array => [
                'name' => $name,
                'sort_order' => $index,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            $seedGates,
            array_keys($seedGates),
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('scanner_gates');
    }
};
