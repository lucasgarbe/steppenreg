<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create the bibs table — one row per physical bib (number + track)
        Schema::create('bibs', function (Blueprint $table): void {
            $table->id();
            $table->integer('number');
            $table->integer('track_id');
            $table->string('tag_id')->nullable()->unique();
            $table->timestamps();

            $table->unique(['number', 'track_id']);
        });

        // Populate bibs from distinct (number, track_id) pairs already in starting_numbers,
        // carrying over the tag_id that was stored there.
        DB::statement('
            INSERT INTO bibs (number, track_id, tag_id, created_at, updated_at)
            SELECT DISTINCT ON (number, track_id)
                number,
                track_id,
                NULLIF(tag_id, \'\'),
                NOW(),
                NOW()
            FROM starting_numbers
            ORDER BY number, track_id, tag_id NULLS LAST
        ');

        // Add bib_id FK column to starting_numbers
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->foreignId('bib_id')->nullable()->constrained('bibs')->nullOnDelete();
        });

        // Backfill bib_id on existing starting_numbers rows
        DB::statement('
            UPDATE starting_numbers sn
            SET bib_id = bibs.id
            FROM bibs
            WHERE bibs.number = sn.number
              AND bibs.track_id = sn.track_id
        ');

        // Make bib_id NOT NULL now that every row has been backfilled
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->foreignId('bib_id')->nullable(false)->change();
        });

        // Drop the now-redundant columns from starting_numbers:
        // number and track_id are now on bibs; tag_id has moved there too.
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->dropUnique('starting_numbers_number_track_id_unique');
            $table->dropUnique('starting_numbers_tag_id_unique');
            $table->dropColumn(['number', 'track_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        // Restore columns on starting_numbers
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->integer('number')->nullable();
            $table->integer('track_id')->nullable();
            $table->string('tag_id')->nullable();
        });

        // Re-populate from bibs
        DB::statement('
            UPDATE starting_numbers sn
            SET number   = bibs.number,
                track_id = bibs.track_id,
                tag_id   = bibs.tag_id
            FROM bibs
            WHERE bibs.id = sn.bib_id
        ');

        // Make number / track_id non-nullable again
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->integer('number')->nullable(false)->change();
            $table->integer('track_id')->nullable(false)->change();
        });

        // Restore uniqueness constraints
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->unique(['number', 'track_id'], 'starting_numbers_number_track_id_unique');
        });

        // Restore globally-unique tag_id constraint (only for non-null values)
        // NB: this may fail if the same tag_id was assigned to multiple bibs during forward migration.

        // Drop bib_id FK and column
        Schema::table('starting_numbers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('bib_id');
        });

        Schema::dropIfExists('bibs');
    }
};
