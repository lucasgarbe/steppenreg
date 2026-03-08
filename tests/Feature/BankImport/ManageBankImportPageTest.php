<?php

namespace Tests\Feature\BankImport;

use App\Domain\BankImport\Filament\Pages\ManageBankImport;
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
        $active1 = Registration::factory()->create(['payed' => false, 'draw_status' => 'not_drawn']);
        $active2 = Registration::factory()->create(['payed' => true, 'draw_status' => 'drawn']);
        $deleted = Registration::factory()->create(['payed' => false]);
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
        Registration::factory()->create(['payed' => false, 'draw_status' => 'not_drawn']);

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);

        $registrations = $component->get('registrations');
        $this->assertNotEmpty($registrations);

        $first = $registrations[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('payed', $first);
        $this->assertArrayHasKey('draw_status', $first);
        $this->assertArrayHasKey('track_name', $first);
    }

    public function test_confirm_payments_marks_registrations_as_payed(): void
    {
        $reg = Registration::factory()->create(['payed' => false]);

        Livewire::actingAs($this->admin)
            ->test(ManageBankImport::class)
            ->call('confirmPayments', [$reg->id]);

        $this->assertDatabaseHas('registrations', ['id' => $reg->id, 'payed' => true]);
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
            ->call('confirmPayments', [999999])
            ->assertNotified();
    }

    public function test_confirm_payments_already_payed_sends_success_notification_with_correct_counts(): void
    {
        $reg = Registration::factory()->create(['payed' => true]);

        Livewire::actingAs($this->admin)
            ->test(ManageBankImport::class)
            ->call('confirmPayments', [$reg->id])
            ->assertNotified();
    }

    public function test_search_registrations_returns_matching_names(): void
    {
        Registration::factory()->create(['name' => 'Maria Muster', 'payed' => false]);
        Registration::factory()->create(['name' => 'Klaus Weber', 'payed' => false]);

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
        Registration::factory()->create(['name' => 'Maria Muster', 'payed' => false]);

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);
        $component->call('searchRegistrations', 'maria');

        $results = $component->get('searchResults');
        $names = array_column($results, 'name');
        $this->assertContains('Maria Muster', $names);
    }

    public function test_search_registrations_returns_empty_for_no_match(): void
    {
        Registration::factory()->create(['name' => 'Maria Muster', 'payed' => false]);

        $component = Livewire::actingAs($this->admin)->test(ManageBankImport::class);
        $component->call('searchRegistrations', 'zzznomatch');

        $results = $component->get('searchResults');
        $this->assertEmpty($results);
    }
}
