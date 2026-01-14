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
        Schema::table('game_scores', function (Blueprint $table) {
            $table->unsignedSmallInteger('team_row_2mw_score')->nullable()->after('team_col_score');
            $table->unsignedSmallInteger('team_col_2mw_score')->nullable()->after('team_row_2mw_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_scores', function (Blueprint $table) {
            $table->dropColumn(['team_row_2mw_score', 'team_col_2mw_score']);
        });
    }
};
