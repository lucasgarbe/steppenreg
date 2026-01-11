# Laravel Implementation Guide

## Laravel Project Structure

```
cycling-event-registration/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── ExecuteDrawCommand.php
│   ├── Domain/                          # Domain Layer
│   │   ├── Registration/
│   │   │   ├── Models/
│   │   │   │   ├── Registration.php
│   │   │   │   └── Team.php
│   │   │   ├── Events/
│   │   │   │   ├── RegistrationCreated.php
│   │   │   │   ├── RegistrationCancelled.php
│   │   │   │   ├── TeamFormed.php
│   │   │   │   └── TeamMemberAdded.php
│   │   │   ├── Enums/
│   │   │   │   └── RegistrationStatus.php
│   │   │   └── Exceptions/
│   │   │       ├── RegistrationClosedException.php
│   │   │       ├── TrackFullException.php
│   │   │       └── DuplicateRegistrationException.php
│   │   ├── Draw/
│   │   │   ├── Events/
│   │   │   │   ├── DrawExecuted.php
│   │   │   │   ├── ParticipantSelected.php
│   │   │   │   └── ParticipantNotSelected.php
│   │   │   ├── ValueObjects/
│   │   │   │   └── DrawResult.php
│   │   │   └── Exceptions/
│   │   │       ├── DrawAlreadyExecutedException.php
│   │   │       └── InsufficientRegistrationsException.php
│   │   ├── Waitlist/
│   │   │   ├── Models/
│   │   │   │   └── WaitlistEntry.php
│   │   │   ├── Events/
│   │   │   │   ├── EnrolledToWaitlist.php
│   │   │   │   └── PromotedFromWaitlist.php
│   │   │   └── Exceptions/
│   │   │       └── NoWaitlistEntriesException.php
│   │   └── Track/
│   │       ├── Models/
│   │       │   └── Track.php
│   │       └── Enums/
│   │           └── TrackStatus.php
│   ├── Services/                        # Application/Service Layer
│   │   ├── RegistrationService.php
│   │   ├── TeamService.php
│   │   ├── DrawService.php
│   │   ├── WaitlistService.php
│   │   └── NotificationService.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── RegistrationController.php
│   │   │   │   ├── TeamController.php
│   │   │   │   ├── TrackController.php
│   │   │   │   └── WaitlistController.php
│   │   │   └── Admin/
│   │   │       ├── DrawController.php
│   │   │       ├── RegistrationManagementController.php
│   │   │       └── ReportController.php
│   │   ├── Requests/
│   │   │   ├── StoreRegistrationRequest.php
│   │   │   ├── UpdateRegistrationRequest.php
│   │   │   └── ExecuteDrawRequest.php
│   │   ├── Resources/
│   │   │   ├── RegistrationResource.php
│   │   │   ├── TeamResource.php
│   │   │   └── WaitlistResource.php
│   │   └── Middleware/
│   │       └── EnsureRegistrationOpen.php
│   ├── Listeners/                       # Event Listeners
│   │   ├── SendRegistrationConfirmation.php
│   │   ├── HandleRegistrationCancellation.php
│   │   ├── SendDrawResultNotification.php
│   │   ├── EnrollToWaitlistAfterDraw.php
│   │   └── SendWaitlistPromotionNotification.php
│   ├── Mail/
│   │   ├── RegistrationConfirmation.php
│   │   ├── DrawResultNotification.php
│   │   ├── WaitlistEnrollment.php
│   │   └── WaitlistPromotion.php
│   ├── Jobs/
│   │   ├── ExecuteTrackDraw.php
│   │   └── PromoteFromWaitlist.php
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── EventServiceProvider.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_tracks_table.php
│   │   ├── 2024_01_01_000002_create_registrations_table.php
│   │   ├── 2024_01_01_000003_create_teams_table.php
│   │   └── 2024_01_01_000004_create_waitlist_entries_table.php
│   ├── factories/
│   │   ├── RegistrationFactory.php
│   │   └── TeamFactory.php
│   └── seeders/
│       ├── TrackSeeder.php
│       └── DatabaseSeeder.php
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   ├── Feature/
│   │   ├── Registration/
│   │   │   ├── CreateRegistrationTest.php
│   │   │   └── CancelRegistrationTest.php
│   │   ├── Draw/
│   │   │   └── ExecuteDrawTest.php
│   │   └── Waitlist/
│   │       └── WaitlistPromotionTest.php
│   └── Unit/
│       ├── Services/
│       │   ├── RegistrationServiceTest.php
│       │   ├── DrawServiceTest.php
│       │   └── WaitlistServiceTest.php
│       └── Models/
│           ├── RegistrationTest.php
│           └── TeamTest.php
├── config/
│   └── event-registration.php
└── resources/
    └── views/
        └── emails/
            ├── registration-confirmation.blade.php
            ├── draw-result.blade.php
            └── waitlist-promotion.blade.php
```

