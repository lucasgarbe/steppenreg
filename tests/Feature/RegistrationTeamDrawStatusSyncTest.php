<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTeamDrawStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_members_are_marked_as_drawn_when_one_member_is_drawn(): void
    {
        $team = Team::factory()->create(['track_id' => 1]);

        $registration1 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        $registration2 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        $registration3 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        $registration1->update([
            'draw_status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration1->id,
            'draw_status' => 'drawn',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration2->id,
            'draw_status' => 'drawn',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration3->id,
            'draw_status' => 'drawn',
        ]);
    }

    public function test_team_members_are_marked_as_not_drawn_when_one_member_is_marked_not_drawn(): void
    {
        $team = Team::factory()->create(['track_id' => 1]);

        $registration1 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $registration2 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $registration1->update([
            'draw_status' => 'not_drawn',
            'drawn_at' => null,
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration1->id,
            'draw_status' => 'not_drawn',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration2->id,
            'draw_status' => 'not_drawn',
        ]);
    }

    public function test_individual_registrations_are_not_affected(): void
    {
        $registration = Registration::factory()->create([
            'team_id' => null,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        $registration->update([
            'draw_status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration->id,
            'draw_status' => 'drawn',
        ]);
    }

    public function test_team_members_share_same_drawn_at_timestamp(): void
    {
        $team = Team::factory()->create(['track_id' => 1]);

        $registration1 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        $registration2 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        $drawnAt = now();
        $registration1->update([
            'draw_status' => 'drawn',
            'drawn_at' => $drawnAt,
        ]);

        $registration1->refresh();
        $registration2->refresh();

        $this->assertEquals($drawnAt->toDateTimeString(), $registration1->drawn_at->toDateTimeString());
        $this->assertEquals($drawnAt->toDateTimeString(), $registration2->drawn_at->toDateTimeString());
    }

    public function test_sync_works_with_bulk_update(): void
    {
        $team = Team::factory()->create(['track_id' => 1]);

        $registration1 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        $registration2 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        $registration3 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        Registration::where('team_id', $team->id)->update([
            'draw_status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration1->id,
            'draw_status' => 'drawn',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration2->id,
            'draw_status' => 'drawn',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration3->id,
            'draw_status' => 'drawn',
        ]);
    }

    public function test_different_teams_are_not_affected(): void
    {
        $team1 = Team::factory()->create(['track_id' => 1]);
        $team2 = Team::factory()->create(['track_id' => 1]);

        $registration1 = Registration::factory()->create([
            'team_id' => $team1->id,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        $registration2 = Registration::factory()->create([
            'team_id' => $team2->id,
            'track_id' => 1,
            'draw_status' => 'not_drawn',
        ]);

        $registration1->update([
            'draw_status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration1->id,
            'draw_status' => 'drawn',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration2->id,
            'draw_status' => 'not_drawn',
        ]);
    }

    public function test_observer_does_not_trigger_when_draw_status_unchanged(): void
    {
        $team = Team::factory()->create(['track_id' => 1]);

        $registration1 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $registration2 = Registration::factory()->create([
            'team_id' => $team->id,
            'track_id' => 1,
            'draw_status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $registration1->update([
            'name' => 'Updated Name',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration1->id,
            'name' => 'Updated Name',
            'draw_status' => 'drawn',
        ]);

        $this->assertDatabaseHas('registrations', [
            'id' => $registration2->id,
            'draw_status' => 'drawn',
        ]);
    }
}
