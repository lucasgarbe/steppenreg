<?php

namespace App\Domain\Draw\Filament\Pages;

use App\Domain\Draw\Exceptions\DrawAlreadyExecutedException;
use App\Domain\Draw\Exceptions\InsufficientRegistrationsException;
use App\Domain\Draw\Filament\Widgets\DrawStatsWidget;
use App\Domain\Draw\Filament\Widgets\TrackStatsWidget;
use App\Domain\Draw\Services\DrawService;
use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class ManageDraw extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGiftTop;

    protected static string|UnitEnum|null $navigationGroup = 'Registration';

    protected static ?string $navigationLabel = 'Manage Draw';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Auslosung';

    protected string $view = 'filament.draw.pages.manage-draw';

    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Draw Configuration')
                    ->description('Configure the parameters for executing an automatic draw')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Select::make('track_id')
                            ->label(track_label())
                            ->options(function () {
                                $tracks = app(EventSettings::class)->tracks ?? [];
                                $options = [];

                                foreach ($tracks as $track) {
                                    $label = $track['name'];
                                    if (isset($track['distance'])) {
                                        $label .= ' ('.$track['distance'].' km)';
                                    }
                                    $options[$track['id']] = $label;
                                }

                                return $options;
                            })
                            ->required()
                            ->helperText('Select the track to perform the draw for'),

                        TextInput::make('participants_to_draw')
                            ->label('Participants to Draw')
                            ->numeric()
                            ->default(50)
                            ->required()
                            ->minValue(1)
                            ->helperText('Number of participants to select in the draw'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function submitDraw()
    {
        $state = $this->form->getState();
        Log::info('submitDraw', $state);

        $this->executeDraw($state['track_id'], $state['participants_to_draw']);
    }

    public function getTrackStats()
    {
        $tracks = app(EventSettings::class)->tracks ?? [];
        $allStats = [];

        foreach ($tracks as $track) {
            $stats = $this->calculateTrackStats($track['id']);
            $allStats[] = [
                'track_name' => $track['name'],
                'distance' => $track['distance'] ?? null,
                'stats' => $stats,
            ];
        }

        $message = "Current Track Statistics:\n\n";
        foreach ($allStats as $trackData) {
            $stats = $trackData['stats'];
            $message .= "Track: {$trackData['track_name']}";
            if ($trackData['distance']) {
                $message .= " ({$trackData['distance']} km)";
            }
            $message .= "\n";
            $message .= "   • Total: {$stats['total']} | Drawn: {$stats['drawn']} | Not Drawn: {$stats['not_drawn']}\n";
            $message .= "   • Available Units: {$stats['drawing_units']} ({$stats['individuals']} individuals + {$stats['teams']} teams)\n\n";
        }

        Notification::make()
            ->title('Track Statistics')
            ->body($message)
            ->info()
            ->send();
    }

    private function calculateTrackStats(int $trackId): array
    {
        $registrations = Registration::where('track_id', $trackId);

        $total = $registrations->count();
        $drawn = $registrations->where('draw_status', 'drawn')->count();
        $notDrawn = $registrations->where('draw_status', 'not_drawn')->count();

        $individuals = $registrations->whereNull('team_id')->where('draw_status', 'not_drawn')->count();
        $teamMembers = $registrations->whereNotNull('team_id')->where('draw_status', 'not_drawn')->count();

        $teams = Team::where('track_id', $trackId)
            ->whereHas('registrations', function ($query) {
                $query->where('draw_status', 'not_drawn');
            })
            ->count();

        $drawingUnits = $individuals + $teams;

        return [
            'total' => $total,
            'drawn' => $drawn,
            'not_drawn' => $notDrawn,
            'individuals' => $individuals,
            'team_members' => $teamMembers,
            'teams' => $teams,
            'drawing_units' => $drawingUnits,
        ];
    }

    public function executeDraw(int $trackId, int $participantsToDraw)
    {
        Log::info('executeDraw', ['track_id' => $trackId, 'participants_to_draw' => $participantsToDraw]);

        try {
            $drawService = app(DrawService::class);

            $draw = $drawService->executeDraw(
                trackId: $trackId,
                availableSpots: $participantsToDraw,
                executedByUserId: Auth::id()
            );

            // Get track info for notification
            $tracks = app(EventSettings::class)->tracks ?? [];
            $trackInfo = collect($tracks)->firstWhere('id', $trackId);
            $trackName = $trackInfo['name'] ?? "Track {$trackId}";

            Notification::make()
                ->title('Draw Completed Successfully!')
                ->body("Drew {$draw->total_drawn} participants out of {$draw->total_registrations} for {$trackName}. Draw notifications are being sent via queue.")
                ->success()
                ->send();

            Log::info('Draw completed', [
                'draw_id' => $draw->id,
                'total_drawn' => $draw->total_drawn,
                'total_not_drawn' => $draw->total_not_drawn,
            ]);

        } catch (DrawAlreadyExecutedException $e) {
            Notification::make()
                ->title('Draw Already Executed')
                ->body('A draw has already been executed for this track. Each track can only have one draw.')
                ->danger()
                ->send();

            Log::warning('Draw already executed for track', ['track_id' => $trackId]);

        } catch (InsufficientRegistrationsException $e) {
            Notification::make()
                ->title('Draw Failed')
                ->body('No participants available for draw in this track.')
                ->danger()
                ->send();

            Log::warning('Insufficient registrations for draw', ['track_id' => $trackId]);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Draw Failed')
                ->body('An error occurred while executing the draw: '.$e->getMessage())
                ->danger()
                ->send();

            Log::error('Draw execution failed', [
                'track_id' => $trackId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DrawStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            TrackStatsWidget::class,
        ];
    }
}
