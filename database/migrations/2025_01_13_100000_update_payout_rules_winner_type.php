<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new winner_type column
        Schema::table('payout_rules', function (Blueprint $table): void {
            $table->string('winner_type')->default('primary')->after('amount');
        });

        // Migrate existing data
        DB::table('payout_rules')
            ->where('reverse_winner', true)
            ->update(['winner_type' => 'reverse']);

        DB::table('payout_rules')
            ->where('touching_squares', true)
            ->update(['winner_type' => 'touching']);

        // Update Q4 to final for consistency
        DB::table('payout_rules')
            ->where('quarter', 'Q4')
            ->update(['quarter' => 'final']);

        // Drop old columns and constraint
        Schema::table('payout_rules', function (Blueprint $table): void {
            $table->dropUnique('payout_unique');
            $table->dropColumn(['reverse_winner', 'touching_squares']);
        });

        // Add new unique constraint
        Schema::table('payout_rules', function (Blueprint $table): void {
            $table->unique(['board_id', 'quarter', 'winner_type'], 'payout_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payout_rules', function (Blueprint $table): void {
            $table->dropUnique('payout_unique');
            $table->boolean('reverse_winner')->default(false);
            $table->boolean('touching_squares')->default(false);
        });

        // Migrate data back
        DB::table('payout_rules')
            ->where('winner_type', 'reverse')
            ->update(['reverse_winner' => true]);

        DB::table('payout_rules')
            ->where('winner_type', 'touching')
            ->update(['touching_squares' => true]);

        Schema::table('payout_rules', function (Blueprint $table): void {
            $table->dropColumn('winner_type');
            $table->unique(['board_id', 'quarter', 'reverse_winner', 'touching_squares'], 'payout_unique');
        });
    }
};
