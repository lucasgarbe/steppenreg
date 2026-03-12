<?php

namespace App\Domain\StartingNumber\Filament\Actions;

use App\Domain\StartingNumber\Models\StartingNumber;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ResetStartingNumbersAction
{
    public static function make(): Action
    {
        return Action::make('reset_starting_numbers')
            ->label('Reset Assignments')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reset All Starting Number Assignments')
            ->modalDescription('This will remove all starting number assignments from participants. The bibs and their tag IDs will be kept. This cannot be undone.')
            ->modalSubmitActionLabel('Reset All Assignments')
            ->action(function (): void {
                $count = StartingNumber::count();

                StartingNumber::query()->delete();

                Notification::make()
                    ->title('Assignments reset')
                    ->body("Removed {$count} starting number ".str('assignment')->plural($count).'.')
                    ->success()
                    ->send();
            });
    }
}
