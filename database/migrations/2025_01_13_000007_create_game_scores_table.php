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
        Schema::create('game_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();

            $table->string('quarter');
            $table->unsignedSmallInteger('team_row_score')->default(0);
            $table->unsignedSmallInteger('team_col_score')->default(0);
            $table->boolean('is_final')->default(false);
            $table->string('source')->default('manual');
            $table->string('external_game_id')->nullable();

            $table->timestamps();

            $table->unique(['board_id', 'quarter']);
            $table->index(['board_id', 'is_final']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_scores');
    }
};
