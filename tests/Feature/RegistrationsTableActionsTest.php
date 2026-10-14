<?php

namespace Tests\Feature;

use App\Domain\Mail\Jobs\SendFlexibleMail;
use App\Domain\Payment\Services\PaymentService;
use App\Filament\Resources\Registrations\Pages\ListRegistrations;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationsTableActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_toggling_paid_on_marks_registration_paid_manually(): void
    {
        $registration = Registration::factory()->create();

        Livewire::test(ListRegistrations::class)
            ->call('updateTableColumnState', 'paymentStatus.paid', (string) $registration->getKey(), true);

        $paymentStatus = $registration->fresh()->paymentStatus;
        $this->assertTrue($paymentStatus->paid);
        $this->assertSame(PaymentService::VIA_MANUAL, $paymentStatus->paid_via);
        $this->assertNotNull($paymentStatus->paid_at);
    }

    public function test_toggling_paid_off_clears_payment_provenance(): void
    {
        $registration = Registration::factory()->paid()->create();

        Livewire::test(ListRegistrations::class)
            ->call('updateTableColumnState', 'paymentStatus.paid', (string) $registration->getKey(), false);

        $paymentStatus = $registration->fresh()->paymentStatus;
        $this->assertFalse($paymentStatus->paid);
        $this->assertNull($paymentStatus->paid_via);
        $this->assertNull($paymentStatus->paid_at);
    }

    public function test_toggling_starting_updates_registration(): void
    {
        $registration = Registration::factory()->create(['starting' => false]);

        Livewire::test(ListRegistrations::class)
            ->call('updateTableColumnState', 'starting', (string) $registration->getKey(), true);

        $this->assertTrue($registration->fresh()->starting);

        Livewire::test(ListRegistrations::class)
            ->call('updateTableColumnState', 'starting', (string) $registration->getKey(), false);

        $this->assertFalse($registration->fresh()->starting);
    }

    public function test_bulk_mark_as_unpaid_clears_payment(): void
    {
        $registrations = Registration::factory()->paid()->count(2)->create();

        Livewire::test(ListRegistrations::class)
            ->callTableBulkAction('mark_as_unpaid', $registrations);

        $registrations->each(fn (Registration $registration) => $this->assertFalse($registration->fresh()->is_paid));
    }

    public function test_bulk_mark_as_not_starting_updates_registrations(): void
    {
        $registrations = Registration::factory()->count(2)->create(['starting' => true]);

        Livewire::test(ListRegistrations::class)
            ->callTableBulkAction('mark_as_not_starting', $registrations);

        $registrations->each(fn (Registration $registration) => $this->assertFalse($registration->fresh()->starting));
    }

    public function test_bulk_mark_as_arrived_sets_finish_time(): void
    {
        $registrations = Registration::factory()->count(2)->create(['finish_time' => null]);

        Livewire::test(ListRegistrations::class)
            ->callTableBulkAction('mark_as_arrived', $registrations);

        $registrations->each(fn (Registration $registration) => $this->assertNotNull($registration->fresh()->finish_time));
    }

    public function test_bulk_mark_as_dnf_sets_zero_finish_time(): void
    {
        $registrations = Registration::factory()->count(2)->create(['finish_time' => null]);

        Livewire::test(ListRegistrations::class)
            ->callTableBulkAction('mark_as_dnf', $registrations);

        $registrations->each(fn (Registration $registration) => $this->assertStringStartsWith('00:00', (string) $registration->fresh()->getRawOriginal('finish_time')));
    }

    public function test_row_send_custom_email_queues_mail(): void
    {
        Queue::fake();
        $registration = Registration::factory()->create();

        Livewire::test(ListRegistrations::class)
            ->callTableAction('send_custom_email', $registration, [
                'subject' => 'Hello',
                'message' => '<p>Hi {{name}}</p>',
            ])
            ->assertHasNoTableActionErrors();

        Queue::assertPushed(SendFlexibleMail::class, 1);
    }

    public function test_row_delete_soft_deletes_registration(): void
    {
        $registration = Registration::factory()->create();

        Livewire::test(ListRegistrations::class)
            ->callTableAction('delete', $registration);

        $this->assertSoftDeleted($registration);
    }

    public function test_row_mark_as_paid_and_starting_actions_are_removed(): void
    {
        Livewire::test(ListRegistrations::class)
            ->assertTableActionDoesNotExist('mark_as_paid')
            ->assertTableActionDoesNotExist('mark_as_starting');
    }
}
