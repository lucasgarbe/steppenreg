<?php

namespace App\Domain\StartingNumber\Tests\Unit;

use App\Domain\StartingNumber\Services\StartingNumberService;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StartingNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StartingNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StartingNumberService::class);
    }

    public function test_service_can_be_resolved(): void
    {
        $this->assertInstanceOf(StartingNumberService::class, $this->service);
    }

    public function test_assigns_number_to_registration(): void
    {
        $registration = Registration::factory()->create([
            'starting_number' => null,
        ]);

        $number = $this->service->assignNumber($registration);

        $this->assertNotNull($number);
        $this->assertIsInt($number);
    }

    public function test_respects_feature_toggle(): void
    {
        config(['steppenreg.features.starting_numbers' => false]);

        $registration = Registration::factory()->create([
            'starting_number' => null,
        ]);

        $number = $this->service->assignNumber($registration);

        // Should not assign when disabled
        $this->assertNull($number);
    }

    public function test_does_not_reassign_existing_number(): void
    {
        $registration = Registration::factory()->create([
            'starting_number' => 42,
        ]);

        $number = $this->service->assignNumber($registration);

        $this->assertEquals(42, $number);
    }
}
