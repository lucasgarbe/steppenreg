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
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('event_id')->after('id')->nullable()->constrained()->cascadeOnDelete();
            $table->index(['event_id', 'track_id']);
            $table->index(['event_id', 'draw_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropIndex(['event_id', 'track_id']);
            $table->dropIndex(['event_id', 'draw_status']);
            $table->dropColumn('event_id');
        });
    }
};
