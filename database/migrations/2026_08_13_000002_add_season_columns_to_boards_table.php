<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table): void {
            $table->foreignId('season_id')->nullable()->after('owner_id')
                ->constrained('seasons')->cascadeOnDelete();

            // Null on the roster board, 1..total_weeks on each weekly board.
            $table->unsignedTinyInteger('week_number')->nullable()->after('season_id');

            // The roster board holds who owns which square for the whole season.
            $table->boolean('is_roster')->default(false)->after('week_number');

            $table->unique(['season_id', 'week_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table): void {
            $table->dropUnique(['season_id', 'week_number']);
            $table->dropForeign(['season_id']);
            $table->dropColumn(['season_id', 'week_number', 'is_roster']);
        });
    }
};
