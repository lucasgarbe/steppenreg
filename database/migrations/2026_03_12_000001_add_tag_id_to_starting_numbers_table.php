<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->string('tag_id')->nullable()->unique()->after('number');
        });
    }

    public function down(): void
    {
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->dropColumn('tag_id');
        });
    }
};
