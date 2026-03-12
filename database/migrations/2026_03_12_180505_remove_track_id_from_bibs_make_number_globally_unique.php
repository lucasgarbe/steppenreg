<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes bibs globally unique by number (not per-track).
     * Consolidates bibs with the same number across different tracks into one bib.
     */
    public function up(): void
    {
        // Step 1: Consolidate duplicate bibs (same number, different tracks)
        // For each number, pick one "primary" bib and update all references
        $duplicateNumbers = DB::select('
            SELECT number
            FROM bibs
            GROUP BY number
            HAVING COUNT(*) > 1
        ');

        foreach ($duplicateNumbers as $row) {
            $number = $row->number;

            // Get all bibs with this number, ordered by: has tag_id first, then lowest ID
            $bibs = DB::select('
                SELECT id, tag_id
                FROM bibs
                WHERE number = ?
                ORDER BY (tag_id IS NOT NULL AND tag_id != \'\') DESC, id ASC
            ', [$number]);

            if (count($bibs) < 2) {
                continue;
            }

            // The first one is our "winner" (has tag_id or lowest ID)
            $primaryBibId = $bibs[0]->id;
            $primaryTagId = $bibs[0]->tag_id;

            // Update all starting_numbers pointing to the other bibs to point to the primary
            $otherBibIds = array_slice(array_map(fn ($b) => $b->id, $bibs), 1);

            DB::table('starting_numbers')
                ->whereIn('bib_id', $otherBibIds)
                ->update(['bib_id' => $primaryBibId]);

            // If the primary bib doesn't have a tag but one of the others does, copy it
            if (empty($primaryTagId)) {
                foreach ($bibs as $bib) {
                    if (! empty($bib->tag_id)) {
                        DB::table('bibs')
                            ->where('id', $primaryBibId)
                            ->update(['tag_id' => $bib->tag_id]);
                        break;
                    }
                }
            }

            // Delete the duplicate bibs
            DB::table('bibs')->whereIn('id', $otherBibIds)->delete();
        }

        // Step 2: Drop the old unique constraint on (number, track_id)
        Schema::table('bibs', function (Blueprint $table) {
            $table->dropUnique(['number', 'track_id']);
        });

        // Step 3: Add unique constraint on just number
        Schema::table('bibs', function (Blueprint $table) {
            $table->unique('number');
        });

        // Step 4: Drop track_id column (no longer needed)
        Schema::table('bibs', function (Blueprint $table) {
            $table->dropColumn('track_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add track_id column
        Schema::table('bibs', function (Blueprint $table) {
            $table->integer('track_id')->nullable();
        });

        // Backfill track_id from the registration's track via starting_numbers
        DB::statement('
            UPDATE bibs
            SET track_id = registrations.track_id
            FROM starting_numbers
            JOIN registrations ON registrations.id = starting_numbers.registration_id
            WHERE bibs.id = starting_numbers.bib_id
        ');

        // Make track_id non-nullable
        Schema::table('bibs', function (Blueprint $table) {
            $table->integer('track_id')->nullable(false)->change();
        });

        // Drop the global unique constraint
        Schema::table('bibs', function (Blueprint $table) {
            $table->dropUnique(['number']);
        });

        // Restore the per-track unique constraint
        Schema::table('bibs', function (Blueprint $table) {
            $table->unique(['number', 'track_id']);
        });
    }
};
