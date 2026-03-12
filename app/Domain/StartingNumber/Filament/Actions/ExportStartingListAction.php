<?php

namespace App\Domain\StartingNumber\Filament\Actions;

use App\Models\Registration;
use App\Settings\EventSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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
            ])
            ->modalHeading('Export Starting List')
            ->modalDescription('Export a CSV file with tag ID, bib number, and name for all participants marked as starting on the selected track.')
            ->modalSubmitActionLabel('Download CSV')
            ->action(function (array $data): StreamedResponse {
                $trackId = (int) $data['track_id'];

                $registrations = Registration::query()
                    ->where('track_id', $trackId)
                    ->where('starting', true)
                    ->with('startingNumber')
                    ->orderBy(
                        \App\Domain\StartingNumber\Models\StartingNumber::select('number')
                            ->whereColumn('registration_id', 'registrations.id')
                            ->limit(1)
                    )
                    ->get();

                $trackName = collect(app(EventSettings::class)->tracks ?? [])
                    ->firstWhere('id', $trackId);
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $trackName['name'] ?? "track_{$trackId}");
                $filename = "starting_list_{$safeName}.csv";

                return response()->streamDownload(function () use ($registrations): void {
                    $handle = fopen('php://output', 'w');

                    fputcsv($handle, ['tag_id', 'bib', 'name']);

                    foreach ($registrations as $registration) {
                        fputcsv($handle, [
                            $registration->startingNumber?->tag_id ?? '',
                            $registration->startingNumber?->number ?? '',
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
