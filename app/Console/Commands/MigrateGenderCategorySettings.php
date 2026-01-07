<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Settings\EventSettings;
use Illuminate\Console\Command;

class MigrateGenderCategorySettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:migrate-gender-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate gender category settings from EventSettings to per-event configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting migration of gender category settings...');
        
        try {
            $eventSettings = app(EventSettings::class);
            
            $events = Event::all();
            
            if ($events->isEmpty()) {
                $this->warn('No events found to migrate.');
                return self::SUCCESS;
            }
            
            $this->info("Found {$events->count()} event(s) to migrate.");
            
            $migrated = 0;
            $skipped = 0;
            
            foreach ($events as $event) {
                $settings = $event->settings ?? [];
                
                // Skip if already has gender categories configured
                if (isset($settings['gender_categories'])) {
                    $this->line("  - Skipping event '{$event->name}' (already has gender categories)");
                    $skipped++;
                    continue;
                }
                
                // Migrate from EventSettings
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
                
                $event->update(['settings' => $settings]);
                
                $this->info("  - Migrated event '{$event->name}'");
                $migrated++;
            }
            
            $this->newLine();
            $this->info("Migration completed successfully!");
            $this->table(
                ['Status', 'Count'],
                [
                    ['Migrated', $migrated],
                    ['Skipped', $skipped],
                    ['Total', $events->count()],
                ]
            );
            
            if ($migrated > 0) {
                $this->newLine();
                $this->comment('Gender category settings have been migrated to per-event configuration.');
                $this->comment('You can now configure different registration opening dates for each gender category in the Event admin.');
            }
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
