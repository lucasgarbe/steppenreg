<?php

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
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('max_members')->nullable()->change();
        });

        // Convert all existing teams to unlimited capacity (NULL)
        DB::table('teams')->update(['max_members' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all NULL values to 5 before making NOT NULL again
        DB::table('teams')->whereNull('max_members')->update(['max_members' => 5]);

        Schema::table('teams', function (Blueprint $table) {
            $table->integer('max_members')->default(5)->nullable(false)->change();
        });
    }
};
