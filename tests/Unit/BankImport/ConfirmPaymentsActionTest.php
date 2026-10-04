<?php

namespace Tests\Unit\BankImport;

use App\Domain\BankImport\Actions\ConfirmPaymentsAction;
use App\Domain\BankImport\Exceptions\InvalidRegistrationIdsException;
use App\Domain\BankImport\Models\BankTransferConfirmation;
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

    public function test_marks_given_registrations_as_paid(): void
    {
        $reg1 = Registration::factory()->create();
        $reg2 = Registration::factory()->create();

        $result = $this->action->execute([$reg1->id, $reg2->id]);

        $this->assertDatabaseHas('payment_statuses', ['registration_id' => $reg1->id, 'paid' => true]);
        $this->assertDatabaseHas('payment_statuses', ['registration_id' => $reg2->id, 'paid' => true]);
        $this->assertEquals(2, $result->newly_confirmed);
        $this->assertEquals(0, $result->already_paid);
    }

    public function test_records_fingerprints_in_confirmation_ledger(): void
    {
        $reg1 = Registration::factory()->create();
        $reg2 = Registration::factory()->create();

        $result = $this->action->execute(
            [$reg1->id, $reg2->id],
            [$reg1->id => 'fp-aaa', $reg2->id => 'fp-bbb'],
        );

        $this->assertDatabaseHas('bank_transfer_confirmations', ['registration_id' => $reg1->id, 'fingerprint' => 'fp-aaa']);
        $this->assertDatabaseHas('bank_transfer_confirmations', ['registration_id' => $reg2->id, 'fingerprint' => 'fp-bbb']);
    }

    public function test_fingerprints_are_recorded_without_duplicates(): void
    {
        $reg = Registration::factory()->create();

        $this->action->execute([$reg->id], [$reg->id => 'fp-aaa']);
        $this->action->execute([$reg->id], [$reg->id => 'fp-aaa']);

        $this->assertDatabaseCount('bank_transfer_confirmations', 1);
    }

    public function test_confirmation_ledger_maps_fingerprints_by_registration_id(): void
    {
        // Fingerprints must land on the registration they refer to, regardless of order
        $regA = Registration::factory()->create();
        $regB = Registration::factory()->create();

        $this->action->execute(
            [$regA->id, $regB->id],
            [$regA->id => 'fp-a', $regB->id => 'fp-b'],
        );

        $this->assertEquals($regA->id, BankTransferConfirmation::where('fingerprint', 'fp-a')->first()?->registration_id);
        $this->assertEquals($regB->id, BankTransferConfirmation::where('fingerprint', 'fp-b')->first()?->registration_id);
    }

    public function test_is_idempotent_for_already_paid_registrations(): void
    {
        $reg = Registration::factory()->paid()->create();

        $result = $this->action->execute([$reg->id]);

        $this->assertDatabaseHas('payment_statuses', ['registration_id' => $reg->id, 'paid' => true]);
        $this->assertEquals(0, $result->newly_confirmed);
        $this->assertEquals(1, $result->already_paid);
    }

    public function test_returns_correct_mixed_counts_for_mixed_paid_state(): void
    {
        $unpaid = Registration::factory()->create();
        $paid = Registration::factory()->paid()->create();

        $result = $this->action->execute([$unpaid->id, $paid->id]);

        $this->assertEquals(1, $result->newly_confirmed);
        $this->assertEquals(1, $result->already_paid);
    }

    public function test_throws_on_nonexistent_registration_id(): void
    {
        $this->expectException(InvalidRegistrationIdsException::class);

        $this->action->execute([999999]);
    }

    public function test_throws_when_any_id_in_list_is_invalid(): void
    {
        $valid = Registration::factory()->create();

        $this->expectException(InvalidRegistrationIdsException::class);

        $this->action->execute([$valid->id, 999999]);
    }

    public function test_excludes_soft_deleted_registrations(): void
    {
        $reg = Registration::factory()->create();
        $reg->delete();

        $this->expectException(InvalidRegistrationIdsException::class);

        $this->action->execute([$reg->id]);
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

        $this->action->execute([$target->id]);

        $this->assertDatabaseHas('payment_statuses', ['registration_id' => $target->id, 'paid' => true]);
        // Unregistered bystanders have no payment row at all
        $this->assertDatabaseMissing('payment_statuses', ['registration_id' => $bystander->id]);
    }
}
