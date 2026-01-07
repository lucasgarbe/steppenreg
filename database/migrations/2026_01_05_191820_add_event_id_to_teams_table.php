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
            // Drop old unique constraint
            $table->dropUnique(['name', 'track_id']);
            
            // Add event_id
            $table->foreignId('event_id')->after('id')->nullable()->constrained()->cascadeOnDelete();
            
            // Add new unique constraint that includes event_id
            $table->unique(['event_id', 'name', 'track_id']);
            $table->index(['event_id', 'track_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'name', 'track_id']);
            $table->dropIndex(['event_id', 'track_id']);
            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');
            
            // Restore old unique constraint
            $table->unique(['name', 'track_id']);
        });
    }
};
