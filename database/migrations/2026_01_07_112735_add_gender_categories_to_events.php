<?php

use App\Models\Event;
use App\Settings\EventSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration populates the gender_categories in the settings JSON column
        // for all existing events
        
        Event::query()->each(function (Event $event) {
            $settings = $event->settings ?? [];
            
            // Skip if already has gender categories configured
            if (isset($settings['gender_categories'])) {
                return;
            }
            
            // Try to get EventSettings for migration from legacy system
            try {
                $eventSettings = app(EventSettings::class);
                
                $settings['gender_categories'] = [
                    'flinta' => [
                        'enabled' => true,
                        'label' => 'FLINTA*',
                        'registration_opens_at' => $eventSettings->flinta_registration_opens_at 
                            ? $eventSettings->flinta_registration_opens_at->toDateTimeString()
                            : ($event->registration_opens_at ? $event->registration_opens_at->toDateTimeString() : null),
                    ],
                    'all_gender' => [
                        'enabled' => true,
                        'label' => 'Open/All Gender',
                        'registration_opens_at' => $eventSettings->everyone_registration_opens_at 
                            ? $eventSettings->everyone_registration_opens_at->toDateTimeString()
                            : ($event->registration_opens_at ? $event->registration_opens_at->toDateTimeString() : null),
                    ],
                ];
            } catch (\Exception $e) {
                // If EventSettings doesn't exist or fails, use event dates
                $settings['gender_categories'] = [
                    'flinta' => [
                        'enabled' => true,
                        'label' => 'FLINTA*',
                        'registration_opens_at' => $event->registration_opens_at 
                            ? $event->registration_opens_at->toDateTimeString() 
                            : null,
                    ],
                    'all_gender' => [
                        'enabled' => true,
                        'label' => 'Open/All Gender',
                        'registration_opens_at' => $event->registration_opens_at 
                            ? $event->registration_opens_at->toDateTimeString() 
                            : null,
                    ],
                ];
            }
            
            $event->update(['settings' => $settings]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove gender_categories from all events
        Event::query()->each(function (Event $event) {
            $settings = $event->settings ?? [];
            
            if (isset($settings['gender_categories'])) {
                unset($settings['gender_categories']);
                $event->update(['settings' => $settings]);
            }
        });
    }
};
