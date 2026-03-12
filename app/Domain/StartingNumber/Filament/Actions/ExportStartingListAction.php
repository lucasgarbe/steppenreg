<?php

namespace App\Domain\StartingNumber\Filament\Actions;

use App\Models\Registration;
use App\Settings\EventSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportStartingListAction
{
    public static function make(): Action
    {
        return Action::make('export_starting_list')
            ->label('Export Starting List')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('info')
            ->form([
                Select::make('track_id')
                    ->label('Track')
                    ->options(function (): array {
                        $settings = app(EventSettings::class);

                        return collect($settings->tracks ?? [])
                            ->mapWithKeys(fn ($track) => [$track['id'] => $track['name']])
                            ->toArray();
                    })
                    ->required()
                    ->placeholder('Select a track'),

                Toggle::make('all_participants')
                    ->label('Include all participants')
                    ->helperText('When enabled, exports all participants. When disabled, only exports participants marked as starting.')
                    ->default(false),
            ])
            ->modalHeading('Export Starting List')
            ->modalDescription('Export a CSV file with bib number, tag ID, and name for participants on the selected track.')
            ->modalSubmitActionLabel('Download CSV')
            ->action(function (array $data): StreamedResponse {
                $trackId = (int) $data['track_id'];
                $allParticipants = $data['all_participants'] ?? false;

                $query = Registration::query()
                    ->where('track_id', $trackId)
                    ->with('startingNumber.bib')
                    ->leftJoin('starting_numbers', 'registrations.id', '=', 'starting_numbers.registration_id')
                    ->leftJoin('bibs', 'starting_numbers.bib_id', '=', 'bibs.id')
                    ->orderBy('bibs.number')
                    ->select('registrations.*');

                // Only filter by starting status if not exporting all participants
                if (! $allParticipants) {
                    $query->where('registrations.starting', true);
                }

                $registrations = $query->get();

                $trackName = collect(app(EventSettings::class)->tracks ?? [])
                    ->firstWhere('id', $trackId);
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $trackName['name'] ?? "track_{$trackId}");
                $timestamp = now()->format('Y-m-d_His');
                $type = $allParticipants ? 'all' : 'starting';
                $filename = "starting_list_{$type}_{$safeName}_{$timestamp}.csv";

                return response()->streamDownload(function () use ($registrations): void {
                    $handle = fopen('php://output', 'w');

                    fputcsv($handle, ['bib', 'tag_id', 'name']);

                    foreach ($registrations as $registration) {
                        fputcsv($handle, [
                            $registration->startingNumber?->number ?? '',
                            $registration->startingNumber?->bib?->tag_id ?? '',
                            $registration->name,
                        ]);
                    }

                    fclose($handle);
                }, $filename, [
                    'Content-Type' => 'text/csv',
                ]);
            });
    }
}
