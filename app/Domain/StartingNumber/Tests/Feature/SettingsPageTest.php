<?php

namespace App\Domain\StartingNumber\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_accessible_by_admin(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get('/admin/starting-numbers');

        // Filament pages return 200
        $response->assertStatus(200);
    }

    public function test_unauthenticated_cannot_access_settings(): void
    {
        $response = $this->get('/admin/starting-numbers');

        $response->assertRedirect('/admin/login');
    }

    public function test_config_can_be_updated(): void
    {
        $this->markTestIncomplete('Config update test needs implementation');
    }
}
