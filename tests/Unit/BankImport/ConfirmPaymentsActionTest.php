<?php

namespace Tests\Unit\BankImport;

use App\Domain\BankImport\Actions\ConfirmPaymentsAction;
use App\Domain\BankImport\Exceptions\InvalidRegistrationIdsException;
use App\Domain\BankImport\Models\BankTransferConfirmation;
use App\Domain\Payment\Models\PaymentStatus;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmPaymentsActionTest extends TestCase
{
    use RefreshDatabase;

    private ConfirmPaymentsAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ConfirmPaymentsAction;
    }

    /**
     * @return array{registration_id: int, fingerprint: string|null}
     */
    private function transfer(Registration $registration, ?string $fingerprint = null): array
    {
        return ['registration_id' => $registration->id, 'fingerprint' => $fingerprint];
    }

    public function test_marks_given_registrations_as_paid(): void
    {
        $reg1 = Registration::factory()->create();
        $reg2 = Registration::factory()->create();

        $result = $this->action->execute([$this->transfer($reg1), $this->transfer($reg2)]);

        $this->assertDatabaseHas('payment_statuses', ['registration_id' => $reg1->id, 'paid' => true]);
        $this->assertDatabaseHas('payment_statuses', ['registration_id' => $reg2->id, 'paid' => true]);
        $this->assertEquals(2, $result->newly_confirmed);
        $this->assertEquals(0, $result->already_paid);
    }

    public function test_records_fingerprints_in_confirmation_ledger(): void
    {
        $reg1 = Registration::factory()->create();
        $reg2 = Registration::factory()->create();

        $result = $this->action->execute([
            $this->transfer($reg1, 'fp-aaa'),
            $this->transfer($reg2, 'fp-bbb'),
        ]);

        $this->assertDatabaseHas('bank_transfer_confirmations', ['registration_id' => $reg1->id, 'fingerprint' => 'fp-aaa']);
        $this->assertDatabaseHas('bank_transfer_confirmations', ['registration_id' => $reg2->id, 'fingerprint' => 'fp-bbb']);
        $this->assertEqualsCanonicalizing(['fp-aaa', 'fp-bbb'], $result->booked_fingerprints);
    }

    public function test_reconfirming_the_same_transfer_is_idempotent(): void
    {
        $reg = Registration::factory()->create();

        $this->action->execute([$this->transfer($reg, 'fp-aaa')]);
        $result = $this->action->execute([$this->transfer($reg, 'fp-aaa')]);

        $this->assertDatabaseCount('bank_transfer_confirmations', 1);
        $this->assertEquals(['fp-aaa'], $result->booked_fingerprints);
        $this->assertEquals(0, $result->already_paid);
    }

    public function test_confirmation_ledger_maps_fingerprints_to_their_registration(): void
    {
        $regA = Registration::factory()->create();
        $regB = Registration::factory()->create();

        $this->action->execute([
            $this->transfer($regB, 'fp-b'),
            $this->transfer($regA, 'fp-a'),
        ]);

        $this->assertEquals($regA->id, BankTransferConfirmation::where('fingerprint', 'fp-a')->first()?->registration_id);
        $this->assertEquals($regB->id, BankTransferConfirmation::where('fingerprint', 'fp-b')->first()?->registration_id);
    }

    public function test_second_transfer_for_the_same_registration_in_one_batch_is_not_booked(): void
    {
        $reg = Registration::factory()->create();

        $result = $this->action->execute([
            $this->transfer($reg, 'fp-first'),
            $this->transfer($reg, 'fp-second'),
        ]);

        $this->assertEquals(['fp-first'], $result->booked_fingerprints);
        $this->assertEquals(1, $result->newly_confirmed);
        $this->assertEquals(1, $result->already_paid);
        $this->assertDatabaseMissing('bank_transfer_confirmations', ['fingerprint' => 'fp-second']);
    }

    public function test_transfer_for_registration_with_another_booked_transfer_is_not_booked(): void
    {
        $reg = Registration::factory()->create();
        $this->action->execute([$this->transfer($reg, 'fp-first')]);

        $result = $this->action->execute([$this->transfer($reg, 'fp-second')]);

        $this->assertEquals([], $result->booked_fingerprints);
        $this->assertEquals(1, $result->already_paid);
        $this->assertDatabaseMissing('bank_transfer_confirmations', ['fingerprint' => 'fp-second']);
    }

    public function test_links_transfer_to_registration_paid_without_a_booked_transfer(): void
    {
        $reg = Registration::factory()->paid()->create();
        $paidVia = PaymentStatus::where('registration_id', $reg->id)->value('paid_via');

        $result = $this->action->execute([$this->transfer($reg, 'fp-transfer')]);

        $this->assertEquals(['fp-transfer'], $result->booked_fingerprints);
        $this->assertEquals(1, $result->linked);
        $this->assertEquals(0, $result->newly_confirmed);
        $this->assertDatabaseHas('bank_transfer_confirmations', ['registration_id' => $reg->id, 'fingerprint' => 'fp-transfer']);
        $this->assertEquals($paidVia, PaymentStatus::where('registration_id', $reg->id)->value('paid_via'));
    }

    public function test_is_idempotent_for_already_paid_registrations_without_fingerprint(): void
    {
        $reg = Registration::factory()->paid()->create();

        $result = $this->action->execute([$this->transfer($reg)]);

        $this->assertDatabaseHas('payment_statuses', ['registration_id' => $reg->id, 'paid' => true]);
        $this->assertEquals(0, $result->newly_confirmed);
        $this->assertEquals(1, $result->already_paid);
    }

    public function test_returns_correct_mixed_counts_for_mixed_paid_state(): void
    {
        $unpaid = Registration::factory()->create();
        $paid = Registration::factory()->paid()->create();

        $result = $this->action->execute([$this->transfer($unpaid), $this->transfer($paid)]);

        $this->assertEquals(1, $result->newly_confirmed);
        $this->assertEquals(1, $result->already_paid);
    }

    public function test_throws_on_nonexistent_registration_id(): void
    {
        $this->expectException(InvalidRegistrationIdsException::class);

        $this->action->execute([['registration_id' => 999999, 'fingerprint' => null]]);
    }

    public function test_throws_when_any_id_in_list_is_invalid(): void
    {
        $valid = Registration::factory()->create();

        $this->expectException(InvalidRegistrationIdsException::class);

        $this->action->execute([$this->transfer($valid), ['registration_id' => 999999, 'fingerprint' => null]]);
    }

    public function test_excludes_soft_deleted_registrations(): void
    {
        $reg = Registration::factory()->create();
        $reg->delete();

        $this->expectException(InvalidRegistrationIdsException::class);

        $this->action->execute([$this->transfer($reg)]);
    }

    public function test_handles_empty_array_gracefully(): void
    {
        $result = $this->action->execute([]);

        $this->assertEquals(0, $result->newly_confirmed);
        $this->assertEquals(0, $result->already_paid);
    }

    public function test_does_not_affect_registrations_not_in_the_list(): void
    {
        $target = Registration::factory()->create();
        $bystander = Registration::factory()->create();

        $this->action->execute([$this->transfer($target)]);

        $this->assertDatabaseHas('payment_statuses', ['registration_id' => $target->id, 'paid' => true]);
        // Unregistered bystanders have no payment row at all
        $this->assertDatabaseMissing('payment_statuses', ['registration_id' => $bystander->id]);
    }
}
