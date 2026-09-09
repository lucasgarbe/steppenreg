<?php

namespace Tests\Feature\StartingNumber;

use App\Domain\Draw\Events\RegistrationDrawn;
use App\Domain\StartingNumber\Services\StartingNumberService;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StartingNumberDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['steppenreg.features.starting_numbers' => false]);
    }

    public function test_registration_drawn_does_not_assign_a_starting_number(): void
    {
        $registration = Registration::factory()->create()->fresh();

        event(new RegistrationDrawn($registration));

        $this->assertNull($registration->fresh()->starting_number);
    }

    public function test_service_returns_null_when_disabled(): void
    {
        $registration = Registration::factory()->create(['starting_number' => null]);

        $number = app(StartingNumberService::class)->assignNumber($registration);

        $this->assertNull($number);
        $this->assertNull($registration->fresh()->starting_number);
    }

    public function test_listener_is_not_registered_when_disabled(): void
    {
        $listeners = Event::getListeners(RegistrationDrawn::class);

        $assigners = array_filter($listeners, function ($listener) {
            $class = is_array($listener) && count($listener) === 2 ? $listener[0] : $listener;

            return is_string($class)
                && str_contains($class, 'AssignStartingNumberOnDrawn');
        });

        $this->assertSame([], $assigners);
    }
}
