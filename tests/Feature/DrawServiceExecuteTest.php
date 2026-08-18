<?php

namespace Tests\Feature;

use App\Domain\Draw\Events\DrawExecuted;
use App\Domain\Draw\Events\RegistrationDrawn;
use App\Domain\Draw\Events\RegistrationNotDrawn;
use App\Domain\Draw\Exceptions\InsufficientRegistrationsException;
use App\Domain\Draw\Models\DrawStatus;
use App\Domain\Draw\Services\DrawService;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DrawServiceExecuteTest extends TestCase
{
    use RefreshDatabase;

    private DrawService $drawService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->drawService = app(DrawService::class);
    }

    public function test_execute_draw_returns_stats(): void
    {
        Event::fake();

        Registration::factory()->count(5)->create(['track_id' => 1, 'team_id' => null]);

        $result = $this->drawService->executeDraw(1, 3);

        $this->assertArrayHasKey('track_id', $result);
        $this->assertArrayHasKey('total_registrations', $result);
        $this->assertArrayHasKey('total_drawn', $result);
        $this->assertArrayHasKey('total_not_drawn', $result);
        $this->assertArrayHasKey('available_spots', $result);
        $this->assertEquals(1, $result['track_id']);
        $this->assertEquals(5, $result['total_registrations']);
        $this->assertEquals(3, $result['total_drawn']);
        $this->assertEquals(2, $result['total_not_drawn']);
        $this->assertEquals(3, $result['available_spots']);
    }

    public function test_execute_draw_creates_draw_status_records(): void
    {
        Event::fake();

        Registration::factory()->count(5)->create(['track_id' => 1, 'team_id' => null]);

        $this->drawService->executeDraw(1, 3);

        $this->assertEquals(5, DrawStatus::count());
        $this->assertEquals(3, DrawStatus::where('status', 'drawn')->count());
        $this->assertEquals(2, DrawStatus::where('status', 'not_drawn')->count());
    }

    public function test_execute_draw_fires_events_per_registration(): void
    {
        Event::fake([RegistrationDrawn::class, RegistrationNotDrawn::class, DrawExecuted::class]);

        Registration::factory()->count(5)->create(['track_id' => 1, 'team_id' => null]);

        $this->drawService->executeDraw(1, 3);

        Event::assertDispatchedTimes(RegistrationDrawn::class, 3);
        Event::assertDispatchedTimes(RegistrationNotDrawn::class, 2);
        Event::assertDispatchedTimes(DrawExecuted::class, 1);
    }

    public function test_execute_draw_throws_when_no_registrations(): void
    {
        $this->expectException(InsufficientRegistrationsException::class);

        $this->drawService->executeDraw(1, 3);
    }

    public function test_execute_draw_throws_when_all_already_have_status(): void
    {
        $registrations = Registration::factory()->count(3)->create(['track_id' => 1, 'team_id' => null]);

        foreach ($registrations as $reg) {
            DrawStatus::create(['registration_id' => $reg->id, 'status' => 'not_drawn']);
        }

        $this->expectException(InsufficientRegistrationsException::class);

        $this->drawService->executeDraw(1, 2);
    }

    public function test_execute_draw_keeps_teams_together(): void
    {
        Event::fake();

        $team = Team::factory()->create(['track_id' => 1]);
        Registration::factory()->count(3)->create(['team_id' => $team->id, 'track_id' => 1]);
        Registration::factory()->count(2)->create(['track_id' => 1, 'team_id' => null]);

        $result = $this->drawService->executeDraw(1, 3);

        $drawnStatuses = DrawStatus::where('status', 'drawn')->get();
        $drawnRegistrationIds = $drawnStatuses->pluck('registration_id')->toArray();

        $teamRegistrations = Registration::where('team_id', $team->id)->pluck('id')->toArray();

        $teamDrawnCount = count(array_intersect($teamRegistrations, $drawnRegistrationIds));
        $this->assertTrue($teamDrawnCount === 0 || $teamDrawnCount === 3);
    }

    public function test_execute_draw_can_run_multiple_times_with_new_registrations(): void
    {
        Event::fake();

        Registration::factory()->count(10)->create(['track_id' => 1, 'team_id' => null]);

        $result1 = $this->drawService->executeDraw(1, 3);
        $this->assertEquals(3, $result1['total_drawn']);

        Registration::factory()->count(5)->create(['track_id' => 1, 'team_id' => null]);

        $result2 = $this->drawService->executeDraw(1, 3);
        $this->assertEquals(3, $result2['total_drawn']);

        $this->assertEquals(6, DrawStatus::where('status', 'drawn')->count());
    }

    public function test_execute_draw_only_eligible_registrations_without_status(): void
    {
        Event::fake();

        $existing = Registration::factory()->count(3)->create(['track_id' => 1, 'team_id' => null]);
        foreach ($existing as $reg) {
            DrawStatus::create(['registration_id' => $reg->id, 'status' => 'drawn', 'drawn_at' => now()]);
        }

        Registration::factory()->count(5)->create(['track_id' => 1, 'team_id' => null]);

        $result = $this->drawService->executeDraw(1, 3);

        $this->assertEquals(5, $result['total_registrations']);
        $this->assertEquals(3, $result['total_drawn']);
    }
}
