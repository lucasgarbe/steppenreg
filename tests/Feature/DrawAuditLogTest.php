<?php

namespace Tests\Feature;

use App\Domain\Draw\Services\DrawService;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DrawAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private DrawService $drawService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->drawService = app(DrawService::class);
        Event::fake();
    }

    public function test_mark_drawn_logs_activity(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $registration = Registration::factory()->create(['track_id' => 1]);

        $this->drawService->markDrawn($registration);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'registration_marked_drawn',
            'causer_id' => $user->id,
        ]);

        $activity = Activity::where('event', 'registration_marked_drawn')->first();
        $this->assertEquals($registration->id, $activity->properties['registration_id']);
    }

    public function test_mark_not_drawn_logs_activity(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $registration = Registration::factory()->create(['track_id' => 1]);

        $this->drawService->markNotDrawn($registration);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'registration_marked_not_drawn',
            'causer_id' => $user->id,
        ]);
    }

    public function test_execute_draw_logs_activity(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        Registration::factory()->count(5)->create(['track_id' => 1, 'team_id' => null]);

        $this->drawService->executeDraw(1, 3, $user->id);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'draw_executed',
            'causer_id' => $user->id,
        ]);

        $activity = Activity::where('event', 'draw_executed')->first();
        $this->assertEquals(1, $activity->properties['track_id']);
        $this->assertEquals(3, $activity->properties['total_drawn']);
        $this->assertEquals(2, $activity->properties['total_not_drawn']);
    }
}
