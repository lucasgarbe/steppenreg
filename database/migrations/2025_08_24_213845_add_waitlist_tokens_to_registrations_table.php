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
            $table->string('waitlist_token', 64)->nullable()->unique()->after('notes');
            $table->string('withdraw_token', 64)->nullable()->unique()->after('waitlist_token');
            $table->timestamp('waitlist_registered_at')->nullable()->after('withdraw_token');
            $table->timestamp('withdrawn_at')->nullable()->after('waitlist_registered_at');
            $table->string('original_draw_status', 20)->nullable()->after('withdrawn_at');
            $table->text('withdrawal_reason')->nullable()->after('original_draw_status');

            $table->index(['waitlist_token']);
            $table->index(['withdraw_token']);
            $table->index(['waitlist_registered_at']);
            $table->index(['withdrawn_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['waitlist_token']);
            $table->dropIndex(['withdraw_token']);
            $table->dropIndex(['waitlist_registered_at']);
            $table->dropIndex(['withdrawn_at']);

            $table->dropColumn([
                'waitlist_token',
                'withdraw_token',
                'waitlist_registered_at',
                'withdrawn_at',
                'original_draw_status',
                'withdrawal_reason',
            ]);
        });
    }
};
