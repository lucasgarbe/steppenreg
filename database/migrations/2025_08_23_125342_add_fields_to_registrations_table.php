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
            $table->string('name')->after('id');
            $table->string('email')->after('name');
            $table->integer('age')->after('email');
            $table->boolean('payed')->default(false)->after('age');
            $table->boolean('starting')->default(false)->after('payed');
            $table->time('finish_time')->nullable()->after('starting');
            $table->text('notes')->nullable()->after('finish_time');

            $table->index(['email']);
            $table->index(['payed']);
            $table->index(['starting']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['payed']);
            $table->dropIndex(['starting']);
            
            $table->dropColumn([
                'name',
                'email',
                'age',
                'payed',
                'starting',
                'finish_time',
                'notes',
            ]);
        });
    }
};