---

## Key Implementation Files

### 1. Models

#### Registration Model
```php
<?php

namespace App\Domain\Registration\Models;

use App\Domain\Track\Models\Track;
use App\Domain\Waitlist\Models\WaitlistEntry;
use App\Domain\Registration\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'track_id',
        'team_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'emergency_contact',
        'emergency_phone',
        'medical_info',
        'status',
        'registered_at',
    ];

    protected $casts = [
        'status' => RegistrationStatus::class,
        'registered_at' => 'datetime',
    ];

    // Relationships
    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function waitlistEntry(): HasOne
    {
        return $this->hasOne(WaitlistEntry::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', RegistrationStatus::Pending);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', RegistrationStatus::Confirmed);
    }

    public function scopeForTrack($query, int $trackId)
    {
        return $query->where('track_id', $trackId);
    }

    // Domain Methods
    public function isPending(): bool
    {
        return $this->status === RegistrationStatus::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === RegistrationStatus::Confirmed;
    }

    public function isInTeam(): bool
    {
        return $this->team_id !== null;
    }

    public function canBeCancelled(): bool
    {
        return !in_array($this->status, [
            RegistrationStatus::Cancelled,
        ]);
    }

    public function canBeUpdated(): bool
    {
        return in_array($this->status, [
            RegistrationStatus::Pending,
            RegistrationStatus::NotSelected,
            RegistrationStatus::Waitlisted,
        ]);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
```

#### Team Model
```php
<?php

namespace App\Domain\Registration\Models;

use App\Domain\Track\Models\Track;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'track_id',
        'captain_registration_id',
        'min_size',
        'max_size',
    ];

    protected $casts = [
        'min_size' => 'integer',
        'max_size' => 'integer',
    ];

    // Relationships
    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'captain_registration_id');
    }

    // Domain Methods
    public function currentSize(): int
    {
        return $this->registrations()->count();
    }

    public function isFull(): bool
    {
        return $this->currentSize() >= $this->max_size;
    }

    public function meetsMinimumSize(): bool
    {
        return $this->currentSize() >= $this->min_size;
    }

    public function isValid(): bool
    {
        return $this->meetsMinimumSize() && 
               $this->registrations()->get()->every(
                   fn($reg) => $reg->track_id === $this->track_id
               );
    }

    public function hasSpaceFor(int $members = 1): bool
    {
        return ($this->currentSize() + $members) <= $this->max_size;
    }
}
```

