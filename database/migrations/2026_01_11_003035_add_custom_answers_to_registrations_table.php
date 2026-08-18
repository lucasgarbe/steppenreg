<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->jsonb('custom_answers')->nullable()->after('notes');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS registrations_custom_answers_gin_idx ON registrations USING GIN (custom_answers)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS registrations_custom_answers_gin_idx');
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('custom_answers');
        });
    }
};
