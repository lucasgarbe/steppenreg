<?php

namespace Database\Seeders;

use App\Settings\EventSettings;
use Illuminate\Database\Seeder;

class EventSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = app(EventSettings::class);

        // Set event configuration
        $settings->event_name = 'Steppenreg';
        $settings->site_active = true;

        // Define cycling tracks with realistic distances and participant limits
        $cyclingTracks = [
            [
                'id' => 1,
                'name' => '150km',
                'distance' => 150,
                'max_participants' => 150,
            ],
            [
                'id' => 2,
                'name' => '200km',
                'distance' => 200,
                'max_participants' => 150,
            ],
            [
                'id' => 3,
                'name' => '300km',
                'distance' => 300,
                'max_participants' => 100,
            ],
            [
                'id' => 4,
                'name' => 'Handbike',
                'distance' => 150,
                'max_participants' => 150,
            ],
        ];

        // Only set tracks if they haven't been customized yet
        if (empty($settings->tracks)) {
            $settings->tracks = $cyclingTracks;
            $this->command->info('Seeded cycling tracks: ' . count($cyclingTracks) . ' routes created');
        } else {
            $this->command->warn('Event tracks already exist - skipping track seeding to preserve customizations');
        }

        $settings->save();

        $this->command->info('Event settings configured: ' . $settings->event_name);
        $this->command->info('Site status: ' . ($settings->site_active ? 'ACTIVE' : 'INACTIVE'));
        $this->command->info('Total tracks available: ' . count($settings->tracks));
    }
}