#### Track Model
```php
<?php

namespace App\Domain\Track\Models;

use App\Domain\Registration\Models\Registration;
use App\Domain\Registration\Models\Team;
use App\Domain\Track\Enums\TrackStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Track extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'distance',
        'capacity',
        'registration_opens_at',
        'registration_closes_at',
        'draw_date',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
        'draw_date' => 'datetime',
        'status' => TrackStatus::class,
    ];

    // Relationships
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    // Domain Methods
    public function isRegistrationOpen(): bool
    {
        $now = now();
        return $now->gte($this->registration_opens_at) &&
               $now->lte($this->registration_closes_at) &&
               $this->status === TrackStatus::Open;
    }

    public function confirmedCount(): int
    {
        return $this->registrations()
            ->confirmed()
            ->count();
    }

    public function availableSpots(): int
    {
        return max(0, $this->capacity - $this->confirmedCount());
    }

    public function isFull(): bool
    {
        return $this->availableSpots() === 0;
    }

    public function hasCapacityFor(int $spots): bool
    {
        return $this->availableSpots() >= $spots;
    }

    public function canExecuteDraw(): bool
    {
        return $this->status === TrackStatus::Closed &&
               now()->gte($this->draw_date) &&
               $this->registrations()->pending()->exists();
    }
}
```

### 2. Enums

#### RegistrationStatus Enum
```php
<?php

namespace App\Domain\Registration\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case NotSelected = 'not_selected';
    case Waitlisted = 'waitlisted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending Draw',
            self::Confirmed => 'Confirmed',
            self::NotSelected => 'Not Selected',
            self::Waitlisted => 'On Waitlist',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'yellow',
            self::Confirmed => 'green',
            self::NotSelected => 'red',
            self::Waitlisted => 'blue',
            self::Cancelled => 'gray',
        };
    }
}
```

#### TrackStatus Enum
```php
<?php

namespace App\Domain\Track\Enums;

enum TrackStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case DrawCompleted = 'draw_completed';
    case EventCompleted = 'event_completed';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Open => 'Registration Open',
            self::Closed => 'Registration Closed',
            self::DrawCompleted => 'Draw Completed',
            self::EventCompleted => 'Event Completed',
        };
    }
}
```

### 3. Services

