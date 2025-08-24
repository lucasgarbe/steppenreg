<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Filament\Resources\Registrations\RegistrationResource;
use App\Filament\Resources\Registrations\Widgets\DrawStatsWidget;
use App\Filament\Resources\Registrations\Widgets\TrackStatsWidget;
use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;

class ManageDraw extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string $resource = RegistrationResource::class;
    protected static ?string $title = 'Draw Management';
    protected string $view = 'filament.resources.registrations.pages.manage-draw';

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

        $this->drawRegistrations($state['track_id'], $state['participants_to_draw']);
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
                'stats' => $stats
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
        $waitlist = $registrations->where('draw_status', 'waitlist')->count();
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
            'waitlist' => $waitlist,
            'not_drawn' => $notDrawn,
            'individuals' => $individuals,
            'team_members' => $teamMembers,
            'teams' => $teams,
            'drawing_units' => $drawingUnits,
        ];
    }

    public function drawRegistrations(int $trackId, int $participantsToDraw)
    {
        Log::info('drawRegistrations', ['track_id' => $trackId, 'participants_to_draw' => $participantsToDraw]);

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

        Log::info('Found drawing units', ['individuals' => $individuals->count(), 'teams' => $teams->count()]);

        // Create drawing units collection and shuffle randomly
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
            Notification::make()
                ->title('Draw Failed')
                ->body('No participants available for draw in this track.')
                ->danger()
                ->send();
            return;
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
                continue; // Skip large teams that would exceed target
            }

            $drawnRegistrations = $drawnRegistrations->merge($unit['registrations']);
            $unitsDrawn++;
            $participantsDrawn += $unit['participant_count'];

            Log::info('Drew unit', [
                'type' => $unit['type'],
                'participant_count' => $unit['participant_count'],
                'total_drawn' => $participantsDrawn
            ]);

            // Log individual registrations being drawn
            foreach ($unit['registrations'] as $registration) {
                Log::info('Drawn registration: ' . $registration->email);
                $registration->draw_status = 'drawn';
                $registration->drawn_at = now();
                $registration->save();

                // If this is a team registration, draw all team members
                if ($registration->team) {
                    Log::info('Drawn registration is in team: ' . $registration->team->name);
                    $teamMembers = $registration->team->registrations()->where('draw_status', 'not_drawn')->get();

                    foreach ($teamMembers as $teamMember) {
                        if ($teamMember->id !== $registration->id) { // Don't double-process the original
                            Log::info('Registration drawn through team: ' . $teamMember->email);
                            $teamMember->draw_status = 'drawn';
                            $teamMember->drawn_at = now();
                            $teamMember->save();
                        }
                    }
                }
            }

            // Stop if we've reached our target
            if ($participantsDrawn >= $participantsToDraw) {
                break;
            }
        }

        // Get track info for notification
        $tracks = app(EventSettings::class)->tracks ?? [];
        $trackInfo = collect($tracks)->firstWhere('id', $trackId);
        $trackName = $trackInfo['name'] ?? "Track {$trackId}";

        Notification::make()
            ->title('Draw Completed Successfully!')
            ->body("Drew {$participantsDrawn} participants from {$unitsDrawn} units for {$trackName}")
            ->success()
            ->send();

        Log::info('Draw completed', ['units_drawn' => $unitsDrawn, 'participants_drawn' => $participantsDrawn]);
    }

    public function sendAllDrawNotifications()
    {
        // Get all registrations with draw results
        $drawn = Registration::where('draw_status', 'drawn')->get();
        $waitlist = Registration::where('draw_status', 'waitlist')->get();
        $rejected = Registration::where('draw_status', 'not_drawn')->get();
        
        $sent = 0;
        
        // Send to drawn participants (generate withdraw tokens first)
        foreach ($drawn as $registration) {
            if (!$registration->withdraw_token) {
                $registration->generateWithdrawToken();
            }
            \App\Jobs\Mail\SendDrawNotification::dispatch($registration);
            $sent++;
        }
        
        // Send to waitlist participants
        foreach ($waitlist as $registration) {
            \App\Jobs\Mail\SendDrawNotification::dispatch($registration);
            $sent++;
        }
        
        // Send to rejected participants (generate waitlist tokens first)
        foreach ($rejected as $registration) {
            if (!$registration->waitlist_token) {
                $registration->generateWaitlistToken();
            }
            \App\Jobs\Mail\SendDrawNotification::dispatch($registration);
            $sent++;
        }
        
        Notification::make()
            ->title("All draw notifications queued!")
            ->body("Sent {$sent} emails to queue: {$drawn->count()} drawn, {$waitlist->count()} waitlist, {$rejected->count()} rejected")
            ->success()
            ->send();

        Log::info('All draw notifications sent', [
            'drawn' => $drawn->count(),
            'waitlist' => $waitlist->count(), 
            'rejected' => $rejected->count(),
            'total_sent' => $sent
        ]);
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
