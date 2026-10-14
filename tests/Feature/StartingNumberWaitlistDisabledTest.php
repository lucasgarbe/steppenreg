<?php

namespace Tests\Feature;

use App\Domain\Draw\Models\DrawStatus;
use App\Domain\StartingNumber\Services\StartingNumberService;
use App\Models\Registration;
use App\Settings\EventSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartingNumberWaitlistDisabledTest extends TestCase
{
    use RefreshDatabase;

    private StartingNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['steppenreg.features.waitlist' => false]);
        $this->service = app(StartingNumberService::class);

        $settings = app(EventSettings::class);
        $settings->tracks = [
            ['id' => 1, 'name' => 'Track One', 'distance' => 100, 'max_participants' => 199],
            ['id' => 2, 'name' => 'Track Two', 'distance' => 200, 'max_participants' => 199],
        ];
        $settings->save();
    }

    public function test_ranges_do_not_contain_a_waitlist_block(): void
    {
        $ranges = $this->service->getTrackRanges(1);

        $this->assertArrayHasKey('main', $ranges);
        $this->assertArrayNotHasKey('waitlist', $ranges);
        $this->assertEquals(1, $ranges['main']['start']);
        $this->assertEquals(199, $ranges['main']['end']);
    }

    public function test_next_track_starts_after_previous_main_range(): void
    {
        // With the waitlist block the second track would start at 300;
        // without it, the 100-boundary after main end (199) is 200.
        $ranges = $this->service->getTrackRanges(2);

        $this->assertEquals(200, $ranges['main']['start']);
        $this->assertEquals(398, $ranges['main']['end']);
    }

    public function test_overflow_activates_after_main_capacity(): void
    {
        for ($i = 1; $i <= 205; $i++) {
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

        // 205 drawn, main capacity 199, no waitlist reservation: 6 overflow numbers
        $this->assertArrayHasKey('waitlist_overflow', $ranges);
        $this->assertEquals(900, $ranges['waitlist_overflow']['start']);
        $this->assertEquals(905, $ranges['waitlist_overflow']['end']);
    }

    public function test_waitlist_status_returns_zeros_when_disabled(): void
    {
        $status = $this->service->getWaitlistStatus(1);

        $this->assertSame(0, $status['used']);
        $this->assertSame(0, $status['capacity']);
        $this->assertSame(0, $status['percentage']);
        $this->assertSame('normal', $status['status']);
    }

    public function test_assignment_still_uses_main_range_when_disabled(): void
    {
        $registration = Registration::factory()->create([
            'track_id' => 2,
            'starting_number' => null,
        ]);
        DrawStatus::create([
            'registration_id' => $registration->id,
            'status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $number = $this->service->assignAndSave($registration->fresh());

        // Track 2 main range is 200-398 when the waitlist block is off
        $this->assertEquals(200, $number);
        $this->assertEquals(200, $registration->fresh()->starting_number);
    }
}
