<?php

namespace App\Filament\Pages;

use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class ManageDraw extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;
    protected static ?string $title = 'Manage Draw';
    protected string $view = 'filament.pages.manage-draw';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'track_id' => null,
            'participants_to_draw' => 50,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('track_id')
                    ->label('Track Selection')
                    ->options(function () {
                        $tracks = app(EventSettings::class)->tracks ?? [];
                        $options = [];

                        foreach ($tracks as $track) {
                            $label = $track['name'];
                            if (isset($track['distance'])) {
                                $label .= ' (' . $track['distance'] . ' km)';
                            }
                            $options[$track['id']] = $label;
                        }

                        return $options;
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->updateTrackInfo($state);
                    })
                    ->helperText('Select the track to perform the draw for'),

                TextInput::make('participants_to_draw')
                    ->label('Participants to Draw')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->helperText('Number of participants to select in the draw'),

                Placeholder::make('track_stats')
                    ->label('Track Statistics')
                    ->content(function () {
                        $trackId = $this->data['track_id'] ?? null;
                        if (!$trackId) {
                            return 'Select a track to see statistics';
                        }

                        $stats = $this->getTrackStats($trackId);
                        return "
                            • Total Registrations: {$stats['total']}
                            • Not Drawn Yet: {$stats['not_drawn']}
                            • Already Drawn: {$stats['drawn']}
                            • On Waitlist: {$stats['waitlist']}
                            • Individual Registrations: {$stats['individuals']}
                            • Team Registrations: {$stats['team_members']} (in {$stats['teams']} teams)
                            • Drawing Units Available: {$stats['drawing_units']}
                        ";
                    }),
            ])
            ->statePath('data')
            ->columns(1);
    }

    protected function getActions(): array
    {
        return [
            Action::make('executeDraw')
                ->label('Execute Draw')
                ->icon('heroicon-o-star')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription(function () {
                    $trackId = $this->data['track_id'] ?? null;
                    $participantsToDraw = $this->data['participants_to_draw'] ?? 0;

                    if (!$trackId) {
                        return 'Please select a track first.';
                    }

                    $stats = $this->getTrackStats($trackId);
                    return "This will randomly draw {$participantsToDraw} participants from {$stats['drawing_units']} available units (individuals + teams) for the selected track. Teams will be drawn as complete units.";
                })
                ->action('performDraw')
                ->disabled(fn() => !($this->data['track_id'] ?? null)),

            Action::make('previewDraw')
                ->label('Preview Draw')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->action('showDrawPreview')
                ->disabled(fn() => !($this->data['track_id'] ?? null)),
        ];
    }

    private function getTrackStats(int $trackId): array
    {
        $registrations = Registration::where('track_id', $trackId);

        $total = $registrations->count();
        $drawn = $registrations->where('draw_status', 'drawn')->count();
        $waitlist = $registrations->where('draw_status', 'waitlist')->count();
        $notDrawn = $registrations->where('draw_status', 'not_drawn')->count();

        $individuals = $registrations->whereNull('team_id')->where('draw_status', 'not_drawn')->count();
        $teamMembers = $registrations->whereNotNull('team_id')->where('draw_status', 'not_drawn')->count();

        $teams = Team::where('track_id', $trackId)
            ->whereHas('registrations', function ($query) {
                $query->where('draw_status', 'not_drawn');
            })
            ->count();

        // Drawing units = individuals + teams (each team counts as 1 unit)
        $drawingUnits = $individuals + $teams;

        return [
            'total' => $total,
            'drawn' => $drawn,
            'waitlist' => $waitlist,
            'not_drawn' => $notDrawn,
            'individuals' => $individuals,
            'team_members' => $teamMembers,
            'teams' => $teams,
            'drawing_units' => $drawingUnits,
        ];
    }

    public function updateTrackInfo($trackId): void
    {
        // This will trigger form re-render with updated stats
        $this->form->fill($this->data);
    }

    public function performDraw(): void
    {
        $trackId = $this->data['track_id'];
        $participantsToDraw = $this->data['participants_to_draw'];

        if (!$trackId || !$participantsToDraw) {
            Notification::make()
                ->title('Invalid Parameters')
                ->body('Please select a track and specify number of participants.')
                ->danger()
                ->send();
            return;
        }

        $result = $this->executeDraw($trackId, $participantsToDraw);

        if ($result['success']) {
            Notification::make()
                ->title('Draw Completed Successfully!')
                ->body("Drew {$result['participants_drawn']} participants from {$result['units_drawn']} units (individuals + teams)")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Draw Failed')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function showDrawPreview(): void
    {
        $trackId = $this->data['track_id'];

        if (!$trackId) {
            return;
        }

        $stats = $this->getTrackStats($trackId);

        Notification::make()
            ->title('Draw Preview')
            ->body("Available for draw: {$stats['drawing_units']} units ({$stats['individuals']} individuals + {$stats['teams']} teams = {$stats['not_drawn']} total participants)")
            ->info()
            ->send();
    }

    private function executeDraw(int $trackId, int $participantsToDraw): array
    {
        // Get all drawing units (individuals + teams) that haven't been drawn yet
        $individuals = Registration::where('track_id', $trackId)
            ->whereNull('team_id')
            ->where('draw_status', 'not_drawn')
            ->get();

        $teams = Team::where('track_id', $trackId)
            ->whereHas('registrations', function ($query) {
                $query->where('draw_status', 'not_drawn');
            })
            ->with(['registrations' => function ($query) {
                $query->where('draw_status', 'not_drawn');
            }])
            ->get();

        // Create drawing units collection
        $drawingUnits = collect();

        // Add individuals as single-person units
        foreach ($individuals as $individual) {
            $drawingUnits->push([
                'type' => 'individual',
                'registrations' => collect([$individual]),
                'participant_count' => 1,
            ]);
        }

        // Add teams as multi-person units
        foreach ($teams as $team) {
            $drawingUnits->push([
                'type' => 'team',
                'team' => $team,
                'registrations' => $team->registrations,
                'participant_count' => $team->registrations->count(),
            ]);
        }

        if ($drawingUnits->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No participants available for draw in this track.',
            ];
        }

        // Shuffle the drawing units randomly
        $shuffledUnits = $drawingUnits->shuffle();

        // Draw units until we reach the target participant count
        $drawnRegistrations = collect();
        $unitsDrawn = 0;
        $participantsDrawn = 0;

        foreach ($shuffledUnits as $unit) {
            // Check if drawing this unit would exceed our target
            if ($participantsDrawn + $unit['participant_count'] > $participantsToDraw) {
                // If we're close to the target, we might want to skip large teams
                // or put them on waitlist instead
                continue;
            }

            $drawnRegistrations = $drawnRegistrations->merge($unit['registrations']);
            $unitsDrawn++;
            $participantsDrawn += $unit['participant_count'];

            // Stop if we've reached our target
            if ($participantsDrawn >= $participantsToDraw) {
                break;
            }
        }

        // Update the drawn registrations
        foreach ($drawnRegistrations as $registration) {
            $registration->update([
                'draw_status' => 'drawn',
                'drawn_at' => now(),
            ]);
        }

        return [
            'success' => true,
            'units_drawn' => $unitsDrawn,
            'participants_drawn' => $participantsDrawn,
        ];
    }
}
