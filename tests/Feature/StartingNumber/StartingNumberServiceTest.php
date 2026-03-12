<?php

namespace Tests\Feature\StartingNumber;

use App\Domain\StartingNumber\Exceptions\NoAvailableNumberException;
use App\Domain\StartingNumber\Models\Bib;
use App\Domain\StartingNumber\Models\StartingNumber;
use App\Domain\StartingNumber\Models\TrackStartingNumberRange;
use App\Domain\StartingNumber\Services\StartingNumberService;
use App\Models\Registration;
use App\Settings\EventSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartingNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private StartingNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StartingNumberService::class);

        // Configure two tracks via EventSettings::fake() (does not persist to DB)
        $settings = EventSettings::fake([
            'event_name' => 'Test Event',
            'tracks' => [
                ['id' => 1, 'name' => 'Track A', 'distance' => 10.0],
                ['id' => 2, 'name' => 'Track B', 'distance' => 20.0],
            ],
        ]);
        app()->instance(EventSettings::class, $settings);

        // Configure number ranges for both tracks (small ranges for easy testing)
        TrackStartingNumberRange::create([
            'track_id' => 1,
            'range_start' => 1,
            'range_end' => 3,
            'overflow_start' => 4,
            'overflow_end' => 5,
        ]);

        TrackStartingNumberRange::create([
            'track_id' => 2,
            'range_start' => 1,
            'range_end' => 3,
            'overflow_start' => 4,
            'overflow_end' => 5,
        ]);
    }

    // -------------------------------------------------------------------------
    // Per-track uniqueness
    // -------------------------------------------------------------------------

    public function test_same_number_can_be_assigned_on_different_tracks(): void
    {
        $regA = Registration::factory()->create(['track_id' => 1]);
        $regB = Registration::factory()->create(['track_id' => 2]);

        $snA = $this->service->assignAndSave($regA);
        $snB = $this->service->assignAndSave($regB);

        $this->assertNotNull($snA);
        $this->assertNotNull($snB);

        // Both tracks start at 1, so both get number 1
        $this->assertEquals(1, $snA->number);
        $this->assertEquals(1, $snB->number);

        // Both share the same global bib (bibs are no longer track-specific)
        $this->assertEquals($snA->bib_id, $snB->bib_id);
    }

    public function test_same_number_cannot_be_assigned_twice_on_same_track(): void
    {
        $reg1 = Registration::factory()->create(['track_id' => 1]);
        $reg2 = Registration::factory()->create(['track_id' => 1]);

        $sn1 = $this->service->assignAndSave($reg1);
        $sn2 = $this->service->assignAndSave($reg2);

        $this->assertNotNull($sn1);
        $this->assertNotNull($sn2);

        // Numbers must differ within the same track
        $this->assertNotEquals($sn1->number, $sn2->number);
        $this->assertEquals(1, $sn1->number);
        $this->assertEquals(2, $sn2->number);
    }

    // -------------------------------------------------------------------------
    // Range exhaustion
    // -------------------------------------------------------------------------

    public function test_throws_when_all_numbers_exhausted_for_track(): void
    {
        $this->expectException(NoAvailableNumberException::class);

        // Fill all 5 slots (3 main + 2 overflow)
        for ($i = 0; $i < 5; $i++) {
            $reg = Registration::factory()->create(['track_id' => 1]);
            $this->service->assignAndSave($reg);
        }

        // Sixth registration on the same track should throw
        $reg = Registration::factory()->create(['track_id' => 1]);
        $this->service->assignAndSave($reg);
    }

    public function test_exhausted_track_does_not_block_other_track(): void
    {
        // Fill track 1 completely
        for ($i = 0; $i < 5; $i++) {
            $reg = Registration::factory()->create(['track_id' => 1]);
            $this->service->assignAndSave($reg);
        }

        // Track 2 should still be assignable (starts from 1, shares global bib)
        $regB = Registration::factory()->create(['track_id' => 2]);
        $sn = $this->service->assignAndSave($regB);

        $this->assertNotNull($sn);
        $this->assertEquals(1, $sn->number); // Track 2 starts at 1, shares the global bib #1
    }

    // -------------------------------------------------------------------------
    // Soft-deleted registrations retire their bib
    // -------------------------------------------------------------------------

    public function test_number_of_soft_deleted_registration_is_retired(): void
    {
        $reg1 = Registration::factory()->create(['track_id' => 1]);
        $this->service->assignAndSave($reg1); // gets number 1

        // Soft-delete the registration — the starting_numbers row survives (no cascade),
        // so number 1 is still considered taken.
        $reg1->delete();

        $reg2 = Registration::factory()->create(['track_id' => 1]);
        $sn2 = $this->service->assignAndSave($reg2);

        // Number 1 is still taken (starting_numbers row exists for soft-deleted reg);
        // reg2 gets 2.
        $this->assertEquals(2, $sn2->number);
    }

    public function test_after_assignment_reset_numbering_starts_from_range_start(): void
    {
        // Assign numbers 1, 2, 3 to three participants on track 1
        $reg1 = Registration::factory()->create(['track_id' => 1]);
        $reg2 = Registration::factory()->create(['track_id' => 1]);
        $reg3 = Registration::factory()->create(['track_id' => 1]);

        $this->service->assignAndSave($reg1); // bib 1
        $this->service->assignAndSave($reg2); // bib 2
        $this->service->assignAndSave($reg3); // bib 3

        // Simulate the reset action: delete all starting_numbers rows, keep bibs
        StartingNumber::query()->delete();

        $this->assertEquals(3, Bib::count(), 'Bibs should survive the reset');
        $this->assertEquals(0, StartingNumber::count(), 'All assignments should be gone');

        // Assign a new participant — should get number 1, not 4
        $reg4 = Registration::factory()->create(['track_id' => 1]);
        $sn4 = $this->service->assignAndSave($reg4);

        $this->assertEquals(1, $sn4->number);
    }

    // -------------------------------------------------------------------------
    // countUsedInRange excludes soft-deleted registrations
    // -------------------------------------------------------------------------

    public function test_range_status_excludes_soft_deleted_registrations_from_capacity(): void
    {
        $reg1 = Registration::factory()->create(['track_id' => 1]);
        $this->service->assignAndSave($reg1); // number 1

        $reg2 = Registration::factory()->create(['track_id' => 1]);
        $this->service->assignAndSave($reg2); // number 2

        // Soft-delete reg1
        $reg1->delete();

        $status = $this->service->getRangeStatus(1);

        // Only reg2 is active; reg1 is soft-deleted and excluded from capacity count
        $this->assertEquals(1, $status['main']['used']);
    }

    // -------------------------------------------------------------------------
    // Bib identity and tag_id persistence
    // -------------------------------------------------------------------------

    public function test_bib_row_is_created_on_first_assignment(): void
    {
        $reg = Registration::factory()->create(['track_id' => 2]);
        $sn = $this->service->assignAndSave($reg);

        $this->assertNotNull($sn->bib);
        $this->assertEquals(1, $sn->bib->number);
    }

    public function test_tag_id_persists_across_participant_changes(): void
    {
        // First participant gets bib 1 on track 1
        $reg1 = Registration::factory()->create(['track_id' => 1]);
        $sn1 = $this->service->assignAndSave($reg1);
        $bibId = $sn1->bib->id;

        // Admin assigns a tag to the physical bib
        $sn1->bib->update(['tag_id' => 'CHIP-001']);

        // Participant is removed — the StartingNumber row is deleted, bib is freed
        $this->service->clearNumber($reg1);

        // Second participant is assigned — number 1 is free (no active assignment),
        // so assignAndSave picks it again via firstOrCreate, reusing the same Bib row.
        $reg2 = Registration::factory()->create(['track_id' => 1]);
        $sn2 = $this->service->assignAndSave($reg2);

        $this->assertEquals(1, $sn2->number);

        // The same Bib row was reused — tag_id is intact
        $this->assertEquals($bibId, $sn2->bib->id);
        $this->assertEquals('CHIP-001', $sn2->bib->tag_id);
    }

    public function test_two_participants_sharing_a_bib_see_same_tag_id(): void
    {
        // Create a bib directly (simulating a pre-configured bib)
        $bib = Bib::create(['number' => 1, 'tag_id' => 'CHIP-42']);

        $reg1 = Registration::factory()->create(['track_id' => 1]);
        $reg2 = Registration::factory()->create(['track_id' => 1]);

        // Manually assign both to the same bib
        $sn1 = StartingNumber::create(['registration_id' => $reg1->id, 'bib_id' => $bib->id]);
        $sn2 = StartingNumber::create(['registration_id' => $reg2->id, 'bib_id' => $bib->id]);

        // Both see the same tag
        $this->assertEquals('CHIP-42', $sn1->fresh()->load('bib')->tag_id);
        $this->assertEquals('CHIP-42', $sn2->fresh()->load('bib')->tag_id);

        // Updating tag via bib is immediately reflected for all assignments
        $bib->update(['tag_id' => 'CHIP-99']);

        $this->assertEquals('CHIP-99', $sn1->fresh()->load('bib')->tag_id);
        $this->assertEquals('CHIP-99', $sn2->fresh()->load('bib')->tag_id);
    }

    public function test_clearing_assignment_does_not_delete_bib_or_its_tag(): void
    {
        $reg = Registration::factory()->create(['track_id' => 1]);
        $sn = $this->service->assignAndSave($reg);
        $sn->bib->update(['tag_id' => 'CHIP-KEEP']);
        $bibId = $sn->bib->id;

        $this->service->clearNumber($reg);

        // StartingNumber row is gone
        $this->assertNull(StartingNumber::where('registration_id', $reg->id)->first());

        // But Bib row and its tag survive
        $bib = Bib::find($bibId);
        $this->assertNotNull($bib);
        $this->assertEquals('CHIP-KEEP', $bib->tag_id);
    }

    // -------------------------------------------------------------------------
    // DB-level uniqueness on bibs
    // -------------------------------------------------------------------------

    public function test_db_rejects_duplicate_bib_number(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Bib::create(['number' => 10]);
        Bib::create(['number' => 10]); // must fail - bibs are globally unique
    }

    public function test_same_bib_can_be_shared_across_tracks(): void
    {
        // Create one bib
        $bib = Bib::create(['number' => 10, 'tag_id' => 'CHIP-10']);

        // Assign to participants on different tracks
        $reg1 = Registration::factory()->create(['track_id' => 1]);
        $reg2 = Registration::factory()->create(['track_id' => 2]);

        $sn1 = StartingNumber::create(['registration_id' => $reg1->id, 'bib_id' => $bib->id]);
        $sn2 = StartingNumber::create(['registration_id' => $reg2->id, 'bib_id' => $bib->id]);

        // Both should reference the same bib
        $this->assertEquals($sn1->bib_id, $sn2->bib_id);
        $this->assertEquals('CHIP-10', $sn1->tag_id);
        $this->assertEquals('CHIP-10', $sn2->tag_id);
    }

    // -------------------------------------------------------------------------
    // Bulk assign
    // -------------------------------------------------------------------------

    public function test_bulk_assign_assigns_across_tracks_independently(): void
    {
        $regA1 = Registration::factory()->create(['track_id' => 1]);
        $regA2 = Registration::factory()->create(['track_id' => 1]);
        $regB1 = Registration::factory()->create(['track_id' => 2]);

        $results = $this->service->bulkAssignNumbers([$regA1->id, $regA2->id, $regB1->id]);

        $this->assertCount(3, $results['assigned']);
        $this->assertCount(0, $results['failed']);

        // Track A: numbers 1 and 2
        $this->assertEquals(1, $regA1->fresh()->startingNumber->number);
        $this->assertEquals(2, $regA2->fresh()->startingNumber->number);

        // Track B: also starts at 1 (independent range)
        $this->assertEquals(1, $regB1->fresh()->startingNumber->number);
    }

    // -------------------------------------------------------------------------
    // Tag propagation and sharing tests
    // -------------------------------------------------------------------------

    public function test_updating_shared_bib_tag_id_visible_to_all_participants(): void
    {
        // Create a bib with initial tag_id
        $bib = Bib::create(['number' => 5, 'tag_id' => 'TAG-OLD']);

        // Assign three different participants to the same bib
        $reg1 = Registration::factory()->create(['track_id' => 1]);
        $reg2 = Registration::factory()->create(['track_id' => 1]);
        $reg3 = Registration::factory()->create(['track_id' => 1]);

        $sn1 = StartingNumber::create(['registration_id' => $reg1->id, 'bib_id' => $bib->id]);
        $sn2 = StartingNumber::create(['registration_id' => $reg2->id, 'bib_id' => $bib->id]);
        $sn3 = StartingNumber::create(['registration_id' => $reg3->id, 'bib_id' => $bib->id]);

        // All should see the initial tag
        $this->assertEquals('TAG-OLD', $sn1->fresh()->load('bib')->tag_id);
        $this->assertEquals('TAG-OLD', $sn2->fresh()->load('bib')->tag_id);
        $this->assertEquals('TAG-OLD', $sn3->fresh()->load('bib')->tag_id);

        // Update the tag_id on the bib
        $bib->update(['tag_id' => 'TAG-NEW']);

        // All participants should immediately see the new tag via the accessor
        $this->assertEquals('TAG-NEW', $sn1->fresh()->load('bib')->tag_id);
        $this->assertEquals('TAG-NEW', $sn2->fresh()->load('bib')->tag_id);
        $this->assertEquals('TAG-NEW', $sn3->fresh()->load('bib')->tag_id);

        // Verify through the registration relationship as well
        $this->assertEquals('TAG-NEW', $reg1->fresh()->startingNumber->tag_id);
        $this->assertEquals('TAG-NEW', $reg2->fresh()->startingNumber->tag_id);
        $this->assertEquals('TAG-NEW', $reg3->fresh()->startingNumber->tag_id);
    }

    public function test_manual_assignment_reuses_existing_bib_with_tag(): void
    {
        // Create a bib with a tag_id
        $bib = Bib::create(['number' => 100, 'tag_id' => 'CHIP-100']);

        // First participant gets assigned (automatic via service)
        $reg1 = Registration::factory()->create(['track_id' => 1]);
        $sn1 = StartingNumber::create(['registration_id' => $reg1->id, 'bib_id' => $bib->id]);

        $this->assertEquals('CHIP-100', $sn1->fresh()->load('bib')->tag_id);

        // Second participant gets manually assigned the same number (simulating UI flow)
        $reg2 = Registration::factory()->create(['track_id' => 1]);

        // The EditRegistration logic uses firstOrCreate, so let's simulate that
        $bibReused = Bib::firstOrCreate(
            ['number' => 100]
        );

        $this->assertEquals($bib->id, $bibReused->id, 'Should reuse existing bib');
        $this->assertEquals('CHIP-100', $bibReused->tag_id, 'Tag should be preserved');

        // Create assignment for second participant
        $sn2 = StartingNumber::create(['registration_id' => $reg2->id, 'bib_id' => $bibReused->id]);

        // Both participants see the same tag
        $this->assertEquals('CHIP-100', $sn1->fresh()->load('bib')->tag_id);
        $this->assertEquals('CHIP-100', $sn2->fresh()->load('bib')->tag_id);

        // They're using the same physical bib
        $this->assertEquals($sn1->bib_id, $sn2->bib_id);
    }

    public function test_deleting_bib_with_assignments_throws_exception(): void
    {
        // Create a bib with assignments
        $bib = Bib::create(['number' => 50, 'tag_id' => 'CHIP-50']);

        $reg1 = Registration::factory()->create(['track_id' => 1]);
        $reg2 = Registration::factory()->create(['track_id' => 1]);

        StartingNumber::create(['registration_id' => $reg1->id, 'bib_id' => $bib->id]);
        StartingNumber::create(['registration_id' => $reg2->id, 'bib_id' => $bib->id]);

        // Attempting to delete the bib should throw an exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete bib #50');

        $bib->delete();
    }

    public function test_deleting_bib_without_assignments_succeeds(): void
    {
        // Create a bib with no assignments
        $bib = Bib::create(['number' => 99, 'tag_id' => 'CHIP-99']);

        // Should be able to delete it
        $bib->delete();

        $this->assertNull(Bib::find($bib->id));
    }
}
