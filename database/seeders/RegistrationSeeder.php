<?php

namespace Database\Seeders;

use App\Models\Registration;
use App\Settings\EventSettings;
use Illuminate\Database\Seeder;

class RegistrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        Registration::truncate();

        // Create various types of registrations
        Registration::factory(50)->create();
        Registration::factory(5)->payed()->create();
        Registration::factory(8)->starting()->create();
        Registration::factory(3)->finished()->create();

        $this->command->info('Created ' . Registration::count() . ' sample registrations');
        $this->command->table(
            ['Status', 'Count'],
            [
                ['Total', Registration::count()],
                ['Paid', Registration::payed()->count()],
                ['Starting', Registration::starting()->count()],
                ['Finished', Registration::finished()->count()],
            ]
        );

        // Show registrations by track
        $settings = app(EventSettings::class);
        if (!empty($settings->tracks)) {
            $this->command->info('Registrations by Track:');
            $trackStats = [];
            foreach ($settings->tracks as $track) {
                $count = Registration::where('track_id', $track['id'])->count();
                $trackStats[] = [$track['name'], $count . ' participants'];
            }
            $this->command->table(['Track', 'Registrations'], $trackStats);
        }
    }
}
