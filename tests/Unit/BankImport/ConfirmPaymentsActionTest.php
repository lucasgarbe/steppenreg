<?php

namespace Tests\Unit\BankImport;

use App\Domain\BankImport\Actions\ConfirmPaymentsAction;
use App\Domain\BankImport\Exceptions\InvalidRegistrationIdsException;
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

    public function test_marks_given_registrations_as_payed(): void
    {
        $reg1 = Registration::factory()->create(['payed' => false]);
        $reg2 = Registration::factory()->create(['payed' => false]);

        $result = $this->action->execute([$reg1->id, $reg2->id]);

        $this->assertDatabaseHas('registrations', ['id' => $reg1->id, 'payed' => true]);
        $this->assertDatabaseHas('registrations', ['id' => $reg2->id, 'payed' => true]);
        $this->assertEquals(2, $result->newly_confirmed);
        $this->assertEquals(0, $result->already_payed);
    }

    public function test_is_idempotent_for_already_payed_registrations(): void
    {
        $reg = Registration::factory()->create(['payed' => true]);

        $result = $this->action->execute([$reg->id]);

        $this->assertDatabaseHas('registrations', ['id' => $reg->id, 'payed' => true]);
        $this->assertEquals(0, $result->newly_confirmed);
        $this->assertEquals(1, $result->already_payed);
    }

    public function test_returns_correct_mixed_counts_for_mixed_payed_state(): void
    {
        $unpayed = Registration::factory()->create(['payed' => false]);
        $payed = Registration::factory()->create(['payed' => true]);

        $result = $this->action->execute([$unpayed->id, $payed->id]);

        $this->assertEquals(1, $result->newly_confirmed);
        $this->assertEquals(1, $result->already_payed);
    }

    public function test_throws_on_nonexistent_registration_id(): void
    {
        $this->expectException(InvalidRegistrationIdsException::class);

        $this->action->execute([999999]);
    }

    public function test_throws_when_any_id_in_list_is_invalid(): void
    {
        $valid = Registration::factory()->create(['payed' => false]);

        $this->expectException(InvalidRegistrationIdsException::class);

        $this->action->execute([$valid->id, 999999]);
    }

    public function test_excludes_soft_deleted_registrations(): void
    {
        $reg = Registration::factory()->create(['payed' => false]);
        $reg->delete();

        $this->expectException(InvalidRegistrationIdsException::class);

        $this->action->execute([$reg->id]);
    }

    public function test_handles_empty_array_gracefully(): void
    {
        $result = $this->action->execute([]);

        $this->assertEquals(0, $result->newly_confirmed);
        $this->assertEquals(0, $result->already_payed);
    }

    public function test_does_not_affect_registrations_not_in_the_list(): void
    {
        $target = Registration::factory()->create(['payed' => false]);
        $bystander = Registration::factory()->create(['payed' => false]);

        $this->action->execute([$target->id]);

        $this->assertDatabaseHas('registrations', ['id' => $bystander->id, 'payed' => false]);
    }
}
