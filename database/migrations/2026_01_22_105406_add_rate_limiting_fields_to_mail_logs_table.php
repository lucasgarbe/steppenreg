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
        Schema::table('mail_logs', function (Blueprint $table) {
            $table->unsignedInteger('attempt_count')->default(0)->after('status');
            $table->timestamp('last_rate_limited_at')->nullable()->after('sent_at');
            $table->unsignedInteger('rate_limit_count')->default(0)->after('last_rate_limited_at');
            $table->json('metadata')->nullable()->after('template_variables');

            $table->index(['last_rate_limited_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mail_logs', function (Blueprint $table) {
            $table->dropIndex(['last_rate_limited_at', 'status']);
            $table->dropColumn(['attempt_count', 'last_rate_limited_at', 'rate_limit_count', 'metadata']);
        });
    }
};
