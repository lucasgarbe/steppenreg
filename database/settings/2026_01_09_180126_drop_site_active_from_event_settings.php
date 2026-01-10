<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove site_active from the settings payload
        DB::table('settings')
            ->where('group', 'event')
            ->where('name', 'site_active')
            ->delete();
    }

    public function down(): void
    {
        // Restore site_active to the settings payload
        DB::table('settings')->insert([
            'group' => 'event',
            'name' => 'site_active',
            'locked' => false,
            'payload' => json_encode(true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
