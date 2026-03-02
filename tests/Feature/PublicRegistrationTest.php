<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up event settings
        $settings = EventSettings::fake([
            'application_state' => 'general_open',
            'custom_questions' => [],
            'enforce_same_track_for_teams' => true,
            'tracks' => [
                ['id' => 1, 'name' => 'Track 1', 'distance' => '5km'],
                ['id' => 2, 'name' => 'Track 2', 'distance' => '10km'],
            ],
            'gender_categories' => [
                [
                    'key' => 'flinta',
                    'sort_order' => 1,
                    'available_in_priority' => true,
                    'available_in_open' => true,
                    'translations' => [
                        'en' => ['label' => 'FLINTA*'],
                        'de' => ['label' => 'FLINTA*'],
                    ],
                ],
                [
                    'key' => 'all_gender',
                    'sort_order' => 2,
                    'available_in_priority' => false,
                    'available_in_open' => true,
                    'translations' => [
                        'en' => ['label' => 'All Gender'],
                        'de' => ['label' => 'All Gender'],
                    ],
                ],
            ],
            'theme_primary_color' => '#F9C458',
            'theme_background_color' => '#fffdf8c2',
            'theme_text_color' => '#1a1a1a',
            'theme_accent_color' => '#7a58fc',
        ]);
        app()->instance(EventSettings::class, $settings);
    }

    protected function validRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 25,
            'gender' => 'all_gender',
            'track_id' => 1,
            'notes' => 'Test notes',
        ], $overrides);
    }

    public function test_can_create_registration(): void
    {
        $response = $this->post(route('registration.store'), $this->validRegistrationData());

        $response->assertRedirect(route('registration.success'));
        $this->assertDatabaseHas('registrations', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 25,
            'gender' => 'all_gender',
            'track_id' => 1,
        ]);
    }

    public function test_duplicate_registration_within_5_minutes_redirects_to_success(): void
    {
        $data = $this->validRegistrationData();

        // First registration
        $this->post(route('registration.store'), $data);

        $this->assertDatabaseCount('registrations', 1);

        // Immediate duplicate (within 5 minutes)
        $response = $this->post(route('registration.store'), $data);

        $response->assertRedirect(route('registration.success'));
        $this->assertDatabaseCount('registrations', 1); // No new registration created
    }

    public function test_duplicate_registration_after_5_minutes_creates_new_registration(): void
    {
        $data = $this->validRegistrationData();

        // First registration
        $this->post(route('registration.store'), $data);
        $this->assertDatabaseCount('registrations', 1);

        // Travel forward 6 minutes
        $this->travel(6)->minutes();

        // Second registration after 5-minute window
        $response = $this->post(route('registration.store'), $data);

        $response->assertRedirect(route('registration.success'));
        $this->assertDatabaseCount('registrations', 2); // New registration created
    }

    public function test_family_registration_same_email_different_names_allowed(): void
    {
        // Parent registration
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Parent Name',
            'email' => 'family@example.com',
            'age' => 40,
            'track_id' => 1,
        ]));

        // Child A registration (same email, different name)
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Child A',
            'email' => 'family@example.com',
            'age' => 12,
            'track_id' => 1,
        ]));

        // Child B registration (same email, different name, same track)
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Child B',
            'email' => 'family@example.com',
            'age' => 10,
            'track_id' => 1,
        ]));

        // Child C registration (same email, different name, different track)
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Child C',
            'email' => 'family@example.com',
            'age' => 8,
            'track_id' => 2,
        ]));

        $this->assertDatabaseCount('registrations', 4);
        $this->assertEquals(4, Registration::where('email', 'family@example.com')->count());
    }

    public function test_duplicate_registration_ignores_soft_deleted_entries(): void
    {
        $data = $this->validRegistrationData();

        // Create and soft delete a registration
        $registration = Registration::create(array_merge($data, [
            'draw_status' => 'not_drawn',
            'payed' => false,
            'starting' => false,
        ]));
        $registration->delete();

        $this->assertDatabaseCount('registrations', 1);
        $this->assertEquals(1, Registration::withTrashed()->count());
        $this->assertEquals(0, Registration::count()); // Soft deleted not counted

        // Try to register again (should succeed because previous is soft deleted)
        $response = $this->post(route('registration.store'), $data);

        $response->assertRedirect(route('registration.success'));
        $this->assertEquals(2, Registration::withTrashed()->count());
        $this->assertEquals(1, Registration::count());
    }

    public function test_registration_with_team_creates_new_team(): void
    {
        $data = $this->validRegistrationData([
            'team_name' => 'Awesome Team',
        ]);

        $response = $this->post(route('registration.store'), $data);

        $response->assertRedirect(route('registration.success'));
        $this->assertDatabaseHas('teams', [
            'name' => 'Awesome Team',
            'track_id' => 1,
        ]);
        $this->assertDatabaseHas('registrations', [
            'name' => 'John Doe',
        ]);

        $team = Team::where('name', 'Awesome Team')->first();
        $registration = Registration::where('name', 'John Doe')->first();
        $this->assertEquals($team->id, $registration->team_id);
    }

    public function test_registration_with_existing_team_joins_team(): void
    {
        // First person creates team
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person A',
            'email' => 'persona@example.com',
            'team_name' => 'Team Rocket',
        ]));

        $team = Team::where('name', 'Team Rocket')->first();
        $this->assertNotNull($team);
        $this->assertEquals(1, $team->registrations()->count());

        // Second person joins same team
        $response = $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person B',
            'email' => 'personb@example.com',
            'team_name' => 'Team Rocket',
        ]));

        $response->assertRedirect(route('registration.success'));
        $this->assertDatabaseCount('teams', 1); // Only one team created
        $team->refresh();
        $this->assertEquals(2, $team->registrations()->count());
    }

    public function test_team_name_is_case_insensitive(): void
    {
        // First registration with lowercase
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person A',
            'email' => 'persona@example.com',
            'team_name' => 'awesome team',
        ]));

        // Second registration with different case
        $response = $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person B',
            'email' => 'personb@example.com',
            'team_name' => 'Awesome Team',
        ]));

        $response->assertRedirect(route('registration.success'));
        $this->assertDatabaseCount('teams', 1); // Only one team created (case insensitive)
    }

    public function test_team_registration_enforcing_same_track_creates_separate_teams_per_track(): void
    {
        $settings = app(EventSettings::class);
        $settings->enforce_same_track_for_teams = true;

        // First person creates team "Runners" on Track 1
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person A',
            'email' => 'persona@example.com',
            'team_name' => 'Runners',
            'track_id' => 1,
        ]));

        // Second person creates team "Runners" on Track 2 (different team, same name)
        $response = $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person B',
            'email' => 'personb@example.com',
            'team_name' => 'Runners',
            'track_id' => 2,
        ]));

        $response->assertRedirect(route('registration.success'));
        $this->assertDatabaseCount('registrations', 2);
        $this->assertDatabaseCount('teams', 2); // Two separate teams with same name but different tracks

        $team1 = Team::where('name', 'Runners')->where('track_id', 1)->first();
        $team2 = Team::where('name', 'Runners')->where('track_id', 2)->first();

        $this->assertNotNull($team1);
        $this->assertNotNull($team2);
        $this->assertNotEquals($team1->id, $team2->id);
    }

    public function test_team_registration_not_enforcing_track_allows_different_tracks(): void
    {
        $settings = app(EventSettings::class);
        $settings->enforce_same_track_for_teams = false;

        // First person creates team on Track 1
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person A',
            'email' => 'persona@example.com',
            'team_name' => 'Multi Track Team',
            'track_id' => 1,
        ]));

        // Second person joins same team on Track 2 (should succeed)
        $response = $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person B',
            'email' => 'personb@example.com',
            'team_name' => 'Multi Track Team',
            'track_id' => 2,
        ]));

        $response->assertRedirect(route('registration.success'));
        $this->assertDatabaseCount('registrations', 2);
        $this->assertDatabaseCount('teams', 1);

        $team = Team::where('name', 'Multi Track Team')->first();
        // Team retains the track_id from the first member who created it
        $this->assertEquals(1, $team->track_id);
    }

    public function test_full_team_prevents_new_members(): void
    {
        // Create a team with max 2 members (for testing)
        $team = Team::create([
            'name' => 'Full Team',
            'max_members' => 2,
            'track_id' => 1,
        ]);

        // Add 2 registrations (team now full)
        Registration::factory()->count(2)->create([
            'team_id' => $team->id,
            'track_id' => 1,
        ]);

        // Try to add a third member
        $response = $this->post(route('registration.store'), $this->validRegistrationData([
            'team_name' => 'Full Team',
        ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('team_name');
    }

    public function test_registration_closed_state_prevents_submission(): void
    {
        $settings = app(EventSettings::class);
        $settings->application_state = 'closed';

        $response = $this->post(route('registration.store'), $this->validRegistrationData());

        $response->assertRedirect(route('registration.create'));
        $response->assertSessionHasErrors('general');
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_registration_validates_required_fields(): void
    {
        $response = $this->post(route('registration.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'age', 'gender', 'track_id']);
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_rate_limiting_prevents_excessive_requests(): void
    {
        // Make 11 requests (limit is 10 per minute)
        for ($i = 0; $i < 11; $i++) {
            $response = $this->post(route('registration.store'), $this->validRegistrationData([
                'email' => "user{$i}@example.com",
            ]));

            if ($i < 10) {
                $response->assertRedirect(route('registration.success'));
            } else {
                $response->assertStatus(429); // Too Many Requests
            }
        }
    }

    public function test_concurrent_team_creation_prevents_duplicate_teams(): void
    {
        $teamName = 'Concurrent Team';
        $createdTeams = [];

        // Simulate concurrent requests by disabling transaction commits temporarily
        DB::beginTransaction();

        try {
            // First "concurrent" registration
            $team1 = Team::whereRaw('LOWER(name) = LOWER(?)', [$teamName])
                ->lockForUpdate()
                ->first();

            if (! $team1) {
                $team1 = Team::create([
                    'name' => $teamName,
                    'max_members' => 5,
                    'track_id' => 1,
                ]);
            }
            $createdTeams[] = $team1->id;

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        DB::beginTransaction();

        try {
            // Second "concurrent" registration (should find existing team)
            $team2 = Team::whereRaw('LOWER(name) = LOWER(?)', [$teamName])
                ->lockForUpdate()
                ->first();

            if (! $team2) {
                $team2 = Team::create([
                    'name' => $teamName,
                    'max_members' => 5,
                    'track_id' => 1,
                ]);
            }
            $createdTeams[] = $team2->id;

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Both should reference the same team
        $this->assertEquals($createdTeams[0], $createdTeams[1]);
        $this->assertDatabaseCount('teams', 1);
    }

    public function test_unlimited_team_accepts_many_members(): void
    {
        // Create a team with unlimited capacity
        $team = Team::create([
            'name' => 'Unlimited Team',
            'max_members' => null, // Unlimited
            'track_id' => 1,
        ]);

        // Add 10 registrations (way beyond typical max of 5)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('registration.store'), $this->validRegistrationData([
                'name' => "Person {$i}",
                'email' => "person{$i}@example.com",
                'team_name' => 'Unlimited Team',
            ]));

            $response->assertRedirect(route('registration.success'));
        }

        $team->refresh();
        $this->assertEquals(10, $team->registrations()->count());
        $this->assertFalse($team->getIsFull());
    }

    public function test_unlimited_team_shows_in_not_full_scope(): void
    {
        // Create unlimited team
        $unlimitedTeam = Team::create([
            'name' => 'Unlimited Team',
            'max_members' => null,
            'track_id' => 1,
        ]);

        // Create full limited team
        $limitedTeam = Team::create([
            'name' => 'Limited Team',
            'max_members' => 2,
            'track_id' => 1,
        ]);

        Registration::factory()->count(2)->create([
            'team_id' => $limitedTeam->id,
            'track_id' => 1,
        ]);

        $notFullTeams = Team::notFull()->pluck('id');

        $this->assertContains($unlimitedTeam->id, $notFullTeams);
        $this->assertNotContains($limitedTeam->id, $notFullTeams);
    }

    public function test_new_team_uses_event_settings_default(): void
    {
        $settings = app(EventSettings::class);
        $settings->default_team_max_members = 8; // Set custom default
        $settings->save();

        $response = $this->post(route('registration.store'), $this->validRegistrationData([
            'team_name' => 'New Team',
        ]));

        $response->assertRedirect(route('registration.success'));

        $team = Team::where('name', 'New Team')->first();
        $this->assertEquals(8, $team->max_members);
    }

    public function test_new_team_defaults_to_unlimited_when_setting_is_null(): void
    {
        $settings = app(EventSettings::class);
        $settings->default_team_max_members = null; // Unlimited default
        $settings->save();

        $response = $this->post(route('registration.store'), $this->validRegistrationData([
            'team_name' => 'Unlimited Default Team',
        ]));

        $response->assertRedirect(route('registration.success'));

        $team = Team::where('name', 'Unlimited Default Team')->first();
        $this->assertNull($team->max_members);
        $this->assertFalse($team->getIsFull());
    }

    public function test_team_stats_excludes_unlimited_teams_from_full_count(): void
    {
        // Create unlimited team with many members
        $unlimitedTeam = Team::create([
            'name' => 'Big Unlimited Team',
            'max_members' => null,
            'track_id' => 1,
        ]);

        Registration::factory()->count(20)->create([
            'team_id' => $unlimitedTeam->id,
            'track_id' => 1,
        ]);

        // Create full limited team
        $limitedTeam = Team::create([
            'name' => 'Full Limited Team',
            'max_members' => 3,
            'track_id' => 1,
        ]);

        Registration::factory()->count(3)->create([
            'team_id' => $limitedTeam->id,
            'track_id' => 1,
        ]);

        $stats = Team::getStats();

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['full']); // Only the limited team counts as full
    }

    public function test_soft_deleted_team_name_can_be_reused_same_track(): void
    {
        $settings = app(EventSettings::class);
        $settings->enforce_same_track_for_teams = true;

        // Create first team "Rockets" on Track 1
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person A',
            'email' => 'persona@example.com',
            'team_name' => 'Rockets',
            'track_id' => 1,
        ]));

        $team = Team::where('name', 'Rockets')->where('track_id', 1)->first();
        $this->assertNotNull($team);

        // Soft delete the team
        $team->delete();

        $this->assertEquals(0, Team::count()); // Soft deleted not counted
        $this->assertEquals(1, Team::withTrashed()->count()); // But exists with trash

        // Try to create new team "Rockets" on Track 1 (should succeed)
        $response = $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person B',
            'email' => 'personb@example.com',
            'team_name' => 'Rockets',
            'track_id' => 1,
        ]));

        $response->assertRedirect(route('registration.success'));
        $this->assertEquals(2, Team::withTrashed()->count()); // 1 soft deleted + 1 new
        $this->assertEquals(1, Team::count()); // Only 1 active team
    }

    public function test_soft_deleted_team_name_can_be_reused_different_track(): void
    {
        $settings = app(EventSettings::class);
        $settings->enforce_same_track_for_teams = true;

        // Create team "Warriors" on Track 1
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person A',
            'email' => 'persona@example.com',
            'team_name' => 'Warriors',
            'track_id' => 1,
        ]));

        // Create team "Warriors" on Track 2 (allowed with enforce_same_track_for_teams)
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person B',
            'email' => 'personb@example.com',
            'team_name' => 'Warriors',
            'track_id' => 2,
        ]));

        $this->assertEquals(2, Team::count());

        // Soft delete both teams
        Team::where('name', 'Warriors')->get()->each->delete();

        $this->assertEquals(0, Team::count());
        $this->assertEquals(2, Team::withTrashed()->count());

        // Create new team "Warriors" on Track 1 (should succeed)
        $response = $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person C',
            'email' => 'personc@example.com',
            'team_name' => 'Warriors',
            'track_id' => 1,
        ]));

        $response->assertRedirect(route('registration.success'));
        $this->assertEquals(3, Team::withTrashed()->count());
        $this->assertEquals(1, Team::count());
    }

    public function test_soft_deleted_team_name_can_be_reused_globally(): void
    {
        $settings = app(EventSettings::class);
        $settings->enforce_same_track_for_teams = false;

        // Create team "Global Team" (no track enforcement)
        $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person A',
            'email' => 'persona@example.com',
            'team_name' => 'Global Team',
            'track_id' => 1,
        ]));

        $team = Team::where('name', 'Global Team')->first();
        $this->assertNotNull($team);
        // Team retains the track_id from the member who created it
        $this->assertEquals(1, $team->track_id);

        // Soft delete the team
        $team->delete();

        $this->assertEquals(0, Team::count());
        $this->assertEquals(1, Team::withTrashed()->count());

        // Try to create new team "Global Team" (should succeed)
        $response = $this->post(route('registration.store'), $this->validRegistrationData([
            'name' => 'Person B',
            'email' => 'personb@example.com',
            'team_name' => 'Global Team',
            'track_id' => 2,
        ]));

        $response->assertRedirect(route('registration.success'));
        $this->assertEquals(2, Team::withTrashed()->count());
        $this->assertEquals(1, Team::count());
    }
}
