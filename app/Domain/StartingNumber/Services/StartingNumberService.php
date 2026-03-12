<?php

namespace App\Domain\StartingNumber\Services;

use App\Domain\StartingNumber\Events\StartingNumberAssigned;
use App\Domain\StartingNumber\Events\StartingNumberCleared;
use App\Domain\StartingNumber\Exceptions\NoAvailableNumberException;
use App\Domain\StartingNumber\Models\Bib;
use App\Domain\StartingNumber\Models\StartingNumber;
use App\Domain\StartingNumber\Models\TrackStartingNumberRange;
use App\Models\Registration;
use Illuminate\Support\Facades\Log;

class StartingNumberService
{
    /**
     * Compute the next available starting number for a registration.
     *
     * Tries the main range first, falls through to overflow automatically.
     * Does not check draw_status — any registration is eligible.
     *
     * @throws NoAvailableNumberException when both main and overflow ranges are exhausted.
     */
    public function assignNumber(Registration $registration): ?int
    {
        if (! config('steppenreg.features.starting_numbers', true)) {
            return null;
        }

        $range = TrackStartingNumberRange::forTrack($registration->track_id)->first();

        if (! $range) {
            Log::warning('StartingNumberService: no range configured for track', [
                'track_id' => $registration->track_id,
                'registration_id' => $registration->id,
            ]);

            return null;
        }

        $number = $this->findNextAvailableInRange($range->range_start, $range->range_end, $registration->track_id);

        if ($number === null) {
            $number = $this->findNextAvailableInRange($range->overflow_start, $range->overflow_end, $registration->track_id);
        }

        if ($number === null) {
            throw new NoAvailableNumberException($registration->track_id);
        }

        return $number;
    }

    /**
     * Assign a number, persist it (creating or reusing the Bib record), and dispatch the event.
     *
     * If a Bib already exists for this (number, track_id) — e.g. a previous participant
     * used the same bib — we reuse it so the tag_id is preserved.
     */
    public function assignAndSave(Registration $registration): ?StartingNumber
    {
        $number = $this->assignNumber($registration);

        if ($number === null) {
            return null;
        }

        $bib = Bib::firstOrCreate(
            ['number' => $number]
        );

        $startingNumber = StartingNumber::create([
            'registration_id' => $registration->id,
            'bib_id' => $bib->id,
        ]);

        event(new StartingNumberAssigned($registration, $number));

        return $startingNumber;
    }

    /**
     * Clear a registration's starting number assignment.
     *
     * The Bib record is intentionally kept — it may have a tag_id assigned,
     * and the physical bib will be reused by future participants.
     */
    public function clearNumber(Registration $registration): void
    {
        $startingNumber = StartingNumber::where('registration_id', $registration->id)->first();

        if (! $startingNumber) {
            return;
        }

        $previousNumber = $startingNumber->bib?->number;
        $startingNumber->delete();

        event(new StartingNumberCleared($registration, $previousNumber));
    }

    /**
     * Bulk assign starting numbers to multiple registrations.
     * Only processes registrations that do not already have a number.
     */
    public function bulkAssignNumbers(array $registrationIds): array
    {
        $results = [
            'assigned' => [],
            'failed' => [],
            'skipped' => [],
        ];

        foreach ($registrationIds as $id) {
            $registration = Registration::find($id);

            if (! $registration) {
                continue;
            }

            // Skip if already has a number
            if (StartingNumber::where('registration_id', $registration->id)->exists()) {
                $results['skipped'][] = $id;

                continue;
            }

            try {
                $startingNumber = $this->assignAndSave($registration);

                if ($startingNumber) {
                    $results['assigned'][] = $id;
                } else {
                    $results['failed'][] = $id;
                }
            } catch (NoAvailableNumberException) {
                $results['failed'][] = $id;
            }
        }

        return $results;
    }

    /**
     * Format a starting number for display (e.g. 1 -> "001").
     */
    public function formatNumber(?int $number): ?string
    {
        return $number !== null ? sprintf('%03d', $number) : null;
    }

