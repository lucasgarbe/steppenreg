<?php

namespace App\Console\Commands;

use App\Settings\EventSettings;
use Illuminate\Console\Command;

class UpdateApplicationState extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:update-state {--dry-run : Show what would change without making changes} {--force : Force update even if automatic transitions are disabled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update application state based on configured datetime transitions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventSettings = app(EventSettings::class);
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');
        
        $this->info('Event State Updater');
        $this->info('Current Time: ' . now()->format('Y-m-d H:i:s T'));
        $this->line('');

        // Show current state
        $currentState = $eventSettings->application_state;
        $this->info("Current State: <comment>{$currentState}</comment> (" . $eventSettings->getApplicationStateLabel() . ')');
        
        // Check if automatic transitions are enabled
        if (!$eventSettings->automatic_state_transitions && !$isForce) {
            $this->warn('Automatic state transitions are disabled.');
            $this->info('Use --force to update anyway, or enable automatic transitions in admin settings.');
            return self::SUCCESS;
        }

        // Calculate what the state should be
        $calculatedState = $eventSettings->calculateAutomaticState();
        
        if ($calculatedState === $currentState) {
            $this->info("✅ State is correct: <comment>{$currentState}</comment>");
            
            // Show next transition if available
            $nextTransition = $eventSettings->getNextStateTransition();
            if ($nextTransition) {
                $this->line('');
                $this->info('Next Transition:');
                $this->line("  📅 {$nextTransition['datetime']->format('Y-m-d H:i:s T')}");
                $this->line("  🔄 {$nextTransition['label']} → <comment>{$nextTransition['state']}</comment>");
                $this->line("  ⏱️  In " . $nextTransition['datetime']->diffForHumans());
            } else {
                $this->line('');
                $this->info('No upcoming automatic transitions scheduled.');
            }
            
            return self::SUCCESS;
        }

        // State needs to change
        $this->warn("State Change Required:");
        $this->line("  From: <comment>{$currentState}</comment>");
        $this->line("  To:   <comment>{$calculatedState}</comment>");
        
        if ($eventSettings->manual_override_active) {
            $this->line("  Reason: Manual override is active (override state: {$eventSettings->manual_override_state})");
        } else {
            $this->line("  Reason: DateTime-based automatic transition");
        }
        
        if ($isDryRun) {
            $this->info('🔍 [DRY RUN] No changes made.');
            return self::SUCCESS;
        }

        // Perform the update
        $oldState = $eventSettings->application_state;
        $eventSettings->application_state = $calculatedState;
        $eventSettings->save();
        
        $this->info("✅ State updated successfully!");
        $this->line("  Updated: <comment>{$oldState}</comment> → <comment>{$calculatedState}</comment>");
        
        // Log the change
        logger()->info('Application state updated via command', [
            'from' => $oldState,
            'to' => $calculatedState,
            'triggered_by' => 'artisan_command',
            'dry_run' => false,
            'forced' => $isForce,
            'automatic_transitions_enabled' => $eventSettings->automatic_state_transitions,
            'timestamp' => now()->toISOString()
        ]);
        
        // Show next transition
        $nextTransition = $eventSettings->getNextStateTransition();
        if ($nextTransition) {
            $this->line('');
            $this->info('Next Transition:');
            $this->line("  📅 {$nextTransition['datetime']->format('Y-m-d H:i:s T')}");
            $this->line("  🔄 {$nextTransition['label']} → <comment>{$nextTransition['state']}</comment>");
            $this->line("  ⏱️  In " . $nextTransition['datetime']->diffForHumans());
        }

        return self::SUCCESS;
    }
}
