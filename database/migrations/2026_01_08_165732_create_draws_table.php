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
        Schema::create('draws', function (Blueprint $table) {
            $table->id();
            $table->integer('track_id');
            $table->foreignId('executed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('executed_at');
            $table->integer('total_registrations')->default(0);
            $table->integer('total_drawn')->default(0);
            $table->integer('total_not_drawn')->default(0);
            $table->integer('available_spots')->default(0);
            $table->json('config')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('track_id');
            $table->index('track_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draws');
    }
};
