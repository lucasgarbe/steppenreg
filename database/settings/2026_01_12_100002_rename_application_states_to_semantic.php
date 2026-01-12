<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Update application_state values
        $settings = DB::table('settings')
            ->where('group', 'event')
            ->where('name', 'application_state')
            ->first();

        if ($settings) {
            $payload = json_decode($settings->payload, true);

            if (isset($payload['value'])) {
                // Rename states
                $stateMap = [
                    'open_flinta' => 'priority_period',
                    'open_everyone' => 'general_open',
                ];

                if (isset($stateMap[$payload['value']])) {
                    $payload['value'] = $stateMap[$payload['value']];
                    DB::table('settings')
                        ->where('id', $settings->id)
                        ->update(['payload' => json_encode($payload)]);
                }
            }
        }

        // Also update manual_override_state if set
        $overrideSettings = DB::table('settings')
            ->where('group', 'event')
            ->where('name', 'manual_override_state')
            ->first();

        if ($overrideSettings) {
            $payload = json_decode($overrideSettings->payload, true);

            if (isset($payload['value']) && $payload['value']) {
                $stateMap = [
                    'open_flinta' => 'priority_period',
                    'open_everyone' => 'general_open',
                ];

                if (isset($stateMap[$payload['value']])) {
                    $payload['value'] = $stateMap[$payload['value']];
                    DB::table('settings')
                        ->where('id', $overrideSettings->id)
                        ->update(['payload' => json_encode($payload)]);
                }
            }
        }
    }

    public function down(): void
    {
        // Reverse the state name changes
        $settings = DB::table('settings')
            ->where('group', 'event')
            ->where('name', 'application_state')
            ->first();

        if ($settings) {
            $payload = json_decode($settings->payload, true);

            if (isset($payload['value'])) {
                $stateMap = [
                    'priority_period' => 'open_flinta',
                    'general_open' => 'open_everyone',
                ];

                if (isset($stateMap[$payload['value']])) {
                    $payload['value'] = $stateMap[$payload['value']];
                    DB::table('settings')
                        ->where('id', $settings->id)
                        ->update(['payload' => json_encode($payload)]);
                }
            }
        }

        // Also reverse manual_override_state
        $overrideSettings = DB::table('settings')
            ->where('group', 'event')
            ->where('name', 'manual_override_state')
            ->first();

        if ($overrideSettings) {
            $payload = json_decode($overrideSettings->payload, true);

            if (isset($payload['value']) && $payload['value']) {
                $stateMap = [
                    'priority_period' => 'open_flinta',
                    'general_open' => 'open_everyone',
                ];

                if (isset($stateMap[$payload['value']])) {
                    $payload['value'] = $stateMap[$payload['value']];
                    DB::table('settings')
                        ->where('id', $overrideSettings->id)
                        ->update(['payload' => json_encode($payload)]);
                }
            }
        }
    }
};
