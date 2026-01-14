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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('player')->after('email');
            $table->boolean('is_approved_creator')->default(false)->after('role');
            $table->string('phone')->nullable()->after('is_approved_creator');

            $table->index('role');
            $table->index('is_approved_creator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_approved_creator']);

            $table->dropColumn(['role', 'is_approved_creator', 'phone']);
        });
    }
};