#### RegistrationService
```php
<?php

namespace App\Services;

use App\Domain\Registration\Models\Registration;
use App\Domain\Registration\Models\Team;
use App\Domain\Registration\Enums\RegistrationStatus;
use App\Domain\Registration\Events\RegistrationCreated;
use App\Domain\Registration\Events\RegistrationCancelled;
use App\Domain\Registration\Exceptions\RegistrationClosedException;
use App\Domain\Registration\Exceptions\TrackFullException;
use App\Domain\Registration\Exceptions\DuplicateRegistrationException;
use App\Domain\Track\Models\Track;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RegistrationService
{
    public function __construct(
        private TeamService $teamService
    ) {}

    /**
     * Create a new registration
     */
    public function register(array $data): Registration
    {
        $track = Track::findOrFail($data['track_id']);

        // Validate registration is open
        if (!$track->isRegistrationOpen()) {
            throw new RegistrationClosedException(
                "Registration is closed for this track"
            );
        }

        // Check for duplicate registration
        if ($this->hasDuplicateRegistration($data['email'], $track->id)) {
            throw new DuplicateRegistrationException(
                "This email is already registered for this track"
            );
        }

        return DB::transaction(function () use ($data, $track) {
            // Create registration
            $registration = Registration::create([
                'track_id' => $track->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'emergency_contact' => $data['emergency_contact'],
                'emergency_phone' => $data['emergency_phone'] ?? null,
                'medical_info' => $data['medical_info'] ?? null,
                'status' => RegistrationStatus::Pending,
                'registered_at' => now(),
            ]);

            // Handle team if specified
            if (!empty($data['team_name'])) {
                $team = $this->teamService->createOrJoinTeam(
                    $registration,
                    $data['team_name']
                );
                $registration->update(['team_id' => $team->id]);
                $registration->load('team');
            }

            // Dispatch event
            event(new RegistrationCreated($registration));

            return $registration->load(['track', 'team']);
        });
    }

    /**
     * Cancel a registration
     */
    public function cancel(int $registrationId, ?string $reason = null): void
    {
        $registration = Registration::findOrFail($registrationId);

        if (!$registration->canBeCancelled()) {
            throw new \InvalidArgumentException(
                "This registration cannot be cancelled"
            );
        }

        DB::transaction(function () use ($registration, $reason) {
            $wasConfirmed = $registration->isConfirmed();

            $registration->update([
                'status' => RegistrationStatus::Cancelled,
            ]);

            event(new RegistrationCancelled($registration, $reason));

            // Waitlist promotion will be handled by event listener
        });
    }

    /**
     * Update registration details
     */
    public function update(int $registrationId, array $data): Registration
    {
        $registration = Registration::findOrFail($registrationId);

        if (!$registration->canBeUpdated()) {
            throw new \InvalidArgumentException(
                "This registration cannot be updated"
            );
        }

        $registration->update([
            'first_name' => $data['first_name'] ?? $registration->first_name,
            'last_name' => $data['last_name'] ?? $registration->last_name,
            'phone' => $data['phone'] ?? $registration->phone,
            'emergency_contact' => $data['emergency_contact'] ?? $registration->emergency_contact,
            'emergency_phone' => $data['emergency_phone'] ?? $registration->emergency_phone,
            'medical_info' => $data['medical_info'] ?? $registration->medical_info,
        ]);

        return $registration->fresh();
    }

    /**
     * Get registrations by track
     */
    public function getByTrack(
        int $trackId, 
        ?RegistrationStatus $status = null
    ): Collection {
        $query = Registration::with(['team'])
            ->forTrack($trackId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('registered_at')->get();
    }

    /**
     * Check for duplicate registration
     */
    protected function hasDuplicateRegistration(string $email, int $trackId): bool
    {
        return Registration::where('email', $email)
            ->where('track_id', $trackId)
            ->whereNot('status', RegistrationStatus::Cancelled)
            ->exists();
    }

    /**
     * Get registration statistics
     */
    public function getStatistics(int $trackId): array
    {
        $registrations = Registration::forTrack($trackId)->get();

        return [
            'total' => $registrations->count(),
            'pending' => $registrations->where('status', RegistrationStatus::Pending)->count(),
            'confirmed' => $registrations->where('status', RegistrationStatus::Confirmed)->count(),
            'not_selected' => $registrations->where('status', RegistrationStatus::NotSelected)->count(),
            'waitlisted' => $registrations->where('status', RegistrationStatus::Waitlisted)->count(),
            'cancelled' => $registrations->where('status', RegistrationStatus::Cancelled)->count(),
            'individual' => $registrations->whereNull('team_id')->count(),
            'team' => $registrations->whereNotNull('team_id')->count(),
        ];
    }
}
```

