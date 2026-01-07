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
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('capacity');
            $table->enum('status', ['draft', 'open', 'closed', 'full'])->default('draft');
            $table->integer('sort_order')->default(0);
            
            // Flexible settings
            $table->json('settings')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['event_id', 'slug']);
            $table->index(['event_id', 'status']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
