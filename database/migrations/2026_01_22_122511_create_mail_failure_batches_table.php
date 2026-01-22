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
        Schema::create('mail_failure_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('failure_count')->default(0);
            $table->json('template_breakdown')->nullable();
            $table->json('mail_log_ids')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamps();

            $table->index('started_at');
            $table->index('notification_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_failure_batches');
    }
};
