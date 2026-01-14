<?php

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
        Schema::create('boards', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('team_row');                    // e.g., "Chiefs"
            $table->string('team_col');                    // e.g., "Eagles"
            $table->timestamp('game_date')->nullable();

            $table->unsignedInteger('price_per_square');   // cents
            $table->unsignedTinyInteger('max_squares_per_user')->default(4);
            $table->boolean('is_public')->default(false);
            $table->string('status')->default('draft');    // draft, open, locked, completed

            $table->text('row_numbers')->nullable();        // [0-9] shuffled (JSON stored as text for older MySQL)
            $table->text('col_numbers')->nullable();
            $table->boolean('numbers_revealed')->default(false);

            $table->text('payment_instructions')->nullable();

            $table->timestamps();

            $table->index(['is_public', 'status']);
            $table->index(['owner_id', 'status']);
            $table->index('uuid');
            $table->index('game_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
