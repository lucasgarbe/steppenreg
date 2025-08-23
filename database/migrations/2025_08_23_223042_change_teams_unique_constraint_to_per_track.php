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
        Schema::table('teams', function (Blueprint $table) {
            // Remove the old unique constraint on name
            $table->dropUnique(['name']);
            
            // Add a unique constraint on name + track_id combination
            $table->unique(['name', 'track_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Remove the composite unique constraint
            $table->dropUnique(['name', 'track_id']);
            
            // Add back the old unique constraint on name
            $table->unique('name');
        });
    }
};
