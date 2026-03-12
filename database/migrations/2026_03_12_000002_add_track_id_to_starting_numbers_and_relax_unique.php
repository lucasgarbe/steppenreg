<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add track_id with a temporary default so the column can be added to existing rows
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->integer('track_id')->default(0)->after('number');
        });

        // Backfill track_id from the linked registration
        DB::statement('
            UPDATE starting_numbers
            SET track_id = registrations.track_id
            FROM registrations
            WHERE starting_numbers.registration_id = registrations.id
        ');

        // Remove the temporary default — track_id must always be supplied explicitly
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->integer('track_id')->default(null)->change();
        });

        // Drop the old global unique constraint on number alone
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->dropUnique('starting_numbers_number_unique');
        });

        // Add composite unique: same number may appear once per track
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->unique(['number', 'track_id'], 'starting_numbers_number_track_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->dropUnique('starting_numbers_number_track_id_unique');
        });

        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->unique('number', 'starting_numbers_number_unique');
        });

        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->dropColumn('track_id');
        });
    }
};
