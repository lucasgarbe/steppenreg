<?php

namespace App\Domain\Draw\Services;

use App\Domain\Draw\Events\DrawExecuted;
use App\Domain\Draw\Events\RegistrationDrawn;
use App\Domain\Draw\Events\RegistrationNotDrawn;
use App\Domain\Draw\Exceptions\DrawAlreadyExecutedException;
use App\Domain\Draw\Exceptions\InsufficientRegistrationsException;
use App\Domain\Draw\Models\Draw;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DrawService
{
    /**
     * Execute a draw for a track
     */
    public function executeDraw(
        int $trackId,
        int $availableSpots,
        ?int $executedByUserId = null,
        array $config = []
    ): Draw {
        // Check if draw already executed for this track
        if (Draw::where('track_id', $trackId)->exists()) {
            throw new DrawAlreadyExecutedException(
                "A draw has already been executed for this track"
            );
        }

        // Get all pending registrations
        $registrations = Registration::where('track_id', $trackId)
            ->where('draw_status', 'not_drawn')
            ->with('team')
            ->get();

        if ($registrations->isEmpty()) {
            throw new InsufficientRegistrationsException(
                "No registrations available for draw"
            );
        }

        return DB::transaction(function () use (
            $trackId,
            $registrations,
            $availableSpots,
            $executedByUserId,
            $config
        ) {
            // Execute selection algorithm
            $selected = $this->selectRegistrations($registrations, $availableSpots);
            $notSelected = $registrations->diff($selected);

            // Create draw record
            $draw = Draw::create([
                'track_id' => $trackId,
                'executed_by_user_id' => $executedByUserId,
                'executed_at' => now(),
                'total_registrations' => $registrations->count(),
                'total_drawn' => $selected->count(),
                'total_not_drawn' => $notSelected->count(),
                'available_spots' => $availableSpots,
                'config' => $config,
            ]);

            // Update selected registrations
            foreach ($selected as $registration) {
                $registration->update([
                    'draw_status' => 'drawn',
                    'drawn_at' => now(),
                    'draw_id' => $draw->id,
                ]);
                
                event(new RegistrationDrawn($registration->fresh()));
            }

            // Update not selected registrations
            foreach ($notSelected as $registration) {
                $registration->update([
                    'draw_id' => $draw->id,
                ]);
                
                event(new RegistrationNotDrawn($registration->fresh()));
            }

            // Dispatch draw executed event
            event(new DrawExecuted($draw));

            return $draw->load('registrations');
        });
    }

    /**
     * Select registrations using random algorithm with team atomicity
     */
    protected function selectRegistrations(
        Collection $registrations,
        int $availableSpots
    ): Collection {
        // Separate individual and team registrations
        $individuals = $registrations->whereNull('team_id');
        $teamRegistrations = $registrations->whereNotNull('team_id');

        // Group team registrations by team
        $teams = $teamRegistrations->groupBy('team_id');

        // Create selection pool (teams count as one unit)
        $pool = collect();

        // Add individuals to pool
        foreach ($individuals as $individual) {
            $pool->push([
                'type' => 'individual',
                'registrations' => collect([$individual]),
                'size' => 1,
            ]);
        }

        // Add teams to pool (atomic units)
        foreach ($teams as $teamId => $members) {
            $pool->push([
                'type' => 'team',
                'team_id' => $teamId,
                'registrations' => $members,
                'size' => $members->count(),
            ]);
        }

        // Shuffle pool for random selection
        $pool = $pool->shuffle();

        // Select until spots filled
        $selected = collect();
        $spotsUsed = 0;

        foreach ($pool as $unit) {
            // Check if we have enough spots for this unit
            if ($spotsUsed + $unit['size'] <= $availableSpots) {
                $selected = $selected->merge($unit['registrations']);
                $spotsUsed += $unit['size'];
            }

            // Stop if we've filled all available spots
            if ($spotsUsed >= $availableSpots) {
                break;
            }
        }

        return $selected;
    }

    /**
     * Get draw statistics for a track
     */
    public function getDrawStatistics(int $trackId): ?array
    {
        $draw = Draw::where('track_id', $trackId)->first();

        if (!$draw) {
            return null;
        }

        return [
            'executed_at' => $draw->executed_at,
            'executed_by' => $draw->executedBy?->name ?? 'Unknown',
            'total_registrations' => $draw->total_registrations,
            'total_drawn' => $draw->total_drawn,
            'total_not_drawn' => $draw->total_not_drawn,
            'available_spots' => $draw->available_spots,
            'success_rate' => $draw->success_rate,
        ];
    }

    /**
     * Check if a draw has been executed for a track
     */
    public function hasDrawBeenExecuted(int $trackId): bool
    {
        return Draw::where('track_id', $trackId)->exists();
    }
}
