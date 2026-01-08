<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert any 'waitlist' status to 'not_drawn'
        DB::table('registrations')
            ->where('draw_status', 'waitlist')
            ->update(['draw_status' => 'not_drawn']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration - no rollback needed
    }
};