#### DrawService
```php
<?php

namespace App\Services;

use App\Domain\Registration\Models\Registration;
use App\Domain\Registration\Models\Team;
use App\Domain\Registration\Enums\RegistrationStatus;
use App\Domain\Track\Models\Track;
use App\Domain\Draw\Events\DrawExecuted;
use App\Domain\Draw\Events\ParticipantSelected;
use App\Domain\Draw\Events\ParticipantNotSelected;
use App\Domain\Draw\Exceptions\DrawAlreadyExecutedException;
use App\Domain\Track\Enums\TrackStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DrawService
{
    public function __construct(
        private WaitlistService $waitlistService
    ) {}

    /**
     * Execute draw for a track
     */
    public function executeDraw(int $trackId): array
    {
        $track = Track::findOrFail($trackId);

        if (!$track->canExecuteDraw()) {
            throw new DrawAlreadyExecutedException(
                "Draw cannot be executed for this track"
            );
        }

        return DB::transaction(function () use ($track) {
            // Get all pending registrations
            $registrations = Registration::with('team')
                ->forTrack($track->id)
                ->pending()
                ->get();

            // Calculate available spots
            $availableSpots = $track->capacity;

            // Execute selection
            $selected = $this->selectRegistrations($registrations, $availableSpots);
            $notSelected = $registrations->diff($selected);

            // Update statuses
            $this->updateRegistrationStatuses($selected, $notSelected);

            // Update track status
            $track->update(['status' => TrackStatus::DrawCompleted]);

            // Dispatch events
            event(new DrawExecuted(
                $track,
                $registrations->count(),
                $selected->count(),
                $notSelected->count()
            ));

            foreach ($selected as $registration) {
                event(new ParticipantSelected($registration->fresh()));
            }

            foreach ($notSelected as $registration) {
                event(new ParticipantNotSelected($registration->fresh()));
            }

            // Enroll non-selected to waitlist
            $this->waitlistService->enrollFromDraw($track->id);

            return [
                'total_registrations' => $registrations->count(),
                'selected' => $selected->count(),
                'not_selected' => $notSelected->count(),
                'teams_selected' => $selected->whereNotNull('team_id')->pluck('team_id')->unique()->count(),
                'individuals_selected' => $selected->whereNull('team_id')->count(),
            ];
        });
    }

    /**
     * Select registrations randomly
     */
    protected function selectRegistrations(
        Collection $registrations,
        int $availableSpots
    ): Collection {
        // Separate individual and team registrations
        $individuals = $registrations->whereNull('team_id');
        $teamRegistrations = $registrations->whereNotNull('team_id');

        // Group team registrations by team
        $teams = $teamRegistrations->groupBy('team_id');

        // Create selection pool (teams count as one unit)
        $pool = collect();

        // Add individuals
        foreach ($individuals as $individual) {
            $pool->push([
                'type' => 'individual',
                'registrations' => collect([$individual]),
                'size' => 1,
            ]);
        }

        // Add teams
        foreach ($teams as $teamId => $members) {
            $pool->push([
                'type' => 'team',
                'team_id' => $teamId,
                'registrations' => $members,
                'size' => $members->count(),
            ]);
        }

        // Shuffle pool for random selection
        $pool = $pool->shuffle();

        // Select until spots filled
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

    /**
     * Update registration statuses
     */
    protected function updateRegistrationStatuses(
        Collection $selected,
        Collection $notSelected
    ): void {
        // Update selected to confirmed
        Registration::whereIn('id', $selected->pluck('id'))
            ->update(['status' => RegistrationStatus::Confirmed]);

        // Update not selected
        Registration::whereIn('id', $notSelected->pluck('id'))
            ->update(['status' => RegistrationStatus::NotSelected]);
    }
}
```

### 4. Controllers

