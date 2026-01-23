<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Teams\Pages\CreateTeam;
use App\Filament\Resources\Teams\Pages\EditTeam;
use App\Models\Team;
use App\Models\User;
use App\Settings\EventSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentTeamValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up event settings
        $settings = EventSettings::fake([
            'application_state' => 'general_open',
            'enforce_same_track_for_teams' => true,
            'tracks' => [
                ['id' => 1, 'name' => 'Track 1', 'distance' => '5km'],
                ['id' => 2, 'name' => 'Track 2', 'distance' => '10km'],
            ],
        ]);
        app()->instance(EventSettings::class, $settings);

        // Authenticate as admin user
        $this->actingAs(User::factory()->create());
    }

    public function test_filament_allows_creating_team_with_soft_deleted_name(): void
    {
        // Create and soft-delete a team
        $team = Team::create([
            'name' => 'Phoenix Team',
            'max_members' => 5,
            'track_id' => 1,
        ]);
        $team->delete();

        $this->assertEquals(0, Team::count());
        $this->assertEquals(1, Team::withTrashed()->count());

        // Attempt to create team with same name via Filament
        Livewire::test(CreateTeam::class)
            ->fillForm([
                'name' => 'Phoenix Team',
                'max_members' => 5,
                'track_id' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify new team was created
        $this->assertEquals(2, Team::withTrashed()->count());
        $this->assertEquals(1, Team::count());
    }

    public function test_filament_prevents_duplicate_active_team_names(): void
    {
        // Create active team
        Team::create([
            'name' => 'Active Team',
            'max_members' => 5,
            'track_id' => 1,
        ]);

        // Attempt to create another team with same name and track
        Livewire::test(CreateTeam::class)
            ->fillForm([
                'name' => 'Active Team',
                'max_members' => 5,
                'track_id' => 1,
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);

        // Only one team should exist
        $this->assertEquals(1, Team::count());
    }

    public function test_filament_edit_team_allows_keeping_same_name(): void
    {
        // Create a team
        $team = Team::create([
            'name' => 'Existing Team',
            'max_members' => 5,
            'track_id' => 1,
        ]);

        // Edit the team without changing the name
        Livewire::test(EditTeam::class, [
            'record' => $team->id,
        ])
            ->fillForm([
                'name' => 'Existing Team', // Same name
                'max_members' => 10, // Changed max_members
                'track_id' => 1,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Verify team was updated
        $team->refresh();
        $this->assertEquals('Existing Team', $team->name);
        $this->assertEquals(10, $team->max_members);
    }

    public function test_filament_allows_same_team_name_on_different_tracks(): void
    {
        $settings = app(EventSettings::class);
        $settings->enforce_same_track_for_teams = true;

        // Create team on Track 1
        Team::create([
            'name' => 'Warriors',
            'max_members' => 5,
            'track_id' => 1,
        ]);

        // Create team with same name on Track 2 (should succeed)
        Livewire::test(CreateTeam::class)
            ->fillForm([
                'name' => 'Warriors',
                'max_members' => 5,
                'track_id' => 2,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertEquals(2, Team::count());
    }

    public function test_filament_prevents_duplicate_when_not_enforcing_tracks(): void
    {
        $settings = app(EventSettings::class);
        $settings->enforce_same_track_for_teams = false;

        // Create team
        Team::create([
            'name' => 'Global Team',
            'max_members' => 5,
            'track_id' => null,
        ]);

        // Attempt to create another team with same name (should fail)
        Livewire::test(CreateTeam::class)
            ->fillForm([
                'name' => 'Global Team',
                'max_members' => 5,
                'track_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);

        $this->assertEquals(1, Team::count());
    }
}
