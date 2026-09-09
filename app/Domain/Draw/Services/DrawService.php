<?php

namespace App\Domain\Draw\Services;

use App\Domain\Draw\Events\DrawExecuted;
use App\Domain\Draw\Events\RegistrationDrawn;
use App\Domain\Draw\Events\RegistrationNotDrawn;
use App\Domain\Draw\Exceptions\InsufficientRegistrationsException;
use App\Domain\Draw\Models\DrawStatus;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\Activity;

class DrawService
{
    /**
     * Mark a registration (and its team members) as drawn.
     */
    public function markDrawn(Registration $registration): void
    {
        $this->setDrawStatus($registration, 'drawn');
    }

    /**
     * Mark a registration (and its team members) as not drawn.
     */
    public function markNotDrawn(Registration $registration): void
    {
        $this->setDrawStatus($registration, 'not_drawn');
    }

    /**
     * Execute a lottery draw for a track, selecting registrations up to availableSpots.
     *
     * @return array{track_id: int, total_registrations: int, total_drawn: int, total_not_drawn: int, available_spots: int, executed_by_user_id: ?int}
     */
    public function executeDraw(
        int $trackId,
        int $availableSpots,
        ?int $executedByUserId = null,
        array $config = []
    ): array {
        $registrations = Registration::where('track_id', $trackId)
            ->whereDoesntHave('drawStatus')
            ->with('team')
            ->get();

        if ($registrations->isEmpty()) {
            throw new InsufficientRegistrationsException(
                'No registrations available for draw'
            );
        }

        $selected = $this->selectRegistrations($registrations, $availableSpots);
        $notSelected = $registrations->diff($selected);

        DB::transaction(function () use ($selected, $notSelected) {
            $now = now();

            foreach ($selected as $registration) {
                $affected = $this->getAffectedRegistrations($registration);

                foreach ($affected as $member) {
                    DrawStatus::updateOrCreate(
                        ['registration_id' => $member->id],
                        ['status' => 'drawn', 'drawn_at' => $now]
                    );
                }

                foreach ($affected as $member) {
                    event(new RegistrationDrawn($member->fresh()));
                }
            }

            foreach ($notSelected as $registration) {
                $affected = $this->getAffectedRegistrations($registration);

                foreach ($affected as $member) {
                    DrawStatus::updateOrCreate(
                        ['registration_id' => $member->id],
                        ['status' => 'not_drawn', 'drawn_at' => null]
                    );
                }

                foreach ($affected as $member) {
                    event(new RegistrationNotDrawn($member->fresh()));
                }
            }
        });

        $result = [
            'track_id' => $trackId,
            'total_registrations' => $registrations->count(),
            'total_drawn' => $selected->count(),
            'total_not_drawn' => $notSelected->count(),
            'available_spots' => $availableSpots,
            'executed_by_user_id' => $executedByUserId,
        ];

        event(new DrawExecuted($result));

        Activity::event('draw_executed')
            ->withProperties($result)
            ->log('Draw executed for track');

        Log::info('DrawService: executeDraw completed', $result);

        return $result;
    }

    /**
     * Return a registration (and its team members) to the undecided pool by
     * removing its draw status, making it eligible for the next draw.
     */
    public function resetToPool(Registration $registration): int
    {
        return DB::transaction(function () use ($registration) {
            $registrations = $this->getAffectedRegistrations($registration);

            $deleted = DrawStatus::whereIn(
                'registration_id',
                $registrations->pluck('id')
            )->delete();

            Activity::event('registration_reset_to_pool')
                ->performedOn($registration)
                ->withProperties([
                    'registration_id' => $registration->id,
                    'affected_count' => $registrations->count(),
                ])
                ->log('Registration reset to draw pool');

            Log::info('DrawService: resetToPool', [
                'registration_id' => $registration->id,
                'affected_count' => $registrations->count(),
                'removed_statuses' => $deleted,
            ]);

            return $deleted;
        });
    }

    protected function setDrawStatus(Registration $registration, string $status): void
    {
        DB::transaction(function () use ($registration, $status) {
            $registrations = $this->getAffectedRegistrations($registration);
            $now = $status === 'drawn' ? now() : null;

            foreach ($registrations as $affected) {
                DrawStatus::updateOrCreate(
                    ['registration_id' => $affected->id],
                    ['status' => $status, 'drawn_at' => $now]
                );
            }

            $eventClass = $status === 'drawn'
                ? RegistrationDrawn::class
                : RegistrationNotDrawn::class;

            foreach ($registrations as $affected) {
                event(new $eventClass($affected->fresh()));
            }

            $activityEvent = $status === 'drawn'
                ? 'registration_marked_drawn'
                : 'registration_marked_not_drawn';

            Activity::event($activityEvent)
                ->performedOn($registration)
                ->withProperties([
                    'registration_id' => $registration->id,
                    'affected_count' => $registrations->count(),
                ])
                ->log("Registration marked as {$status}");

            Log::info("DrawService: setDrawStatus({$status})", [
                'trigger_registration_id' => $registration->id,
                'affected_count' => $registrations->count(),
            ]);
        });
    }

    protected function getAffectedRegistrations(Registration $registration): Collection
    {
        if (! $registration->team_id) {
            return collect([$registration]);
        }

        return Registration::where('team_id', $registration->team_id)->get();
    }

    protected function selectRegistrations(
        Collection $registrations,
        int $availableSpots
    ): Collection {
        $individuals = $registrations->whereNull('team_id');
        $teamRegistrations = $registrations->whereNotNull('team_id');

        $teams = $teamRegistrations->groupBy('team_id');

        $pool = collect();

        foreach ($individuals as $individual) {
            $pool->push([
                'type' => 'individual',
                'registrations' => collect([$individual]),
                'size' => 1,
            ]);
        }

        foreach ($teams as $teamId => $members) {
            $pool->push([
                'type' => 'team',
                'team_id' => $teamId,
                'registrations' => $members,
                'size' => $members->count(),
            ]);
        }

        $pool = $pool->shuffle();

        $selected = collect();
        $spotsUsed = 0;

        foreach ($pool as $unit) {
            if ($spotsUsed + $unit['size'] <= $availableSpots) {
                $selected = $selected->merge($unit['registrations']);
                $spotsUsed += $unit['size'];
            }

            if ($spotsUsed >= $availableSpots) {
                break;
            }
        }

        return $selected;
    }
}
