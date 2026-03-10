<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('track_starting_number_ranges', function (Blueprint $table) {
            $table->id();
            // References the track id from EventSettings JSON - not a DB FK
            $table->integer('track_id')->unique();
            $table->integer('range_start');
            $table->integer('range_end');
            $table->integer('overflow_start');
            $table->integer('overflow_end');
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('track_starting_number_ranges');
    }
};
