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
        Schema::create('winners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('square_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('quarter');
            $table->unsignedTinyInteger('row_score');
            $table->unsignedTinyInteger('col_score');
            $table->unsignedInteger('payout_amount');      // cents

            $table->boolean('is_reverse')->default(false);
            $table->boolean('is_touching')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['board_id', 'quarter']);
            $table->index(['user_id', 'board_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('winners');
    }
};
