<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Remove waitlist_only_starts_at field
        $this->migrator->delete('event.waitlist_only_starts_at');

        // Update any closed_waitlist states to closed
        $settings = DB::table('settings')
            ->where('group', 'event')
            ->where('name', 'application_state')
            ->first();

        if ($settings) {
            $payload = json_decode($settings->payload, true);
            if (isset($payload['value']) && $payload['value'] === 'closed_waitlist') {
                $payload['value'] = 'closed';
                DB::table('settings')
                    ->where('id', $settings->id)
                    ->update(['payload' => json_encode($payload)]);
            }
        }
    }

    public function down(): void
    {
        $this->migrator->add('event.waitlist_only_starts_at', null);
    }
};
