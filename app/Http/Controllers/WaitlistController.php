<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\WaitlistEntry;
use App\Models\WithdrawalRequest;
use App\Settings\EventSettings;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WaitlistController extends Controller
{
    public function showJoinForm(string $token): View|RedirectResponse
    {
        $waitlistEntry = WaitlistEntry::findByToken($token);
        $registration = $waitlistEntry?->registration;
        
        if (!$registration || !$waitlistEntry) {
            return $this->invalidTokenResponse('Waitlist link not found or expired.');
        }

        if (!$waitlistEntry->can_join) {
            return $this->alreadyProcessedResponse($registration, 'waitlist');
        }

        $eventSettings = app(EventSettings::class);
        
        return view('public.waitlist.join', compact('registration', 'token', 'eventSettings'));
    }

    public function joinWaitlist(string $token, Request $request): RedirectResponse|View
    {
        $waitlistEntry = WaitlistEntry::findByToken($token);
        $registration = $waitlistEntry?->registration;
        
        if (!$registration || !$waitlistEntry) {
            return redirect()->route('registration.create')
                ->withErrors(['token' => 'Waitlist link not found or expired.']);
        }

        if (!$waitlistEntry->can_join) {
            return $this->alreadyProcessedResponse($registration, 'waitlist');
        }

        // Join waitlist using the Registration model method
        if ($registration->joinWaitlist()) {
            $position = $registration->getWaitlistPosition();
            
            return view('public.waitlist.success', compact('registration', 'position'));
        }

        return back()->withErrors(['general' => 'Failed to join waitlist. Please try again.']);
    }

    public function showWithdrawForm(string $token): View|RedirectResponse
    {
        $withdrawalRequest = WithdrawalRequest::findByToken($token);
        $registration = $withdrawalRequest?->registration;
        
        if (!$registration || !$withdrawalRequest) {
            return $this->invalidTokenResponse('Withdrawal link not found or expired.');
        }

        if (!$withdrawalRequest->can_withdraw) {
            return $this->alreadyProcessedResponse($registration, 'withdraw');
        }

        $eventSettings = app(EventSettings::class);
        
        return view('public.withdraw.form', compact('registration', 'token', 'eventSettings'));
    }

    public function withdraw(string $token, Request $request): RedirectResponse|View
    {
        $withdrawalRequest = WithdrawalRequest::findByToken($token);
        $registration = $withdrawalRequest?->registration;
        
        if (!$registration || !$withdrawalRequest) {
            return redirect()->route('registration.create')
                ->withErrors(['token' => 'Withdrawal link not found or expired.']);
        }

        if (!$withdrawalRequest->can_withdraw) {
            return $this->alreadyProcessedResponse($registration, 'withdraw');
        }

        $request->validate([
            'reason' => 'nullable|string|max:2000',
            'confirm' => 'required|accepted',
        ]);

        // Process withdrawal using the new method
        if ($withdrawalRequest->processWithdrawal($request->reason)) {
            // Try to promote next person on waitlist
            $this->promoteNextWaitlistParticipant($registration->track_id);
            
            return view('public.withdraw.success', compact('registration'));
        }

        return back()->withErrors(['general' => 'Failed to process withdrawal. Please try again.']);
    }

    private function invalidTokenResponse(string $message): RedirectResponse
    {
        return redirect()->route('registration.create')
            ->withErrors(['token' => $message]);
    }

    private function alreadyProcessedResponse(Registration $registration, string $action): View
    {
        $eventSettings = app(EventSettings::class);
        
        return view('public.waitlist.already-processed', compact(
            'registration', 
            'action', 
            'eventSettings'
        ));
    }

    private function promoteNextWaitlistParticipant(int $trackId): ?Registration
    {
        // Find next person in waitlist for this track using the new relationship
        $nextWaitlistEntry = WaitlistEntry::forTrack($trackId)
            ->active()
            ->orderedByRegistration()
            ->first();

        if (!$nextWaitlistEntry) {
            return null;
        }

        $nextParticipant = $nextWaitlistEntry->registration;

        // Promote to drawn status
        $nextParticipant->update([
            'draw_status' => 'drawn',
            'drawn_at' => now(),
            'promoted_from_waitlist_at' => now(),
        ]);

        // Recalculate waitlist positions for remaining entries
        $this->recalculateWaitlistPositions($trackId);

        // TODO: Send "Spot Available" email to promoted participant
        // This would be implemented when we update the mail system

        return $nextParticipant;
    }

    private function recalculateWaitlistPositions(int $trackId): void
    {
        $waitlistEntries = WaitlistEntry::forTrack($trackId)
            ->active()
            ->orderedByRegistration()
            ->get();

        foreach ($waitlistEntries as $index => $entry) {
            $entry->update(['position' => $index + 1]);
        }
    }

    public function status(string $token): View|RedirectResponse
    {
        // Check if it's a waitlist token
        $waitlistEntry = WaitlistEntry::findByToken($token);
        if ($waitlistEntry) {
            $registration = $waitlistEntry->registration;
        } else {
            // Check if it's a withdrawal token
            $withdrawalRequest = WithdrawalRequest::findByToken($token);
            $registration = $withdrawalRequest?->registration;
        }
        
        if (!$registration) {
            return $this->invalidTokenResponse('Link not found or expired.');
        }

        $eventSettings = app(EventSettings::class);
        
        return view('public.waitlist.status', compact('registration', 'eventSettings'));
    }
}
