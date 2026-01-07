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
        // Add event_id to waitlist_entries
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->foreignId('event_id')->after('id')->nullable()->constrained()->cascadeOnDelete();
            $table->index(['event_id', 'registered_at']);
        });

        // Add event_id to withdrawal_requests
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->foreignId('event_id')->after('id')->nullable()->constrained()->cascadeOnDelete();
            $table->index(['event_id', 'is_withdrawn']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropIndex(['event_id', 'registered_at']);
            $table->dropColumn('event_id');
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropIndex(['event_id', 'is_withdrawn']);
            $table->dropColumn('event_id');
        });
    }
};
