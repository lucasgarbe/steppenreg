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
        Schema::table('registrations', function (Blueprint $table) {
            $table->timestamp('waitlist_token_expires_at')->nullable()->after('waitlist_token');
            $table->timestamp('withdraw_token_expires_at')->nullable()->after('withdraw_token');
            $table->timestamp('promoted_from_waitlist_at')->nullable()->after('withdrawal_reason');
            $table->boolean('is_withdrawn')->default(false)->after('promoted_from_waitlist_at');
            
            $table->index(['waitlist_token_expires_at']);
            $table->index(['withdraw_token_expires_at']);
            $table->index(['promoted_from_waitlist_at']);
            $table->index(['is_withdrawn']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['waitlist_token_expires_at']);
            $table->dropIndex(['withdraw_token_expires_at']);
            $table->dropIndex(['promoted_from_waitlist_at']);
            $table->dropIndex(['is_withdrawn']);
            
            $table->dropColumn([
                'waitlist_token_expires_at',
                'withdraw_token_expires_at',
                'promoted_from_waitlist_at',
                'is_withdrawn'
            ]);
        });
    }
};
