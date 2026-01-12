<?php

namespace App\Filament\Widgets;

use App\Settings\EventSettings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StateTransitionWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $eventSettings = app(EventSettings::class);
        $stats = [];

        // Current State
        $currentState = $eventSettings->application_state;
        $stateLabel = $eventSettings->getApplicationStateLabel();

        $stats[] = Stat::make('Current State', $stateLabel)
            ->description('Application state right now')
            ->descriptionIcon('heroicon-m-information-circle')
            ->color(match ($currentState) {
                'priority_period', 'general_open' => 'success',
                'live_event' => 'info',
                'closed' => 'danger',
                default => 'gray'
            });

        // Show available categories during priority_period
        if ($currentState === 'priority_period') {
            $activeCategories = $eventSettings->getActiveGenderCategories();
            $locale = app()->getLocale();
            $labels = collect($activeCategories)
                ->pluck("translations.{$locale}.label")
                ->join(', ');

            $stats[] = Stat::make('Open For', $labels ?: 'None')
                ->description('Currently available priority categories')
                ->descriptionIcon('heroicon-m-users')
                ->color('info');
        }

        // Show all categories during general_open
        if ($currentState === 'general_open') {
            $allCategories = $eventSettings->gender_categories;
            $locale = app()->getLocale();
            $labels = collect($allCategories)
                ->pluck("translations.{$locale}.label")
                ->join(', ');

            $stats[] = Stat::make('Open For', $labels)
                ->description('All categories available')
                ->descriptionIcon('heroicon-m-users')
                ->color('success');
        }

        // Automatic Transitions Status
        if ($eventSettings->automatic_state_transitions) {
            if ($eventSettings->manual_override_active) {
                $stats[] = Stat::make('Manual Override', 'Active')
                    ->description('Overriding automatic transitions')
                    ->descriptionIcon('heroicon-m-hand-raised')
                    ->color('warning');
            } else {
                $stats[] = Stat::make('Automatic Mode', 'Enabled')
                    ->description('State changes automatically')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('success');
            }
        } else {
            $stats[] = Stat::make('Manual Mode', 'Active')
                ->description('State managed manually')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('gray');
        }

        // Next Transition
        $nextTransition = $eventSettings->getNextStateTransition();
        if ($nextTransition && $eventSettings->automatic_state_transitions && ! $eventSettings->manual_override_active) {
            $timeUntil = $nextTransition['datetime']->diffForHumans();
            $stats[] = Stat::make('Next Transition', $nextTransition['label'])
                ->description($timeUntil)
                ->descriptionIcon('heroicon-m-arrow-right')
                ->color('info');
        } elseif ($eventSettings->automatic_state_transitions) {
            $stats[] = Stat::make('Next Transition', 'None Scheduled')
                ->description('No upcoming transitions')
                ->descriptionIcon('heroicon-m-check')
                ->color('gray');
        }

        return $stats;
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
