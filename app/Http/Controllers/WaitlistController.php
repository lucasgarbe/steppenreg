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
            // Refresh the registration to get updated status
            $registration->refresh();
            
            // For teams, the current registration is the team captain who initiated the join
            // For individuals, it's just the individual registration
            // Always send email to the registration that initiated the waitlist join
            \App\Jobs\Mail\SendWaitlistConfirmation::dispatch($registration);

            return view('public.waitlist.success', compact('registration'));
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

        // Validate the simplified withdrawal form (no confirmation checkbox)
        $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        // Process withdrawal using the new method
        if ($withdrawalRequest->processWithdrawal($request->reason)) {
            // Send withdrawal confirmation email
            \App\Jobs\Mail\SendWithdrawalConfirmation::dispatch($registration);

            // Redirect to success page with success message
            return redirect()->route('withdraw.success')
                ->with('success', __('public.withdrawal.success.message'))
                ->with('registration_name', $registration->name);
        }

        return back()->withErrors(['general' => 'Failed to process withdrawal. Please try again.']);
    }

    public function withdrawSuccess(): View
    {
        // Check if user was redirected from a successful withdrawal
        if (!session()->has('success')) {
            return redirect()->route('registration.create');
        }

        $eventSettings = app(EventSettings::class);

        return view('public.withdraw.success', [
            'eventSettings' => $eventSettings,
            'registration_name' => session('registration_name')
        ]);
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


    private function recalculateWaitlistPositions(int $trackId): void
    {
        // Position calculation is no longer needed in pool-based system
        // This method is kept for backward compatibility but does nothing
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
