<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate any existing starting_number values from registrations into the new table
        if (Schema::hasColumn('registrations', 'starting_number')) {
            DB::table('registrations')
                ->whereNotNull('starting_number')
                ->orderBy('id')
                ->each(function (object $registration) {
                    DB::table('starting_numbers')->insertOrIgnore([
                        'registration_id' => $registration->id,
                        'number' => $registration->starting_number,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });

            Schema::table('registrations', function (Blueprint $table) {
                $table->dropColumn('starting_number');
            });
        }
    }

    public function down(): void
    {
        // Restore the column and copy data back
        Schema::table('registrations', function (Blueprint $table) {
            $table->integer('starting_number')->nullable()->unique();
        });

        DB::table('starting_numbers')
            ->orderBy('registration_id')
            ->each(function (object $sn) {
                DB::table('registrations')
                    ->where('id', $sn->registration_id)
                    ->update(['starting_number' => $sn->number]);
            });
    }
};
