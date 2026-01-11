<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table to properly remove columns with indexes
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->recreateTableForSQLite();
        } else {
            // For other databases, use the standard approach
            Schema::table('registrations', function (Blueprint $table) {
                // Drop indexes first
                $table->dropIndex(['waitlist_token']);
                $table->dropIndex(['waitlist_token_expires_at']);
                $table->dropIndex(['waitlist_registered_at']);
                $table->dropIndex(['withdraw_token']);
                $table->dropIndex(['withdraw_token_expires_at']);
                $table->dropIndex(['withdrawn_at']);
                $table->dropIndex(['promoted_from_waitlist_at']);
                $table->dropIndex(['is_withdrawn']);

                // Drop columns
                $table->dropColumn([
                    'waitlist_token',
                    'waitlist_token_expires_at',
                    'waitlist_registered_at',
                    'withdraw_token',
                    'withdraw_token_expires_at',
                    'withdrawn_at',
                    'original_draw_status',
                    'withdrawal_reason',
                    'promoted_from_waitlist_at',
                    'is_withdrawn',
                ]);
            });
        }
    }

    private function recreateTableForSQLite(): void
    {
        // Create temporary table with clean structure
        Schema::create('registrations_temp', function (Blueprint $table) {
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
            $table->integer('starting_number')->nullable()->unique();
            $table->time('finish_time')->nullable();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('promoted_from_waitlist_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['email']);
            $table->index(['payed']);
            $table->index(['starting']);
            $table->index(['track_id']);
            $table->index(['draw_status']);
            $table->index(['gender']);
            $table->index(['starting_number']);
            $table->index(['promoted_from_waitlist_at']);
        });

        // Copy data from old table to new table (only clean columns)
        DB::statement('
            INSERT INTO registrations_temp (
                id, name, email, track_id, age, gender, payed, starting, 
                draw_status, drawn_at, starting_number, finish_time, team_id, 
                notes, promoted_from_waitlist_at, created_at, updated_at, deleted_at
            )
            SELECT 
                id, name, email, track_id, age, gender, payed, starting, 
                draw_status, drawn_at, starting_number, finish_time, team_id, 
                notes, promoted_from_waitlist_at, created_at, updated_at, deleted_at
            FROM registrations
        ');

        // Drop old table and rename new one
        Schema::dropIfExists('registrations');
        Schema::rename('registrations_temp', 'registrations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Re-add all the legacy columns for rollback
            $table->string('waitlist_token', 64)->nullable()->unique();
            $table->timestamp('waitlist_token_expires_at')->nullable();
            $table->timestamp('waitlist_registered_at')->nullable();
            $table->string('withdraw_token', 64)->nullable()->unique();
            $table->timestamp('withdraw_token_expires_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('original_draw_status', 20)->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->timestamp('promoted_from_waitlist_at')->nullable();
            $table->boolean('is_withdrawn')->default(false);

            // Re-add indexes
            $table->index(['waitlist_token']);
            $table->index(['waitlist_token_expires_at']);
            $table->index(['waitlist_registered_at']);
            $table->index(['withdraw_token']);
            $table->index(['withdraw_token_expires_at']);
            $table->index(['withdrawn_at']);
            $table->index(['promoted_from_waitlist_at']);
            $table->index(['is_withdrawn']);
        });
    }
};
