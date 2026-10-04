<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('paid')->default(false);
            $table->string('paid_via')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='registrations'");
            foreach ($indexes as $index) {
                if (str_contains($index->name, 'payed')) {
                    DB::statement("DROP INDEX IF EXISTS \"{$index->name}\"");
                }
            }
        } else {
            DB::statement('DROP INDEX IF EXISTS registrations_payed_index');
        }

        $registrations = DB::table('registrations')->where('payed', true)->get();

        foreach ($registrations as $registration) {
            DB::table('payment_statuses')->insert([
                'registration_id' => $registration->id,
                'paid' => true,
                'paid_via' => 'legacy',
                'paid_at' => $registration->created_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('payed');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->boolean('payed')->default(false);
            $table->index(['payed']);
        });

        $paymentStatuses = DB::table('payment_statuses')->where('paid', true)->get();

        foreach ($paymentStatuses as $paymentStatus) {
            DB::table('registrations')
                ->where('id', $paymentStatus->registration_id)
                ->update(['payed' => true]);
        }

        Schema::dropIfExists('payment_statuses');
    }
};
