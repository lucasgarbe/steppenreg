<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PublicRegistrationController extends Controller
{
    public function create()
    {
        $eventSettings = app(EventSettings::class);
        $tracks = $eventSettings->tracks ?? [];

        $applicationState = $eventSettings->application_state;
        $isPriorityPeriod = ($applicationState === 'priority_period');
        $isLiveEvent = $eventSettings->isLiveEvent();

        // Handle closed state
        if ($applicationState === 'closed') {
            return view('public.registration.closed', [
                'eventSettings' => $eventSettings,
                'state' => $applicationState,
            ]);
        }

        if ($isLiveEvent) {
            return view('public.registration.live-event', [
                'eventSettings' => $eventSettings,
            ]);
        }

        // Get available categories based on state
        $availableCategories = $eventSettings->getAvailableGenderCategories();

        // Get categories with messages for display
        $categoriesWithMessages = collect($availableCategories)
            ->filter(fn ($cat) => ! empty($cat['message'][app()->getLocale()]))
            ->values()
            ->toArray();

        return view('public.registration.create', compact(
            'tracks',
            'eventSettings',
            'applicationState',
            'isPriorityPeriod',
            'availableCategories',
            'categoriesWithMessages'
        ));
    }

    public function store(Request $request)
    {
        $eventSettings = app(EventSettings::class);

        // Check if registration is allowed
        if (
            $eventSettings->application_state === 'closed' ||
            $eventSettings->isLiveEvent()
        ) {
            return redirect()->route('registration.create')
                ->withErrors(['general' => 'Registration is currently closed.']);
        }

        // Get available gender keys based on current state
        $availableGenderKeys = collect($eventSettings->getAvailableGenderCategories())
            ->pluck('key')
            ->toArray();

        // Validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'age' => 'required|integer|min:1|max:99',
            'gender' => 'required|string|in:'.implode(',', $availableGenderKeys),
            'track_id' => 'required|integer',
            'team_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];

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

        // Check for exact duplicate registration within 5-minute window
        $fiveMinutesAgo = now()->subMinutes(5);

        $existingRegistration = Registration::where('name', $validated['name'])
            ->where('email', $validated['email'])
            ->where('track_id', $validated['track_id'])
            ->where('age', $validated['age'])
            ->where('gender', $validated['gender'])
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->whereNull('deleted_at')
            ->first();

        if ($existingRegistration) {
            // Idempotent behavior: redirect to success page as if registration just happened
            return redirect()->route('registration.success')
                ->with('success', 'Registration completed successfully!');
        }

        $teamId = null;

        // Handle team assignment if team name provided
        if ($request->filled('team_name')) {
            $teamName = trim($request->team_name);
            $enforceSameTrack = $eventSettings->enforce_same_track_for_teams;

            try {
                // Use transaction with locking to prevent race conditions
                $teamId = DB::transaction(function () use ($teamName, $enforceSameTrack, $request, $eventSettings) {
                    $selectedTrackId = (int) $request->track_id;

                    // Lock-based check to prevent duplicate team creation.
                    // When enforcing same track, scope lookup to the selected track only.
                    // When not enforcing, match by name globally across all tracks.
                    $existingTeam = Team::withoutTrashed()
                        ->whereRaw('LOWER(name) = LOWER(?)', [$teamName])
                        ->when($enforceSameTrack, fn ($query) => $query->where('track_id', $selectedTrackId))
                        ->lockForUpdate()
                        ->first();

                    if ($existingTeam) {
                        // When enforcing same track, check if user selected different track
                        if ($enforceSameTrack && $existingTeam->track_id != $selectedTrackId) {
                            $existingTrackName = $this->getTrackName($existingTeam->track_id);
                            $selectedTrackName = $this->getTrackName($selectedTrackId);

                            throw new \Exception("Team '{$teamName}' already exists on {$existingTrackName}, but you selected {$selectedTrackName}. Please choose a different team name or change your track selection.");
                        }

                        // Check if team is full (only if max_members is set)
                        $currentMembers = $existingTeam->registrations()->count();
                        if ($existingTeam->max_members !== null && $currentMembers >= $existingTeam->max_members) {
                            throw new \Exception("Team '{$teamName}' is already full ({$existingTeam->max_members} members).");
                        }

                        return $existingTeam->id;
                    }

                    // Create new team (safe because we hold the lock).
                    // Always store the selected track_id so teams retain their track association.
                    $team = Team::create([
                        'name' => $teamName,
                        'max_members' => $eventSettings->default_team_max_members,
                        'track_id' => $selectedTrackId,
                    ]);

                    return $team->id;
                });
            } catch (\Throwable $e) {
                return back()
                    ->withErrors(['team_name' => $e->getMessage()])
                    ->withInput();
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
