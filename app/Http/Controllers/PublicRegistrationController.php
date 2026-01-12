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
                'state' => $applicationState,
            ]);
        }

        if ($isLiveEvent) {
            // Live event - show special message
            return view('public.registration.live-event', [
                'eventSettings' => $eventSettings,
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
            'team_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];

        // If only FLINTA* registration is open, restrict gender selection
        if ($eventSettings->isOpenForFlintaOnly()) {
            $rules['gender'] = 'required|string|in:flinta';
        }

        // Build dynamic validation rules and custom error messages for custom questions
        $customQuestions = $eventSettings->custom_questions ?? [];
        $customMessages = [];

        // Get fallback validation messages from translation files
        $fallbackMessages = trans('public.registration.custom_questions.validation');

        foreach ($customQuestions as $question) {
            $key = $question['key'];
            $fieldRules = [];
            $fieldName = "custom_answers.{$key}";

            // Get translations for current locale
            $locale = app()->getLocale();
            $translations = $question['translations'][$locale] ?? $question['translations']['en'] ?? [];

            // Required/optional
            if ($question['required'] ?? false) {
                $fieldRules[] = 'required';

                // Custom required error message or fallback
                if (! empty($translations['error_required'])) {
                    $customMessages["{$fieldName}.required"] = $translations['error_required'];
                } else {
                    $customMessages["{$fieldName}.required"] = $fallbackMessages['required'] ?? 'Dieses Feld ist erforderlich.';
                }
            } else {
                $fieldRules[] = 'nullable';
            }

            // Type-specific validation
            switch ($question['type']) {
                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:255';

                    // Custom invalid error for email or fallback
                    if (! empty($translations['error_invalid'])) {
                        $customMessages["{$fieldName}.email"] = $translations['error_invalid'];
                    } else {
                        $customMessages["{$fieldName}.email"] = $fallbackMessages['email'] ?? 'Bitte gib eine gültige E-Mail-Adresse ein.';
                    }

                    // Custom max length error or fallback
                    if (! empty($translations['error_max'])) {
                        $customMessages["{$fieldName}.max"] = $translations['error_max'];
                    } else {
                        $customMessages["{$fieldName}.max"] = $fallbackMessages['max']['string'] ?? ':attribute darf maximal :max Zeichen lang sein.';
                    }
                    break;

                case 'number':
                    $fieldRules[] = 'numeric';

                    // Custom invalid error for numeric or fallback
                    if (! empty($translations['error_invalid'])) {
                        $customMessages["{$fieldName}.numeric"] = $translations['error_invalid'];
                    } else {
                        $customMessages["{$fieldName}.numeric"] = $fallbackMessages['numeric'] ?? 'Bitte gib eine Zahl ein.';
                    }
                    break;

                case 'text':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';

                    // Custom max length error or fallback
                    if (! empty($translations['error_max'])) {
                        $customMessages["{$fieldName}.max"] = $translations['error_max'];
                    } else {
                        $customMessages["{$fieldName}.max"] = $fallbackMessages['max']['string'] ?? 'Die Eingabe darf maximal :max Zeichen lang sein.';
                    }
                    break;

                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:1000';

                    // Custom max length error or fallback
                    if (! empty($translations['error_max'])) {
                        $customMessages["{$fieldName}.max"] = $translations['error_max'];
                    } else {
                        $customMessages["{$fieldName}.max"] = $fallbackMessages['max']['string'] ?? 'Die Eingabe darf maximal :max Zeichen lang sein.';
                    }
                    break;

                case 'date':
                    $fieldRules[] = 'date';

                    // Custom invalid error for date or fallback
                    if (! empty($translations['error_invalid'])) {
                        $customMessages["{$fieldName}.date"] = $translations['error_invalid'];
                    } else {
                        $customMessages["{$fieldName}.date"] = $fallbackMessages['date'] ?? 'Bitte gib ein gültiges Datum ein.';
                    }
                    break;

                case 'checkbox':
                    $fieldRules[] = 'array';

                    // Custom invalid error for array or fallback
                    if (! empty($translations['error_invalid'])) {
                        $customMessages["{$fieldName}.array"] = $translations['error_invalid'];
                    } else {
                        $customMessages["{$fieldName}.array"] = $fallbackMessages['array'] ?? 'Bitte wähle gültige Optionen aus.';
                    }
                    break;

                case 'select':
                case 'radio':
                    // Validate against allowed options
                    $allowedValues = collect($question['options'] ?? [])
                        ->pluck('value')
                        ->toArray();
                    if (! empty($allowedValues)) {
                        $fieldRules[] = Rule::in($allowedValues);

                        // Custom invalid error for select/radio or fallback
                        if (! empty($translations['error_invalid'])) {
                            $customMessages["{$fieldName}.in"] = $translations['error_invalid'];
                        } else {
                            $customMessages["{$fieldName}.in"] = $fallbackMessages['in'] ?? 'Der ausgewählte Wert ist ungültig.';
                        }
                    }
                    break;
            }

            // Custom validation rules from question config
            if (! empty($question['validation']) && is_array($question['validation'])) {
                $fieldRules = array_merge($fieldRules, $question['validation']);
            }

            $rules[$fieldName] = $fieldRules;
        }

        $validated = $request->validate($rules, $customMessages);

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
            'name' => $validated['name'],
            'email' => $validated['email'],
            'age' => $validated['age'],
            'gender' => $validated['gender'],
            'track_id' => $validated['track_id'],
            'team_id' => $teamId,
            'notes' => $validated['notes'] ?? null,
            'custom_answers' => $validated['custom_answers'] ?? null,
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
