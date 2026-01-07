<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Track;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Steppenwolf 2025 Event
        $steppenwolf2025 = Event::create([
            'name' => 'Steppenwolf 2025',
            'slug' => 'steppenwolf-2025',
            'status' => 'active',
            'description' => 'Annual Steppenwolf cycling event',
            'registration_opens_at' => now()->addDays(7),
            'registration_closes_at' => now()->addDays(30),
            'event_date' => now()->addDays(60),
        ]);

        // Create tracks for Steppenwolf 2025
        Track::create([
            'event_id' => $steppenwolf2025->id,
            'name' => '50km Classic',
            'slug' => '50km-classic',
            'description' => 'Perfect for beginners and intermediate riders',
            'capacity' => 100,
            'status' => 'open',
            'sort_order' => 1,
        ]);

        Track::create([
            'event_id' => $steppenwolf2025->id,
            'name' => '100km Challenge',
            'slug' => '100km-challenge',
            'description' => 'For experienced cyclists looking for a challenge',
            'capacity' => 80,
            'status' => 'open',
            'sort_order' => 2,
        ]);

        Track::create([
            'event_id' => $steppenwolf2025->id,
            'name' => '30km Family',
            'slug' => '30km-family',
            'description' => 'Family-friendly route with scenic views',
            'capacity' => 150,
            'status' => 'open',
            'sort_order' => 3,
        ]);

        // Create Steppenwolf 2026 Event (draft)
        $steppenwolf2026 = Event::create([
            'name' => 'Steppenwolf 2026',
            'slug' => 'steppenwolf-2026',
            'status' => 'draft',
            'description' => 'Planning for next year\'s event',
            'event_date' => now()->addYear(),
        ]);

        // Create tracks for Steppenwolf 2026
        Track::create([
            'event_id' => $steppenwolf2026->id,
            'name' => '50km Route',
            'slug' => '50km-route',
            'capacity' => 120,
            'status' => 'draft',
            'sort_order' => 1,
        ]);

        Track::create([
            'event_id' => $steppenwolf2026->id,
            'name' => '100km Route',
            'slug' => '100km-route',
            'capacity' => 100,
            'status' => 'draft',
            'sort_order' => 2,
        ]);
    }
}
