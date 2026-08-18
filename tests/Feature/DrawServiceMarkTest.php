<?php

namespace Tests\Feature;

use App\Domain\Draw\Events\RegistrationDrawn;
use App\Domain\Draw\Events\RegistrationNotDrawn;
use App\Domain\Draw\Models\DrawStatus;
use App\Domain\Draw\Services\DrawService;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DrawServiceMarkTest extends TestCase
{
    use RefreshDatabase;

    private DrawService $drawService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->drawService = app(DrawService::class);
    }

    public function test_mark_drawn_creates_draw_status_record(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);

        $this->drawService->markDrawn($registration);

        $this->assertDatabaseHas('draw_statuses', [
            'registration_id' => $registration->id,
            'status' => 'drawn',
        ]);

        $drawStatus = DrawStatus::where('registration_id', $registration->id)->first();
        $this->assertNotNull($drawStatus->drawn_at);
    }

    public function test_mark_not_drawn_creates_draw_status_record(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);

        $this->drawService->markNotDrawn($registration);

        $this->assertDatabaseHas('draw_statuses', [
            'registration_id' => $registration->id,
            'status' => 'not_drawn',
        ]);

        $drawStatus = DrawStatus::where('registration_id', $registration->id)->first();
        $this->assertNull($drawStatus->drawn_at);
    }

    public function test_mark_drawn_fires_registration_drawn_event(): void
    {
        Event::fake([RegistrationDrawn::class]);

        $registration = Registration::factory()->create(['track_id' => 1]);

        $this->drawService->markDrawn($registration);

        Event::assertDispatched(RegistrationDrawn::class, function ($event) use ($registration) {
            return $event->registration->id === $registration->id;
        });
    }

    public function test_mark_not_drawn_fires_registration_not_drawn_event(): void
    {
        Event::fake([RegistrationNotDrawn::class]);

        $registration = Registration::factory()->create(['track_id' => 1]);

        $this->drawService->markNotDrawn($registration);

        Event::assertDispatched(RegistrationNotDrawn::class, function ($event) use ($registration) {
            return $event->registration->id === $registration->id;
        });
    }

    public function test_mark_drawn_syncs_all_team_members(): void
    {
        Event::fake([RegistrationDrawn::class]);

        $team = Team::factory()->create(['track_id' => 1]);

        $reg1 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);
        $reg2 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);
        $reg3 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);

        $this->drawService->markDrawn($reg1);

        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $reg1->id, 'status' => 'drawn']);
        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $reg2->id, 'status' => 'drawn']);
        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $reg3->id, 'status' => 'drawn']);
    }

    public function test_mark_not_drawn_syncs_all_team_members(): void
    {
        Event::fake([RegistrationNotDrawn::class]);

        $team = Team::factory()->create(['track_id' => 1]);

        $reg1 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);
        $reg2 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);

        $this->drawService->markNotDrawn($reg1);

        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $reg1->id, 'status' => 'not_drawn']);
        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $reg2->id, 'status' => 'not_drawn']);
    }

    public function test_mark_drawn_fires_event_per_team_member(): void
    {
        Event::fake([RegistrationDrawn::class]);

        $team = Team::factory()->create(['track_id' => 1]);

        $reg1 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);
        $reg2 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);
        $reg3 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);

        $this->drawService->markDrawn($reg1);

        Event::assertDispatchedTimes(RegistrationDrawn::class, 3);
    }

    public function test_mark_drawn_does_not_affect_other_teams(): void
    {
        Event::fake([RegistrationDrawn::class]);

        $team1 = Team::factory()->create(['track_id' => 1]);
        $team2 = Team::factory()->create(['track_id' => 1]);

        $reg1 = Registration::factory()->create(['team_id' => $team1->id, 'track_id' => 1]);
        $reg2 = Registration::factory()->create(['team_id' => $team2->id, 'track_id' => 1]);

        $this->drawService->markDrawn($reg1);

        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $reg1->id, 'status' => 'drawn']);
        $this->assertDatabaseMissing('draw_statuses', ['registration_id' => $reg2->id]);
    }

    public function test_mark_drawn_updates_existing_draw_status(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);

        $this->drawService->markNotDrawn($registration);
        $this->drawService->markDrawn($registration);

        $this->assertDatabaseHas('draw_statuses', [
            'registration_id' => $registration->id,
            'status' => 'drawn',
        ]);

        $this->assertEquals(1, DrawStatus::where('registration_id', $registration->id)->count());
    }

    public function test_individual_registration_mark_does_not_affect_others(): void
    {
        Event::fake([RegistrationDrawn::class]);

        $reg1 = Registration::factory()->create(['track_id' => 1, 'team_id' => null]);
        $reg2 = Registration::factory()->create(['track_id' => 1, 'team_id' => null]);

        $this->drawService->markDrawn($reg1);

        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $reg1->id, 'status' => 'drawn']);
        $this->assertDatabaseMissing('draw_statuses', ['registration_id' => $reg2->id]);

        Event::assertDispatchedTimes(RegistrationDrawn::class, 1);
    }
}
