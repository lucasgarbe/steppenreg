<?php

namespace Tests\Feature;

use App\Domain\Draw\Models\DrawStatus;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_draw_statuses_table_can_be_created(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);

        $drawStatus = DrawStatus::create([
            'registration_id' => $registration->id,
            'status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $this->assertDatabaseHas('draw_statuses', [
            'registration_id' => $registration->id,
            'status' => 'drawn',
        ]);
    }

    public function test_draw_status_belongs_to_registration(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);

        $drawStatus = DrawStatus::create([
            'registration_id' => $registration->id,
            'status' => 'not_drawn',
        ]);

        $this->assertInstanceOf(Registration::class, $drawStatus->registration);
        $this->assertEquals($registration->id, $drawStatus->registration->id);
    }

    public function test_registration_has_one_draw_status(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);

        DrawStatus::create([
            'registration_id' => $registration->id,
            'status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $registration->refresh();

        $this->assertInstanceOf(DrawStatus::class, $registration->drawStatus);
        $this->assertEquals('drawn', $registration->drawStatus->status);
    }

    public function test_registration_can_have_no_draw_status(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);

        $this->assertNull($registration->fresh()->drawStatus);
    }

    public function test_draw_status_registration_id_is_unique(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);

        DrawStatus::create([
            'registration_id' => $registration->id,
            'status' => 'drawn',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DrawStatus::create([
            'registration_id' => $registration->id,
            'status' => 'not_drawn',
        ]);
    }

    public function test_draw_status_casts_drawn_at_to_datetime(): void
    {
        $registration = Registration::factory()->create(['track_id' => 1]);

        $drawStatus = DrawStatus::create([
            'registration_id' => $registration->id,
            'status' => 'drawn',
            'drawn_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $drawStatus->drawn_at);
    }

    public function test_migration_rolls_back_cleanly(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('draw_statuses'));

        \Illuminate\Support\Facades\Artisan::call('migrate:rollback', ['--path' => 'database/migrations/2026_08_18_000001_create_draw_statuses_table.php']);

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('draw_statuses'));

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--path' => 'database/migrations/2026_08_18_000001_create_draw_statuses_table.php']);

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('draw_statuses'));
    }
}
