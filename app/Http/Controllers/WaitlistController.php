<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Settings\EventSettings;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WaitlistController extends Controller
{
    public function showJoinForm(string $token): View|RedirectResponse
    {
        $registration = Registration::findByWaitlistToken($token);
        
        if (!$registration) {
            return $this->invalidTokenResponse('Waitlist link not found or expired.');
        }

        if (!$registration->can_join_waitlist) {
            return $this->alreadyProcessedResponse($registration, 'waitlist');
        }

        $eventSettings = app(EventSettings::class);
        
        return view('public.waitlist.join', compact('registration', 'token', 'eventSettings'));
    }

    public function joinWaitlist(string $token, Request $request): RedirectResponse|View
    {
        $registration = Registration::findByWaitlistToken($token);
        
        if (!$registration) {
            return redirect()->route('registration.create')
                ->withErrors(['token' => 'Waitlist link not found or expired.']);
        }

        if (!$registration->can_join_waitlist) {
            return $this->alreadyProcessedResponse($registration, 'waitlist');
        }

        // Join waitlist
        if ($registration->joinWaitlist()) {
            $position = $registration->getWaitlistPosition();
            
            return view('public.waitlist.success', compact('registration', 'position'));
        }

        return back()->withErrors(['general' => 'Failed to join waitlist. Please try again.']);
    }

    public function showWithdrawForm(string $token): View|RedirectResponse
    {
        $registration = Registration::findByWithdrawToken($token);
        
        if (!$registration) {
            return $this->invalidTokenResponse('Withdrawal link not found or expired.');
        }

        if (!$registration->can_withdraw) {
            return $this->alreadyProcessedResponse($registration, 'withdraw');
        }

        $eventSettings = app(EventSettings::class);
        
        return view('public.withdraw.form', compact('registration', 'token', 'eventSettings'));
    }

    public function withdraw(string $token, Request $request): RedirectResponse|View
    {
        $registration = Registration::findByWithdrawToken($token);
        
        if (!$registration) {
            return redirect()->route('registration.create')
                ->withErrors(['token' => 'Withdrawal link not found or expired.']);
        }

        if (!$registration->can_withdraw) {
            return $this->alreadyProcessedResponse($registration, 'withdraw');
        }

        $request->validate([
            'reason' => 'nullable|string|max:1000',
            'confirm' => 'required|accepted',
        ]);

        // Process withdrawal
        if ($registration->withdraw($request->reason)) {
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
        // Find next person in waitlist for this track
        $nextParticipant = Registration::where('track_id', $trackId)
            ->where('draw_status', 'waitlist')
            ->whereNull('withdrawn_at')
            ->orderBy('waitlist_registered_at', 'asc')
            ->first();

        if (!$nextParticipant) {
            return null;
        }

        // Promote to drawn status
        $nextParticipant->draw_status = 'drawn';
        $nextParticipant->drawn_at = now();
        $nextParticipant->save();

        // TODO: Send "Spot Available" email to promoted participant
        // This would be implemented when we update the mail system

        return $nextParticipant;
    }

    public function status(string $token): View|RedirectResponse
    {
        // Check if it's a waitlist or withdraw token
        $registration = Registration::findByWaitlistToken($token) 
                     ?? Registration::findByWithdrawToken($token);
        
        if (!$registration) {
            return $this->invalidTokenResponse('Link not found or expired.');
        }

        $eventSettings = app(EventSettings::class);
        
        return view('public.waitlist.status', compact('registration', 'eventSettings'));
    }
}
