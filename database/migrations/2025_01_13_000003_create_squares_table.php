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
        Schema::create('squares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedTinyInteger('row');            // 0-9
            $table->unsignedTinyInteger('col');            // 0-9

            $table->boolean('is_paid')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->unique(['board_id', 'row', 'col']);
            $table->index(['board_id', 'user_id']);
            $table->index(['board_id', 'is_paid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('squares');
    }
};
