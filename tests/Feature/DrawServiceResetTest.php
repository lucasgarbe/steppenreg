<?php

namespace Tests\Feature;

use App\Domain\Draw\Services\DrawService;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DrawServiceResetTest extends TestCase
{
    use RefreshDatabase;

    private DrawService $drawService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->drawService = app(DrawService::class);
    }

    public function test_resetting_an_individual_removes_its_draw_status(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);
        $this->drawService->markNotDrawn($registration);

        $this->drawService->resetToPool($registration);

        $this->assertDatabaseMissing('draw_statuses', [
            'registration_id' => $registration->id,
        ]);
        $this->assertSame(1, Registration::withoutDrawStatus()->count());
    }

    public function test_resetting_one_member_returns_the_whole_team_to_the_pool(): void
    {
        $team = Team::factory()->create();
        $members = Registration::factory()->count(3)->create([
            'track_id' => $team->track_id,
            'team_id' => $team->id,
        ]);
        $members->each(fn (Registration $member) => $this->drawService->markNotDrawn($member));
        $outsider = Registration::factory()->create([
            'track_id' => $team->track_id,
            'team_id' => null,
        ]);
        $this->drawService->markNotDrawn($outsider);

        $this->drawService->resetToPool($members->first());

        $this->assertDatabaseMissing('draw_statuses', [
            'registration_id' => $members->first()->id,
        ]);
        $this->assertSame(3, Registration::withoutDrawStatus()->count());
        $this->assertDatabaseHas('draw_statuses', [
            'registration_id' => $outsider->id,
            'status' => 'not_drawn',
        ]);
    }

    public function test_reset_removes_drawn_status_too(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);
        $this->drawService->markDrawn($registration);

        $this->drawService->resetToPool($registration);

        $this->assertDatabaseMissing('draw_statuses', [
            'registration_id' => $registration->id,
        ]);
    }

    public function test_reset_logs_activity(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);
        $this->drawService->markNotDrawn($registration);

        $this->drawService->resetToPool($registration);

        $activity = Activity::where('event', 'registration_reset_to_pool')
            ->where('subject_id', $registration->id)
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame(1, $activity->properties->get('affected_count'));
    }

    public function test_reset_registrations_are_included_in_the_next_draw(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);
        $this->drawService->markNotDrawn($registration);

        $this->drawService->resetToPool($registration);

        $result = $this->drawService->executeDraw(1, 5, null);

        $this->assertSame(1, $result['total_drawn']);
        $this->assertDatabaseHas('draw_statuses', [
            'registration_id' => $registration->id,
            'status' => 'drawn',
        ]);
    }
}