#### RegistrationController
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Http\Requests\UpdateRegistrationRequest;
use App\Http\Resources\RegistrationResource;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    public function __construct(
        private RegistrationService $registrationService
    ) {}

    /**
     * Store a new registration
     */
    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        try {
            $registration = $this->registrationService->register(
                $request->validated()
            );

            return response()->json([
                'message' => 'Registration created successfully',
                'data' => new RegistrationResource($registration),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified registration
     */
    public function show(int $id): JsonResponse
    {
        $registration = \App\Domain\Registration\Models\Registration::with(['track', 'team'])
            ->findOrFail($id);

        return response()->json([
            'data' => new RegistrationResource($registration),
        ]);
    }

    /**
     * Update the specified registration
     */
    public function update(
        UpdateRegistrationRequest $request,
        int $id
    ): JsonResponse {
        try {
            $registration = $this->registrationService->update(
                $id,
                $request->validated()
            );

            return response()->json([
                'message' => 'Registration updated successfully',
                'data' => new RegistrationResource($registration),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Update failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel the specified registration
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->registrationService->cancel($id);

            return response()->json([
                'message' => 'Registration cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cancellation failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
```

### 5. Event Service Provider

```php
<?php

namespace App\Providers;

use App\Domain\Registration\Events\RegistrationCreated;
use App\Domain\Registration\Events\RegistrationCancelled;
use App\Domain\Draw\Events\ParticipantSelected;
use App\Domain\Draw\Events\ParticipantNotSelected;
use App\Domain\Waitlist\Events\PromotedFromWaitlist;
use App\Listeners\SendRegistrationConfirmation;
use App\Listeners\HandleRegistrationCancellation;
use App\Listeners\SendDrawResultNotification;
use App\Listeners\SendWaitlistPromotionNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        RegistrationCreated::class => [
            SendRegistrationConfirmation::class,
        ],
        RegistrationCancelled::class => [
            HandleRegistrationCancellation::class,
        ],
        ParticipantSelected::class => [
            SendDrawResultNotification::class,
        ],
        ParticipantNotSelected::class => [
            SendDrawResultNotification::class,
        ],
        PromotedFromWaitlist::class => [
            SendWaitlistPromotionNotification::class,
        ],
    ];
}
```

---

## Database Migrations

### Tracks Migration
```php
Schema::create('tracks', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('distance')->nullable();
    $table->integer('capacity');
    $table->timestamp('registration_opens_at');
    $table->timestamp('registration_closes_at');
    $table->timestamp('draw_date');
    $table->string('status')->default('draft');
    $table->timestamps();
});
```

### Registrations Migration
```php
Schema::create('registrations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('track_id')->constrained()->onDelete('cascade');
    $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
    $table->string('first_name');
    $table->string('last_name');
    $table->string('email');
    $table->string('phone');
    $table->string('emergency_contact');
    $table->string('emergency_phone')->nullable();
    $table->text('medical_info')->nullable();
    $table->string('status')->default('pending');
    $table->timestamp('registered_at');
    $table->timestamps();
    
    $table->index(['track_id', 'status']);
    $table->index(['email', 'track_id']);
});
```

### Teams Migration
```php
Schema::create('teams', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->foreignId('track_id')->constrained()->onDelete('cascade');
    $table->foreignId('captain_registration_id')->nullable()
        ->constrained('registrations')->onDelete('set null');
    $table->integer('min_size')->default(2);
    $table->integer('max_size')->default(6);
    $table->timestamps();
    
    $table->unique(['name', 'track_id']);
});
```

### Waitlist Entries Migration
```php
Schema::create('waitlist_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('registration_id')->constrained()->onDelete('cascade');
    $table->foreignId('track_id')->constrained()->onDelete('cascade');
    $table->integer('position');
    $table->timestamp('enrolled_at');
    $table->timestamps();
    
    $table->unique(['registration_id', 'track_id']);
    $table->index(['track_id', 'position']);
});
```

---

## Configuration

### config/event-registration.php
```php
<?php

return [
    'team' => [
        'min_size' => env('TEAM_MIN_SIZE', 2),
        'max_size' => env('TEAM_MAX_SIZE', 6),
    ],
    
    'waitlist' => [
        'promotion_deadline_hours' => env('WAITLIST_PROMOTION_DEADLINE', 48),
    ],
    
    'notifications' => [
        'from_email' => env('MAIL_FROM_ADDRESS'),
        'from_name' => env('MAIL_FROM_NAME'),
    ],
];
```

---

## Best Practices Summary

1. **Service Layer Pattern**: Business logic in services, not controllers
2. **Repository Pattern** (optional): Can add repositories for complex queries
3. **Event-Driven**: Use Laravel events for loose coupling
4. **Queue Jobs**: Long-running tasks (draw execution, email sending) should be queued
5. **Form Requests**: Validation in dedicated request classes
6. **API Resources**: Consistent API responses with resource classes
7. **Database Transactions**: Use DB::transaction() for atomic operations
8. **Enums**: Type-safe status values with PHP 8.1+ enums
9. **Model Scopes**: Reusable query logic in model scopes
10. **Testing**: Feature tests for workflows, unit tests for services
