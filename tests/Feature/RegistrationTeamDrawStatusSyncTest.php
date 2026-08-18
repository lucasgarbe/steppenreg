<?php

namespace Tests\Feature;

use App\Domain\Draw\Models\DrawStatus;
use App\Domain\Draw\Services\DrawService;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegistrationTeamDrawStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    private DrawService $drawService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->drawService = app(DrawService::class);
        Event::fake();
    }

    public function test_team_members_are_marked_as_drawn_when_one_member_is_drawn(): void
    {
        $team = Team::factory()->create(['track_id' => 1]);

        $registration1 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);
        $registration2 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);
        $registration3 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);

        $this->drawService->markDrawn($registration1);

        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $registration1->id, 'status' => 'drawn']);
        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $registration2->id, 'status' => 'drawn']);
        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $registration3->id, 'status' => 'drawn']);
    }

    public function test_team_members_are_marked_as_not_drawn_when_one_member_is_marked_not_drawn(): void
    {
        $team = Team::factory()->create(['track_id' => 1]);

        $registration1 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);
        $registration2 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);

        $this->drawService->markDrawn($registration1);
        $this->drawService->markNotDrawn($registration1);

        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $registration1->id, 'status' => 'not_drawn']);
        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $registration2->id, 'status' => 'not_drawn']);
    }

    public function test_individual_registrations_are_not_affected(): void
    {
        $registration = Registration::factory()->create(['team_id' => null, 'track_id' => 1]);

        $this->drawService->markDrawn($registration);

        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $registration->id, 'status' => 'drawn']);
    }

    public function test_team_members_share_same_drawn_at_timestamp(): void
    {
        $team = Team::factory()->create(['track_id' => 1]);

        $registration1 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);
        $registration2 = Registration::factory()->create(['team_id' => $team->id, 'track_id' => 1]);

        $this->drawService->markDrawn($registration1);

        $ds1 = DrawStatus::where('registration_id', $registration1->id)->first();
        $ds2 = DrawStatus::where('registration_id', $registration2->id)->first();

        $this->assertEquals($ds1->drawn_at->toDateTimeString(), $ds2->drawn_at->toDateTimeString());
    }

    public function test_different_teams_are_not_affected(): void
    {
        $team1 = Team::factory()->create(['track_id' => 1]);
        $team2 = Team::factory()->create(['track_id' => 1]);

        $registration1 = Registration::factory()->create(['team_id' => $team1->id, 'track_id' => 1]);
        $registration2 = Registration::factory()->create(['team_id' => $team2->id, 'track_id' => 1]);

        $this->drawService->markDrawn($registration1);

        $this->assertDatabaseHas('draw_statuses', ['registration_id' => $registration1->id, 'status' => 'drawn']);
        $this->assertDatabaseMissing('draw_statuses', ['registration_id' => $registration2->id]);
    }
}
