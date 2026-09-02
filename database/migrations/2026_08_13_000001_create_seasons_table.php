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
        Schema::create('seasons', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->nullable()->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->unsignedTinyInteger('total_weeks')->default(18);
            $table->string('number_draw_mode')->default('upfront');  // upfront, manual
            $table->string('status')->default('draft');              // draft, open, active, completed

            $table->timestamps();

            $table->index('uuid');
            $table->index(['owner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
