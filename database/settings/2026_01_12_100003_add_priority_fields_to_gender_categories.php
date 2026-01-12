<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $settings = DB::table('settings')
            ->where('group', 'event')
            ->where('name', 'gender_categories')
            ->first();

        if ($settings) {
            $payload = json_decode($settings->payload, true);

            if (isset($payload['value']) && is_array($payload['value'])) {
                // Add new fields to each category
                foreach ($payload['value'] as &$category) {
                    // Add is_priority (default false as per requirement)
                    if (! isset($category['is_priority'])) {
                        $category['is_priority'] = false;
                    }

                    // Add message structure (null by default)
                    if (! isset($category['message'])) {
                        $category['message'] = [
                            'en' => null,
                            'de' => null,
                        ];
                    }

                    // Add message_style (default info)
                    if (! isset($category['message_style'])) {
                        $category['message_style'] = 'info';
                    }
                }

                DB::table('settings')
                    ->where('id', $settings->id)
                    ->update(['payload' => json_encode($payload)]);
            }
        }
    }

    public function down(): void
    {
        $settings = DB::table('settings')
            ->where('group', 'event')
            ->where('name', 'gender_categories')
            ->first();

        if ($settings) {
            $payload = json_decode($settings->payload, true);

            if (isset($payload['value']) && is_array($payload['value'])) {
                foreach ($payload['value'] as &$category) {
                    unset($category['is_priority']);
                    unset($category['message']);
                    unset($category['message_style']);
                }

                DB::table('settings')
                    ->where('id', $settings->id)
                    ->update(['payload' => json_encode($payload)]);
            }
        }
    }
};
