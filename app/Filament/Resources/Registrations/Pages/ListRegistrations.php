<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Filament\Resources\Registrations\RegistrationResource;
use App\Settings\EventSettings;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageDraw')
                ->label('Draw Management')
                ->icon('heroicon-o-calculator')
                ->color('gray')
                ->url(static::$resource::getUrl('draw')),
            CreateAction::make()->color('success'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All Registrations')
                ->badge(fn() => static::$resource::getModel()::count()),
        ];

        $settings = app(EventSettings::class);

        if (isset($settings->tracks) && is_array($settings->tracks)) {
            foreach ($settings->tracks as $track) {
                $trackId = $track['id'];
                $trackName = $track['name'];

                $tabs["track_{$trackId}"] = Tab::make($trackName)
                    ->modifyQueryUsing(fn(Builder $query) => $query->where('track_id', $trackId))
                    ->badge(fn() => static::$resource::getModel()::where('track_id', $trackId)->count());
            }
        }

        return $tabs;
    }
}
