<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $registrations = DB::table('registrations')
            ->whereNotNull('draw_status')
            ->get();

        foreach ($registrations as $registration) {
            DB::table('draw_statuses')->updateOrInsert(
                ['registration_id' => $registration->id],
                [
                    'status' => $registration->draw_status === 'drawn' ? 'drawn' : 'not_drawn',
                    'drawn_at' => $registration->drawn_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='registrations'");
            foreach ($indexes as $index) {
                if (str_contains($index->name, 'draw_status') || str_contains($index->name, 'draw_id')) {
                    DB::statement("DROP INDEX IF EXISTS \"{$index->name}\"");
                }
            }
        } else {
            DB::statement('DROP INDEX IF EXISTS registrations_draw_id_draw_status_index');
            DB::statement('DROP INDEX IF EXISTS registrations_draw_status_index');
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['draw_id']);
            $table->dropColumn(['draw_status', 'drawn_at', 'draw_id']);
        });

        Schema::dropIfExists('draws');
    }

    public function down(): void
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

        Schema::table('registrations', function (Blueprint $table) {
            $table->enum('draw_status', ['not_drawn', 'drawn', 'waitlist'])->default('not_drawn')->after('starting');
            $table->timestamp('drawn_at')->nullable()->after('draw_status');
            $table->foreignId('draw_id')->nullable()->after('track_id')->constrained('draws')->onDelete('set null');

            $table->index(['draw_status']);
            $table->index(['draw_id', 'draw_status']);
        });

        $drawStatuses = DB::table('draw_statuses')->get();

        foreach ($drawStatuses as $drawStatus) {
            DB::table('registrations')
                ->where('id', $drawStatus->registration_id)
                ->update([
                    'draw_status' => $drawStatus->status,
                    'drawn_at' => $drawStatus->drawn_at,
                ]);
        }

        Schema::dropIfExists('draw_statuses');
    }
};
