<?php

namespace App\Observers;

use App\Jobs\Mail\SendRegistrationConfirmation;
use App\Models\Registration;
use Illuminate\Support\Facades\Log;

class RegistrationObserver
{
    private static bool $syncInProgress = false;

    public function created(Registration $registration): void
    {
        SendRegistrationConfirmation::dispatch($registration);
    }

    /**
     * Handle the Registration "updating" event.
     * Synchronizes draw status across all team members when a registration's draw_status changes.
     */
    public function updating(Registration $registration): void
    {
        // Skip if we're already in the middle of synchronization to prevent infinite loops
        if (self::$syncInProgress) {
            return;
        }

        // Check if draw_status is being changed
        if (! $registration->isDirty('draw_status')) {
            return;
        }

        // Only synchronize if this registration is part of a team
        if (! $registration->team_id) {
            return;
        }

        $newDrawStatus = $registration->draw_status;
        $newDrawnAt = $registration->drawn_at;

        Log::info('RegistrationObserver: Synchronizing team draw status', [
            'registration_id' => $registration->id,
            'registration_email' => $registration->email,
            'team_id' => $registration->team_id,
            'new_draw_status' => $newDrawStatus,
        ]);

        // Set flag to prevent recursion
        self::$syncInProgress = true;

        try {
            // Get all team members except the current registration
            $teamMembers = Registration::where('team_id', $registration->team_id)
                ->where('id', '!=', $registration->id)
                ->get();

            foreach ($teamMembers as $teamMember) {
                // Only update if the status is actually different
                if ($teamMember->draw_status !== $newDrawStatus) {
                    $teamMember->draw_status = $newDrawStatus;
                    $teamMember->drawn_at = $newDrawnAt;
                    $teamMember->saveQuietly(); // Use saveQuietly to avoid triggering events

                    Log::info('RegistrationObserver: Updated team member draw status', [
                        'team_member_id' => $teamMember->id,
                        'team_member_email' => $teamMember->email,
                        'draw_status' => $newDrawStatus,
                    ]);
                }
            }

            Log::info('RegistrationObserver: Team synchronization completed', [
                'team_id' => $registration->team_id,
                'members_updated' => $teamMembers->count(),
            ]);
        } finally {
            // Always reset the flag, even if an exception occurs
            self::$syncInProgress = false;
        }
    }

    public function updated(Registration $registration): void
    {
        // NOTE: Draw notifications are now sent manually via admin action
        // This prevents automatic emails during bulk draw operations

        // NOTE: Starting number assignment is now handled by the StartingNumber domain
        // via event listeners (AssignStartingNumberOnDrawn listens to RegistrationDrawn)

        // Log draw status changes for audit purposes
        if ($registration->wasChanged('draw_status')) {
            Log::info('RegistrationObserver: Draw status updated', [
                'registration_id' => $registration->id,
                'email' => $registration->email,
                'old_status' => $registration->getOriginal('draw_status'),
                'new_status' => $registration->draw_status,
                'team_id' => $registration->team_id,
            ]);
        }
    }
}
