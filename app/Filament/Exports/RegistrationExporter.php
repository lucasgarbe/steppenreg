<?php

namespace App\Filament\Exports;

use App\Models\Registration;
use App\Settings\EventSettings;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Builder;

class RegistrationExporter extends Exporter
{
    protected static ?string $model = Registration::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),

            ExportColumn::make('starting_number')
                ->label('Starting Number')
                ->state(function (Registration $record) {
                    if (! $record->starting_number) {
                        return '---';
                    }

                    $service = app(\App\Domain\StartingNumber\Services\StartingNumberService::class);

                    return $service->getNumberLabel($record);
                }),

            ExportColumn::make('name')
                ->label('Name'),

            ExportColumn::make('email')
                ->label('Email'),

            ExportColumn::make('age')
                ->label('Age')
                ->formatStateUsing(fn (?int $state): string => $state ? (string) $state : '---'),

            ExportColumn::make('gender')
                ->label('Gender')
                ->formatStateUsing(function (?string $state, Registration $record): string {
                    if (! $state) {
                        return '---';
                    }

                    return $record->gender_label;
                }),

            ExportColumn::make('track_name')
                ->label('Track')
                ->state(fn (Registration $record): string => $record->track_name ?? 'No Track'),

            ExportColumn::make('team.name')
                ->label('Team')
                ->formatStateUsing(fn (?string $state): string => $state ?? 'Individual'),

            ExportColumn::make('draw_status')
                ->label('Draw Status')
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'drawn' => 'Drawn',
                    'not_drawn' => 'Not Drawn',
                    default => '---',
                }),

            ExportColumn::make('drawn_at')
                ->label('Drawn At')
                ->formatStateUsing(fn ($state): string => $state ? $state->format('Y-m-d H:i:s') : '---'),

            ExportColumn::make('payed')
                ->label('Paid')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),

            ExportColumn::make('starting')
                ->label('Starting')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),

            ExportColumn::make('finish_time')
                ->label('Finish Time')
                ->formatStateUsing(fn ($state): string => $state ? $state->format('H:i') : '---'),

            ExportColumn::make('notes')
                ->label('Notes')
                ->formatStateUsing(fn (?string $state): string => $state ?? '---'),

            ExportColumn::make('created_at')
                ->label('Registration Date')
                ->formatStateUsing(fn ($state): string => $state->format('Y-m-d H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your registration export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Toggle::make('includeCustomQuestions')
                ->label('Include Custom Questions Data')
                ->default(false)
                ->helperText('Add custom question answers as additional columns in the export'),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('team');
    }

    public function resolveColumns(): array
    {
        $columns = static::getColumns();

        $options = $this->getOptions();

        if ($options['includeCustomQuestions'] ?? false) {
            $columns = array_merge($columns, $this->getCustomQuestionColumns());
        }

        return $columns;
    }

    protected function getCustomQuestionColumns(): array
    {
        $columns = [];
        $eventSettings = app(EventSettings::class);
        $customQuestions = $eventSettings->custom_questions ?? [];

        foreach ($customQuestions as $question) {
            $key = $question['key'];

            $columns[] = ExportColumn::make("custom_answers.{$key}")
                ->label($key)
                ->state(function (Registration $record) use ($question, $key) {
                    $value = $record->getCustomAnswer($key);

                    if (empty($value)) {
                        return '---';
                    }

                    if ($question['type'] === 'checkbox' && is_array($value)) {
                        return implode(', ', $value);
                    }

                    if (in_array($question['type'], ['select', 'radio'])) {
                        $option = collect($question['options'] ?? [])
                            ->firstWhere('value', $value);

                        if ($option) {
                            return $option['label_en'] ?? $value;
                        }
                    }

                    return (string) $value;
                });
        }

        return $columns;
    }

    public function getFormats(): array
    {
        return [
            \Filament\Actions\Exports\Enums\ExportFormat::Csv,
        ];
    }

    public function getFileDisk(): string
    {
        return 'local';
    }

    public function getJob(): string
    {
        return \App\Filament\Exports\Jobs\PrepareSingleCsvExport::class;
    }
}
