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
            $table->enum('draw_status', ['not_drawn', 'drawn', 'waitlist'])
                  ->default('not_drawn')
                  ->after('starting');
            $table->timestamp('drawn_at')->nullable()->after('draw_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['draw_status', 'drawn_at']);
        });
    }
};
