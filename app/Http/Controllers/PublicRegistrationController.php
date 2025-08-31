<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicRegistrationController extends Controller
{
    public function create()
    {
        $eventSettings = app(EventSettings::class);
        $tracks = $eventSettings->tracks ?? [];

        // Check application state and determine access
        $applicationState = $eventSettings->application_state;
        $isFlintaOnly = $eventSettings->isOpenForFlintaOnly();
        $isLiveEvent = $eventSettings->isLiveEvent();

        // Handle different states
        if ($applicationState === 'closed' || $applicationState === 'closed_waitlist') {
            // Registration is closed (waitlist is managed via email links, not public form)
            return view('public.registration.closed', [
                'eventSettings' => $eventSettings,
                'state' => $applicationState
            ]);
        }

        if ($isLiveEvent) {
            // Live event - show special message
            return view('public.registration.live-event', [
                'eventSettings' => $eventSettings
            ]);
        }

        // Registration is open (either FLINTA* only or everyone)
        return view('public.registration.create', compact(
            'tracks',
            'eventSettings',
            'applicationState',
            'isFlintaOnly'
        ));
    }

    public function store(Request $request)
    {
        $eventSettings = app(EventSettings::class);

        // Check if registration is allowed
        if (
            $eventSettings->application_state === 'closed' ||
            $eventSettings->application_state === 'closed_waitlist' ||
            $eventSettings->isLiveEvent()
        ) {
            return redirect()->route('registration.create')
                ->withErrors(['general' => 'Registration is currently closed.']);
        }

        // Prepare validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'age' => 'required|integer|min:1|max:120',
            'gender' => 'required|string|in:flinta,all_gender',
            'track_id' => 'required|integer',
            'participation_count' => 'required|integer|min:1|max:20',
            'team_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];

        // If only FLINTA* registration is open, restrict gender selection
        if ($eventSettings->isOpenForFlintaOnly()) {
            $rules['gender'] = 'required|string|in:flinta';
        }

        $request->validate($rules);

        $teamId = null;

        // Handle team assignment if team name provided
        if ($request->filled('team_name')) {
            // Normalize team name (trim, case-insensitive)
            $teamName = trim($request->team_name);

            // First, check if team name exists on ANY track
            $existingTeam = Team::whereRaw('LOWER(name) = LOWER(?)', [$teamName])->first();

            if ($existingTeam) {
                // Team exists - check if it's on the same track (convert to int for proper comparison)
                if ($existingTeam->track_id != (int) $request->track_id) {
                    // Team exists but on different track - show error
                    $existingTrackName = $this->getTrackName($existingTeam->track_id);
                    $selectedTrackName = $this->getTrackName($request->track_id);

                    return back()
                        ->withErrors(['team_name' => "Team '{$teamName}' already exists on {$existingTrackName}, but you selected {$selectedTrackName}. Please choose a different team name or change your track selection."])
                        ->withInput();
                }

                // Team exists on same track - check if it's full
                $currentMembers = $existingTeam->registrations()->count();
                if ($currentMembers >= $existingTeam->max_members) {
                    return back()
                        ->withErrors(['team_name' => "Team '{$teamName}' is already full ({$existingTeam->max_members} members)."])
                        ->withInput();
                }

                $teamId = $existingTeam->id;
            } else {
                // Team doesn't exist - create new team
                $team = Team::create([
                    'name' => $teamName,
                    'max_members' => 5, // Default team size controlled by admin
                    'track_id' => $request->track_id,
                ]);
                $teamId = $team->id;
            }
        }

        // All public registrations start as not_drawn (waitlist is managed separately via email)
        $drawStatus = 'not_drawn';

        // Create registration
        Registration::create([
            'name' => $request->name,
            'email' => $request->email,
            'age' => $request->age,
            'gender' => $request->gender,
            'track_id' => $request->track_id,
            'participation_count' => $request->participation_count,
            'team_id' => $teamId,
            'notes' => $request->notes,
            'draw_status' => $drawStatus,
            'payed' => false,
            'starting' => false,
        ]);

        return redirect()->route('registration.success')->with('success', 'Registration completed successfully!');
    }

    public function success()
    {
        $eventSettings = app(EventSettings::class);
        return view('public.registration.success', [
            'eventSettings' => $eventSettings,
        ]);
    }

    /**
     * Get track name by ID for error messages
     */
    private function getTrackName(int $trackId): string
    {
        $eventSettings = app(EventSettings::class);
        $tracks = $eventSettings->tracks ?? [];

        foreach ($tracks as $track) {
            if ($track['id'] === $trackId) {
                return $track['name'];
            }
        }

        return "Track {$trackId}";
    }
}
