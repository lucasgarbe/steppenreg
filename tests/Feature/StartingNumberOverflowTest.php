<?php

namespace Tests\Feature;

use App\Domain\Draw\Models\DrawStatus;
use App\Domain\StartingNumber\Services\StartingNumberService;
use App\Models\Registration;
use App\Settings\EventSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartingNumberOverflowTest extends TestCase
{
    use RefreshDatabase;

    private StartingNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StartingNumberService::class);
    }

    public function test_overflow_activates_when_main_and_waitlist_are_full(): void
    {
        $settings = app(EventSettings::class);
        $settings->tracks = [
            ['id' => 1, 'name' => 'Test Track', 'distance' => 100, 'max_participants' => 10],
        ];
        $settings->save();

        $mainCapacity = 10;
        $waitlistCapacity = 10;
        $totalToCreate = $mainCapacity + $waitlistCapacity + 5;

        for ($i = 1; $i <= $totalToCreate; $i++) {
            $registration = Registration::factory()->create([
                'track_id' => 1,
                'starting_number' => $i,
            ]);
            DrawStatus::create([
                'registration_id' => $registration->id,
                'status' => 'drawn',
                'drawn_at' => now(),
            ]);
        }

        $ranges = $this->service->getTrackRanges(1);

        $this->assertArrayHasKey('waitlist_overflow', $ranges);
        $this->assertEquals(900, $ranges['waitlist_overflow']['start']);
        $this->assertEquals(904, $ranges['waitlist_overflow']['end']);
    }

    public function test_overflow_does_not_activate_when_waitlist_not_full(): void
    {
        $settings = app(EventSettings::class);
        $settings->tracks = [
            ['id' => 1, 'name' => 'Test Track', 'distance' => 100, 'max_participants' => 10],
        ];
        $settings->save();

        $mainCapacity = 10;
        $registrationsToCreate = $mainCapacity + 5;

        for ($i = 1; $i <= $registrationsToCreate; $i++) {
            $registration = Registration::factory()->create([
                'track_id' => 1,
                'starting_number' => $i,
            ]);
            DrawStatus::create([
                'registration_id' => $registration->id,
                'status' => 'drawn',
                'drawn_at' => now(),
            ]);
        }

        $ranges = $this->service->getTrackRanges(1);

        $this->assertArrayNotHasKey('waitlist_overflow', $ranges);
    }

    public function test_overflow_count_includes_soft_deleted_registrations(): void
    {
        $settings = app(EventSettings::class);
        $settings->tracks = [
            ['id' => 1, 'name' => 'Test Track', 'distance' => 100, 'max_participants' => 10],
        ];
        $settings->save();

        $mainCapacity = 10;
        $waitlistCapacity = 10;

        for ($i = 1; $i <= $mainCapacity + $waitlistCapacity; $i++) {
            $registration = Registration::factory()->create([
                'track_id' => 1,
                'starting_number' => $i,
            ]);
            DrawStatus::create([
                'registration_id' => $registration->id,
                'status' => 'drawn',
                'drawn_at' => now(),
            ]);
        }

        $softDeleted = Registration::factory()->create([
            'track_id' => 1,
            'starting_number' => $mainCapacity + $waitlistCapacity + 1,
        ]);
        DrawStatus::create([
            'registration_id' => $softDeleted->id,
            'status' => 'drawn',
            'drawn_at' => now(),
        ]);
        $softDeleted->delete();

        $ranges = $this->service->getTrackRanges(1);

        $this->assertArrayHasKey('waitlist_overflow', $ranges);
        $this->assertEquals(900, $ranges['waitlist_overflow']['start']);
        $this->assertEquals(900, $ranges['waitlist_overflow']['end']);
    }

    public function test_overflow_does_not_count_registrations_without_starting_number(): void
    {
        $settings = app(EventSettings::class);
        $settings->tracks = [
            ['id' => 1, 'name' => 'Test Track', 'distance' => 100, 'max_participants' => 10],
        ];
        $settings->save();

        $mainCapacity = 10;
        $waitlistCapacity = 10;

        for ($i = 1; $i <= $mainCapacity + $waitlistCapacity; $i++) {
            $registration = Registration::factory()->create([
                'track_id' => 1,
                'starting_number' => $i,
            ]);
            DrawStatus::create([
                'registration_id' => $registration->id,
                'status' => 'drawn',
                'drawn_at' => now(),
            ]);
        }

        $registrationWithoutNumber = Registration::factory()->create([
            'track_id' => 1,
            'starting_number' => null,
        ]);
        DrawStatus::create([
            'registration_id' => $registrationWithoutNumber->id,
            'status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $ranges = $this->service->getTrackRanges(1);

        $this->assertArrayNotHasKey('waitlist_overflow', $ranges);
    }
}
