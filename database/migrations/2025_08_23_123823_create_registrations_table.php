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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->integer('track_id')->nullable();
            $table->integer('age');
            $table->enum('gender', ['flinta', 'all_gender'])->nullable();
            $table->boolean('payed')->default(false);
            $table->boolean('starting')->default(false);
            $table->enum('draw_status', ['not_drawn', 'drawn', 'waitlist'])->default('not_drawn');
            $table->timestamp('drawn_at')->nullable();
            $table->time('finish_time')->nullable();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['email']);
            $table->index(['payed']);
            $table->index(['starting']);
            $table->index(['track_id']);
            $table->index(['draw_status']);
            $table->index(['gender']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};