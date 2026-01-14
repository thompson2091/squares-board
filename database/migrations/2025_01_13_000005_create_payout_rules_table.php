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
        Schema::create('payout_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();

            $table->string('quarter');                     // Q1, Q2, Q3, Q4, final
            $table->string('payout_type');                 // percentage, fixed
            $table->unsignedInteger('amount');             // percentage (0-10000) or cents
            $table->boolean('reverse_winner')->default(false);
            $table->boolean('touching_squares')->default(false);

            $table->timestamps();

            $table->unique(['board_id', 'quarter', 'reverse_winner', 'touching_squares'], 'payout_unique');
            $table->index('board_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_rules');
    }
};
