<?php

namespace App\Domain\StartingNumber\Filament\Resources\StartingNumberResource\Pages;

use App\Domain\StartingNumber\Filament\Actions\ResetStartingNumbersAction;
use App\Domain\StartingNumber\Filament\Resources\StartingNumberResource;
use App\Settings\EventSettings;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStartingNumbers extends ListRecords
{
    protected static string $resource = StartingNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ResetStartingNumbersAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $settings = app(EventSettings::class);
        $tracks = collect($settings->tracks ?? []);

        $tabs = [
            'all' => Tab::make('All'),
        ];

        foreach ($tracks as $track) {
            $tabs[(string) $track['id']] = Tab::make($track['name'])
                ->modifyQueryUsing(
                    fn (Builder $query) => $query->whereHas(
                        'bib',
                        fn (Builder $q) => $q->where('track_id', $track['id'])
                    )
                );
        }

        return $tabs;
    }
}
