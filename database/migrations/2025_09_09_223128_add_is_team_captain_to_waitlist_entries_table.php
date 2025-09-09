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
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->boolean('is_team_captain')->default(false)->after('original_draw_status');
            $table->index('is_team_captain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropIndex(['is_team_captain']);
            $table->dropColumn('is_team_captain');
        });
    }
};
