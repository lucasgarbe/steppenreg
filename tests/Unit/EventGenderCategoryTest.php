<?php

namespace Tests\Unit;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventGenderCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_returns_default_gender_categories(): void
    {
        $event = Event::factory()->create([
            'settings' => null,
        ]);

        $categories = $event->getGenderCategories();

        $this->assertIsArray($categories);
        $this->assertArrayHasKey('flinta', $categories);
        $this->assertArrayHasKey('all_gender', $categories);
        $this->assertTrue($categories['flinta']['enabled']);
        $this->assertTrue($categories['all_gender']['enabled']);
    }

    public function test_event_returns_configured_gender_categories(): void
    {
        $event = Event::factory()->create([
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'label' => 'FLINTA*',
                        'registration_opens_at' => '2026-03-01 10:00:00',
                    ],
                    'all_gender' => [
                        'enabled' => false,
                        'label' => 'Open/All Gender',
                        'registration_opens_at' => '2026-03-08 10:00:00',
                    ],
                ],
            ],
        ]);

        $categories = $event->getGenderCategories();

        $this->assertTrue($categories['flinta']['enabled']);
        $this->assertFalse($categories['all_gender']['enabled']);
        $this->assertEquals('2026-03-01 10:00:00', $categories['flinta']['registration_opens_at']);
    }

    public function test_gender_category_is_not_open_when_disabled(): void
    {
        $event = Event::factory()->create([
            'status' => 'active',
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addMonth(),
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => false,
                        'registration_opens_at' => now()->subHour(),
                    ],
                ],
            ],
        ]);

        $this->assertFalse($event->isGenderCategoryOpen('flinta'));
    }

    public function test_gender_category_is_not_open_before_event_registration_opens(): void
    {
        $event = Event::factory()->create([
            'status' => 'active',
            'registration_opens_at' => now()->addDay(),
            'registration_closes_at' => now()->addMonth(),
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'registration_opens_at' => now()->subHour(),
                    ],
                ],
            ],
        ]);

        $this->assertFalse($event->isGenderCategoryOpen('flinta'));
    }

    public function test_gender_category_is_not_open_after_event_registration_closes(): void
    {
        $event = Event::factory()->create([
            'status' => 'active',
            'registration_opens_at' => now()->subWeek(),
            'registration_closes_at' => now()->subDay(),
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'registration_opens_at' => now()->subWeek(),
                    ],
                ],
            ],
        ]);

        $this->assertFalse($event->isGenderCategoryOpen('flinta'));
    }

    public function test_gender_category_is_not_open_before_gender_specific_date(): void
    {
        $event = Event::factory()->create([
            'status' => 'active',
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addMonth(),
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'registration_opens_at' => now()->addDay()->toDateTimeString(),
                    ],
                ],
            ],
        ]);

        $this->assertFalse($event->isGenderCategoryOpen('flinta'));
    }

    public function test_gender_category_is_open_when_all_conditions_met(): void
    {
        $event = Event::factory()->create([
            'status' => 'active',
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addMonth(),
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'registration_opens_at' => now()->subHour()->toDateTimeString(),
                    ],
                ],
            ],
        ]);

        $this->assertTrue($event->isGenderCategoryOpen('flinta'));
    }

    public function test_gender_category_is_open_when_no_gender_specific_date(): void
    {
        $event = Event::factory()->create([
            'status' => 'active',
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addMonth(),
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'registration_opens_at' => null,
                    ],
                ],
            ],
        ]);

        $this->assertTrue($event->isGenderCategoryOpen('flinta'));
    }

    public function test_get_gender_category_opening_date_returns_null_when_not_set(): void
    {
        $event = Event::factory()->create([
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'registration_opens_at' => null,
                    ],
                ],
            ],
        ]);

        $this->assertNull($event->getGenderCategoryOpeningDate('flinta'));
    }

    public function test_get_gender_category_opening_date_returns_carbon_instance(): void
    {
        $dateTime = '2026-03-01 10:00:00';
        $event = Event::factory()->create([
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'registration_opens_at' => $dateTime,
                    ],
                ],
            ],
        ]);

        $result = $event->getGenderCategoryOpeningDate('flinta');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals($dateTime, $result->toDateTimeString());
    }

    public function test_get_available_gender_categories_returns_only_open_categories(): void
    {
        $event = Event::factory()->create([
            'status' => 'active',
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addMonth(),
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'label' => 'FLINTA*',
                        'registration_opens_at' => now()->subHour()->toDateTimeString(),
                    ],
                    'all_gender' => [
                        'enabled' => true,
                        'label' => 'Open/All Gender',
                        'registration_opens_at' => now()->addWeek()->toDateTimeString(),
                    ],
                ],
            ],
        ]);

        $available = $event->getAvailableGenderCategories();

        $this->assertArrayHasKey('flinta', $available);
        $this->assertArrayNotHasKey('all_gender', $available);
    }

    public function test_get_next_gender_category_opening_returns_null_when_all_open(): void
    {
        $event = Event::factory()->create([
            'status' => 'active',
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addMonth(),
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'registration_opens_at' => now()->subHour()->toDateTimeString(),
                    ],
                    'all_gender' => [
                        'enabled' => true,
                        'registration_opens_at' => now()->subHour()->toDateTimeString(),
                    ],
                ],
            ],
        ]);

        $this->assertNull($event->getNextGenderCategoryOpening());
    }

    public function test_get_next_gender_category_opening_returns_nearest_future_opening(): void
    {
        $event = Event::factory()->create([
            'status' => 'active',
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addMonth(),
            'settings' => [
                'gender_categories' => [
                    'flinta' => [
                        'enabled' => true,
                        'label' => 'FLINTA*',
                        'registration_opens_at' => now()->addDays(2)->toDateTimeString(),
                    ],
                    'all_gender' => [
                        'enabled' => true,
                        'label' => 'Open/All Gender',
                        'registration_opens_at' => now()->addWeek()->toDateTimeString(),
                    ],
                ],
            ],
        ]);

        $next = $event->getNextGenderCategoryOpening();

        $this->assertNotNull($next);
        $this->assertEquals('flinta', $next['gender']);
        $this->assertEquals('FLINTA*', $next['label']);
        $this->assertInstanceOf(Carbon::class, $next['datetime']);
    }
}