    /**
     * Get the formatted label for a registration's starting number.
     */
    public function getNumberLabel(Registration $registration): ?string
    {
        $number = $registration->startingNumber?->number;

        return $this->formatNumber($number);
    }

    /**
     * Classify the number type: 'main', 'overflow', or null if no number/range.
     */
    public function getNumberType(Registration $registration): ?string
    {
        $number = $registration->startingNumber?->number;

        if ($number === null || ! $registration->track_id) {
            return null;
        }

        $range = TrackStartingNumberRange::forTrack($registration->track_id)->first();

        if (! $range) {
            return null;
        }

        if ($number >= $range->range_start && $number <= $range->range_end) {
            return 'main';
        }

        if ($number >= $range->overflow_start && $number <= $range->overflow_end) {
            return 'overflow';
        }

        return null;
    }

    /**
     * Get usage stats for both main and overflow ranges of a track.
     */
    public function getRangeStatus(int $trackId): array
    {
        $range = TrackStartingNumberRange::forTrack($trackId)->first();

        if (! $range) {
            return [
                'track_id' => $trackId,
                'configured' => false,
            ];
        }

        $mainUsed = $this->countUsedInRange($range->range_start, $range->range_end, $trackId);
        $overflowUsed = $this->countUsedInRange($range->overflow_start, $range->overflow_end, $trackId);
        $mainCapacity = $range->main_capacity;
        $overflowCapacity = $range->overflow_capacity;

        $status = match (true) {
            $overflowUsed > 0 => 'overflow_active',
            $mainUsed >= $mainCapacity => 'main_full',
            ($mainCapacity > 0 && ($mainUsed / $mainCapacity) >= 0.9) => 'critical',
            ($mainCapacity > 0 && ($mainUsed / $mainCapacity) >= 0.75) => 'warning',
            default => 'normal',
        };

        return [
            'track_id' => $trackId,
            'configured' => true,
            'main' => [
                'start' => $range->range_start,
                'end' => $range->range_end,
                'used' => $mainUsed,
                'capacity' => $mainCapacity,
            ],
            'overflow' => [
                'start' => $range->overflow_start,
                'end' => $range->overflow_end,
                'used' => $overflowUsed,
                'capacity' => $overflowCapacity,
            ],
            'status' => $status,
        ];
    }

    /**
     * Find the next available integer in [start, end] for the given track.
     *
     * A bib is "taken" only when it has at least one starting_numbers assignment row
     * (including those linked to soft-deleted registrations, since their row persists
     * until the registration is hard-deleted). Bibs with no assignment rows are free
     * to be reused from the bottom of the range — this ensures that after a full
     * assignment reset the numbering starts from the beginning again.
     */
    private function findNextAvailableInRange(int $start, int $end, int $trackId): ?int
    {
        // Find numbers already assigned to registrations in this track
        $takenNumbers = StartingNumber::join('bibs', 'starting_numbers.bib_id', '=', 'bibs.id')
            ->join('registrations', 'starting_numbers.registration_id', '=', 'registrations.id')
            ->where('registrations.track_id', $trackId)
            ->whereBetween('bibs.number', [$start, $end])
            ->pluck('bibs.number')
            ->toArray();

        for ($i = $start; $i <= $end; $i++) {
            if (! in_array($i, $takenNumbers)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Count how many numbers in [start, end] are currently assigned for the given track.
     * Only counts active (non-soft-deleted) registrations for capacity reporting.
     */
    private function countUsedInRange(int $start, int $end, int $trackId): int
    {
        return StartingNumber::join('bibs', 'starting_numbers.bib_id', '=', 'bibs.id')
            ->join('registrations', 'starting_numbers.registration_id', '=', 'registrations.id')
            ->whereNull('registrations.deleted_at')
            ->where('registrations.track_id', $trackId)
            ->whereBetween('bibs.number', [$start, $end])
            ->count();
    }
}
