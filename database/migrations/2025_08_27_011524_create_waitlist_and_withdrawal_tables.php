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
        // Create waitlist_entries table
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('registered_at');
            $table->integer('position')->nullable()->comment('Cached waitlist position');
            $table->string('original_draw_status', 20)->default('not_drawn');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['token']);
            $table->index(['registration_id', 'registered_at']);
            $table->index(['registered_at']);
            $table->index(['position']);
        });

        // Create withdrawal_requests table
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->boolean('is_withdrawn')->default(false);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['token']);
            $table->index(['registration_id']);
            $table->index(['withdrawn_at']);
            $table->index(['is_withdrawn']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('waitlist_entries');
    }
};
