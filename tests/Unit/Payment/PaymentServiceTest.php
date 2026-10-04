<?php

namespace Tests\Unit\Payment;

use App\Domain\Payment\Services\PaymentService;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_unpaid_without_payment_row(): void
    {
        $registration = Registration::factory()->create();

        $this->assertFalse($registration->is_paid);
        $this->assertCount(0, Registration::paid()->get());
        $this->assertCount(1, Registration::unpaid()->get());
    }

    public function test_mark_paid_creates_status_row(): void
    {
        $registration = Registration::factory()->create();

        app(PaymentService::class)->markPaid($registration);

        $registration->refresh();

        $this->assertTrue($registration->is_paid);
        $this->assertTrue($registration->paymentStatus->paid);
        $this->assertNotNull($registration->paymentStatus->paid_at);
        $this->assertEquals('manual', $registration->paymentStatus->paid_via);
    }

    public function test_mark_paid_is_idempotent(): void
    {
        $registration = Registration::factory()->paid()->create();

        $before = $registration->paymentStatus->paid_at;

        app(PaymentService::class)->markPaid($registration);

        $this->assertEquals($before, $registration->paymentStatus->paid_at);
        $this->assertCount(1, $registration->paymentStatus()->get());
    }

    public function test_mark_unpaid_clears_state(): void
    {
        $registration = Registration::factory()->paid()->create();

        app(PaymentService::class)->markUnpaid($registration);

        $this->assertFalse($registration->is_paid);
        $this->assertNull($registration->paymentStatus->paid_via);
        $this->assertNull($registration->paymentStatus->paid_at);
    }

    public function test_paid_and_unpaid_scopes(): void
    {
        $paid = Registration::factory()->paid()->create();
        Registration::factory()->create();

        $this->assertTrue(Registration::paid()->get()->pluck('id')->contains($paid->id));
        $this->assertCount(1, Registration::paid()->get());
        $this->assertCount(1, Registration::unpaid()->get());
    }
}
