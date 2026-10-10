<?php

namespace Tests\Feature\BankImport;

use App\Domain\BankImport\Filament\Pages\ManageBankImport;
use App\Domain\BankImport\Models\BankTransferConfirmation;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageBankImportPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_page_requires_authentication(): void
    {
        $this->get('/admin/bank-import')
            ->assertRedirect('/admin/login');
    }

    public function test_page_renders_for_authenticated_user(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/bank-import')
            ->assertOk();
    }

    public function test_mount_populates_registrations_with_all_non_deleted_registrations(): void
    {
        $active1 = Registration::factory()->create();
        $active2 = Registration::factory()->paid()->create();
        $deleted = Registration::factory()->create();
        $deleted->delete();

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);

        $registrations = $component->get('registrations');

        $ids = array_column($registrations, 'id');
        $this->assertContains($active1->id, $ids);
        $this->assertContains($active2->id, $ids);
        $this->assertNotContains($deleted->id, $ids);
    }

    public function test_registrations_property_contains_required_fields(): void
    {
        Registration::factory()->create();

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);

        $registrations = $component->get('registrations');
        $this->assertNotEmpty($registrations);

        $first = $registrations[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('paid', $first);
        $this->assertArrayHasKey('draw_status', $first);
        $this->assertArrayHasKey('track_name', $first);
    }

    public function test_confirm_payments_marks_registrations_as_paid(): void
    {
        $reg = Registration::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ManageBankImport::class)
            ->call('confirmPayments', [['registration_id' => $reg->id, 'fingerprint' => 'fp-a']]);

        $this->assertDatabaseHas('payment_statuses', ['registration_id' => $reg->id, 'paid' => true]);
    }

    public function test_confirm_payments_with_empty_array_does_nothing_gracefully(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ManageBankImport::class)
            ->call('confirmPayments', [])
            ->assertHasNoErrors();
    }

    public function test_confirm_payments_with_invalid_id_sends_error_notification(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ManageBankImport::class)
            ->call('confirmPayments', [['registration_id' => 999999, 'fingerprint' => 'fp-a']])
            ->assertNotified();
    }

    public function test_confirm_payments_for_duplicate_transfer_sends_notification(): void
    {
        $reg = Registration::factory()->create();
        BankTransferConfirmation::create(['registration_id' => $reg->id, 'fingerprint' => 'fp-first']);

        Livewire::actingAs($this->admin)
            ->test(ManageBankImport::class)
            ->call('confirmPayments', [['registration_id' => $reg->id, 'fingerprint' => 'fp-second']])
            ->assertNotified();
    }

    public function test_confirm_payments_with_malformed_transfers_books_nothing(): void
    {
        $reg = Registration::factory()->create();

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);
        $result = $component->instance()->confirmPayments([['id' => $reg->id], 'garbage']);

        $this->assertEquals([], $result['booked_fingerprints']);
        $this->assertDatabaseMissing('payment_statuses', ['registration_id' => $reg->id, 'paid' => true]);
    }

    public function test_confirmed_fingerprints_include_every_fingerprint_of_a_registration(): void
    {
        $reg = Registration::factory()->create();
        BankTransferConfirmation::create(['registration_id' => $reg->id, 'fingerprint' => 'fp-a']);
        BankTransferConfirmation::create(['registration_id' => $reg->id, 'fingerprint' => 'fp-b']);

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);

        $this->assertEqualsCanonicalizing(['fp-a', 'fp-b'], $component->get('confirmedFingerprints'));
    }

    public function test_registration_ids_with_transfer_lists_registrations_with_a_booked_transfer(): void
    {
        $withTransfer = Registration::factory()->create();
        Registration::factory()->paid()->create();
        BankTransferConfirmation::create(['registration_id' => $withTransfer->id, 'fingerprint' => 'fp-a']);

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);

        $this->assertEquals([$withTransfer->id], $component->get('registrationIdsWithTransfer'));
    }

    public function test_confirm_payments_returns_booked_fingerprints_only(): void
    {
        $reg = Registration::factory()->create();

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);
        $result = $component->instance()->confirmPayments([
            ['registration_id' => $reg->id, 'fingerprint' => 'fp-first'],
            ['registration_id' => $reg->id, 'fingerprint' => 'fp-second'],
        ]);

        $this->assertEquals(['fp-first'], $result['booked_fingerprints']);
        $this->assertContains($reg->id, $component->get('registrationIdsWithTransfer'));
    }

    public function test_search_registrations_returns_matching_names(): void
    {
        Registration::factory()->create(['name' => 'Maria Muster']);
        Registration::factory()->create(['name' => 'Klaus Weber']);

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);
        $component->call('searchRegistrations', 'Maria');

        $results = $component->get('searchResults');

        $this->assertNotEmpty($results);
        $names = array_column($results, 'name');
        $this->assertContains('Maria Muster', $names);
        $this->assertNotContains('Klaus Weber', $names);
    }

    public function test_search_registrations_is_case_insensitive(): void
    {
        Registration::factory()->create(['name' => 'Maria Muster']);

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);
        $component->call('searchRegistrations', 'maria');

        $results = $component->get('searchResults');
        $names = array_column($results, 'name');
        $this->assertContains('Maria Muster', $names);
    }

    public function test_search_registrations_returns_empty_for_no_match(): void
    {
        Registration::factory()->create(['name' => 'Maria Muster']);

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);
        $component->call('searchRegistrations', 'zzznomatch');

        $results = $component->get('searchResults');
        $this->assertEmpty($results);
    }
}
