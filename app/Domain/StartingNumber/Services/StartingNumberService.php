<?php

namespace App\Domain\StartingNumber\Services;

use App\Domain\StartingNumber\Events\StartingNumberAssigned;
use App\Domain\StartingNumber\Events\StartingNumberCleared;
use App\Domain\StartingNumber\Exceptions\NoAvailableNumberException;
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
     * Assign a number, persist it to the starting_numbers table, and dispatch the event.
     */
    public function assignAndSave(Registration $registration): ?StartingNumber
    {
        $number = $this->assignNumber($registration);

        if ($number === null) {
            return null;
        }

        $startingNumber = StartingNumber::create([
            'registration_id' => $registration->id,
            'number' => $number,
        ]);

        event(new StartingNumberAssigned($registration, $number));

        return $startingNumber;
    }

    /**
     * Clear a registration's starting number.
     */
    public function clearNumber(Registration $registration): void
    {
        $startingNumber = StartingNumber::where('registration_id', $registration->id)->first();

        if (! $startingNumber) {
            return;
        }

        $previousNumber = $startingNumber->number;
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
     * Queries starting_numbers joined to registrations (including soft-deleted rows
     * via withTrashed on the join subquery) so that numbers for soft-deleted
     * registrations are permanently retired and never reassigned.
     */
    private function findNextAvailableInRange(int $start, int $end, int $trackId): ?int
    {
        // We join against the registrations table without the soft-delete scope
        // by using a raw join. StartingNumber records are only hard-deleted when
        // their registration is hard-deleted (cascade), so soft-deleted registrations
        // still have their starting_numbers row intact — those numbers are retired.
        $usedNumbers = StartingNumber::join(
            \Illuminate\Support\Facades\DB::raw('(SELECT id, track_id FROM registrations) AS reg'),
            'starting_numbers.registration_id',
            '=',
            'reg.id'
        )
            ->where('reg.track_id', $trackId)
            ->whereBetween('starting_numbers.number', [$start, $end])
            ->pluck('starting_numbers.number')
            ->toArray();

        for ($i = $start; $i <= $end; $i++) {
            if (! in_array($i, $usedNumbers)) {
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
        return StartingNumber::join('registrations', 'starting_numbers.registration_id', '=', 'registrations.id')
            ->whereNull('registrations.deleted_at')
            ->where('registrations.track_id', $trackId)
            ->whereBetween('starting_numbers.number', [$start, $end])
            ->count();
    }
}
