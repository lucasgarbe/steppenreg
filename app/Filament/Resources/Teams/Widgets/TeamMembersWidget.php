<?php

namespace App\Filament\Resources\Teams\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TeamMembersWidget extends BaseWidget
{
    public $team;

    protected int|string|array $columnSpan = 'full';

    public function mount($team = null): void
    {
        $this->team = $team;
    }

    protected function getTableHeading(): string
    {
        $memberCount = $this->team?->registrations()->count() ?? 0;
        $maxMembers = $this->team?->max_members ?? 0;

        return "Team Members ({$memberCount}/{$maxMembers})";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->team?->registrations()->getQuery() ?? \App\Models\Registration::whereRaw('1=0')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Participant Name')
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->sortable(),

                TextColumn::make('track_name')
                    ->label('Track')
                    ->placeholder('No track selected'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Finished' => 'success',
                        'Starting' => 'info',
                        'Payed' => 'warning',
                        'Registered' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->emptyStateDescription('This team has no members yet.')
            ->emptyStateIcon('heroicon-o-users')
            ->paginated(false);
    }
}
