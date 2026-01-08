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
            $table->foreignId('draw_id')->nullable()->after('track_id')
                ->constrained('draws')->onDelete('set null');
            
            $table->index(['draw_id', 'draw_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['draw_id']);
            $table->dropIndex(['draw_id', 'draw_status']);
            $table->dropColumn('draw_id');
        });
    }
};
