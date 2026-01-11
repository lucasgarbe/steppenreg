# Implementation Specifications

## Table of Contents
1. [Domain Models & Entities](#domain-models--entities)
2. [Service Layer Specifications](#service-layer-specifications)
3. [Event System](#event-system)
4. [API Endpoints](#api-endpoints)
5. [Business Rules](#business-rules)
6. [Data Validation](#data-validation)

---

## Domain Models & Entities

### Registration Entity
```php
class Registration extends Model
{
    protected $fillable = [
        'user_id',
        'track_id',
        'team_id',
        'status',
        'first_name',
        'last_name',
        'email',
        'phone',
        'emergency_contact',
        'medical_info',
        'registered_at',
    ];

    // Status enum: pending, confirmed, not_selected, cancelled, waitlisted
    protected $casts = [
        'registered_at' => 'datetime',
        'status' => RegistrationStatus::class,
    ];

    // Relationships
    public function track(): BelongsTo;
    public function team(): BelongsTo;
    public function waitlistEntry(): HasOne;
}
```

### Team Entity
```php
class Team extends Model
{
    protected $fillable = [
        'name',
        'track_id',
        'captain_registration_id',
        'min_size',
        'max_size',
        'created_at',
    ];

    // Relationships
    public function registrations(): HasMany;
    public function track(): BelongsTo;
    public function captain(): BelongsTo; // to Registration

    // Domain Methods
    public function isFull(): bool;
    public function isValid(): bool;
    public function currentSize(): int;
}
```

### Track Entity
```php
class Track extends Model
{
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
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
        'draw_date' => 'datetime',
        'status' => TrackStatus::class,
    ];

    // Relationships
    public function registrations(): HasMany;
    public function teams(): HasMany;

    // Domain Methods
    public function isRegistrationOpen(): bool;
    public function availableSpots(): int;
    public function confirmedCount(): int;
}
```

### WaitlistEntry Entity
```php
class WaitlistEntry extends Model
{
    protected $fillable = [
        'registration_id',
        'track_id',
        'position',
        'enrolled_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    // Relationships
    public function registration(): BelongsTo;
    public function track(): BelongsTo;
}
```

---

## Service Layer Specifications

### RegistrationService

**Purpose**: Manages individual participant registrations lifecycle

**Methods**:

```php
class RegistrationService
{
    /**
     * Create a new registration
     * 
     * @throws RegistrationClosedException
     * @throws TrackFullException
     * @throws DuplicateRegistrationException
     */
    public function register(array $data): Registration
    {
        // 1. Validate registration period
        // 2. Check track capacity
        // 3. Check for duplicate registration (same user, same track)
        // 4. Create registration with status 'pending'
        // 5. If team_name provided, call TeamService
        // 6. Dispatch RegistrationCreated event
        // 7. Return registration
    }

    /**
     * Cancel a registration
     * 
     * @throws RegistrationNotFoundException
     * @throws InvalidStatusException
     */
    public function cancel(int $registrationId, string $reason = null): void
    {
        // 1. Find registration
        // 2. Validate can be cancelled (not already cancelled)
        // 3. Update status to 'cancelled'
        // 4. If registration was confirmed, free up spot
        // 5. Dispatch RegistrationCancelled event
        // 6. Trigger waitlist promotion if applicable
    }

    /**
     * Update registration details
     */
    public function update(int $registrationId, array $data): Registration
    {
        // 1. Find registration
        // 2. Validate can be updated (before draw or specific fields only)
        // 3. Update allowed fields
        // 4. Return updated registration
    }

    /**
     * Get registrations by track
     */
    public function getByTrack(int $trackId, ?string $status = null): Collection
    {
        // Return filtered registrations
    }

    /**
     * Get registration statistics
     */
    public function getStatistics(int $trackId): array
    {
        // Return counts by status, team vs individual, etc.
    }
}
```

### TeamService

**Purpose**: Manages team formation and validation

**Methods**:

```php
class TeamService
{
    /**
     * Create or join a team
     * 
     * @throws InvalidTeamException
     * @throws TeamFullException
     * @throws TrackMismatchException
     */
    public function createOrJoinTeam(Registration $registration, string $teamName): Team
    {
        // 1. Check if team exists for this track
        // 2. If exists:
        //    - Validate team not full
        //    - Validate track matches
        //    - Add registration to team
        //    - Dispatch TeamMemberAdded event
        // 3. If not exists:
        //    - Create new team
        //    - Set registration as captain
        //    - Dispatch TeamFormed event
        // 4. Update registration with team_id
        // 5. Return team
    }

    /**
     * Validate team meets requirements
     */
    public function validateTeam(Team $team): bool
    {
        // 1. Check minimum size met
        // 2. Check all members same track
        // 3. Check all members valid status
        // 4. Return validation result
    }

    /**
     * Get team members
     */
    public function getTeamMembers(int $teamId): Collection
    {
        // Return team registrations
    }

    /**
     * Check if team is complete
     */
    public function isTeamComplete(Team $team): bool
    {
        // Check if team meets minimum requirements
    }

    /**
     * Remove member from team
     */
    public function removeMember(int $registrationId): void
    {
        // 1. Find registration
        // 2. Remove from team
        // 3. If team empty, delete team
        // 4. Dispatch event if needed
    }
}
```

### DrawService

**Purpose**: Executes lottery selection process

**Methods**:

```php
class DrawService
{
    /**
     * Execute draw for a track
     * 
     * @throws DrawAlreadyExecutedException
     * @throws InsufficientRegistrationsException
     */
    public function executeDraw(int $trackId): DrawResult
    {
        // 1. Validate draw not already executed
        // 2. Get all pending registrations for track
        // 3. Separate individual and team registrations
        // 4. Calculate available spots
        // 5. Execute random selection (teams as units)
        // 6. Update selected registrations to 'confirmed'
        // 7. Update non-selected to 'not_selected'
        // 8. Update track status to 'draw_completed'
        // 9. Dispatch DrawExecuted event
        // 10. Dispatch ParticipantSelected/NotSelected events
        // 11. Trigger WaitlistService.enrollFromDraw()
        // 12. Return results summary
    }

    /**
     * Select registrations randomly
     */
    protected function selectRegistrations(
        Collection $registrations, 
        int $spots
    ): Collection
    {
        // 1. Group registrations by team (null for individuals)
        // 2. Create selection pool (teams count as 1 unit)
        // 3. Randomly select until spots filled
        // 4. Expand teams to individual registrations
        // 5. Return selected registrations
    }

    /**
     * Handle team selection atomically
     */
    protected function handleTeamSelection(Team $team): Collection
    {
        // Return all team member registrations
    }

    /**
     * Get draw results
     */
    public function getDrawResults(int $trackId): array
    {
        // Return statistics about draw outcome
    }

    /**
     * Validate can execute draw
     */
    protected function canExecuteDraw(Track $track): bool
    {
        // Check prerequisites for draw execution
    }
}
```

### WaitlistService

**Purpose**: Manages waitlist queue and promotions

**Methods**:

```php
class WaitlistService
{
    /**
     * Enroll non-selected registrations to waitlist
     */
    public function enrollFromDraw(int $trackId): void
    {
        // 1. Get all 'not_selected' registrations for track
        // 2. Create waitlist entries with position
        // 3. Update registration status to 'waitlisted'
        // 4. Dispatch EnrolledToWaitlist events
    }

    /**
     * Promote next from waitlist
     * 
     * @throws NoWaitlistEntriesException
     */
    public function promoteNext(int $trackId, int $spots = 1): Collection
    {
        // 1. Get next N from waitlist by position
        // 2. Handle teams atomically (all or none)
        // 3. Update registrations to 'confirmed'
        // 4. Remove from waitlist
        // 5. Update positions for remaining
        // 6. Dispatch PromotedFromWaitlist events
        // 7. Return promoted registrations
    }

    /**
     * Handle cancellation and promote waitlist
     */
    public function handleCancellation(Registration $registration): void
    {
        // 1. Calculate freed spots (1 for individual, team size for team)
        // 2. Call promoteNext() with freed spots
    }

    /**
     * Get waitlist position
     */
    public function getWaitlistPosition(int $registrationId): ?int
    {
        // Return position or null if not on waitlist
    }

    /**
     * Get waitlist by track
     */
    public function getWaitlist(int $trackId): Collection
    {
        // Return ordered waitlist entries
    }

    /**
     * Get estimated promotion chance
     */
    public function getPromotionEstimate(int $registrationId): array
    {
        // Return position, total ahead, historical promotion rate
    }
}
```

### NotificationService

**Purpose**: Sends email notifications for system events

**Methods**:

```php
class NotificationService
{
    /**
     * Send registration confirmation
     */
    public function sendRegistrationConfirmation(Registration $registration): void
    {
        // Send email with registration details, team info, next steps
    }

    /**
     * Send draw result notification
     */
    public function sendDrawResult(Registration $registration, bool $selected): void
    {
        // Send email with selection result
        // If selected: payment info, event details
        // If not selected: waitlist info, cancellation policy
    }

    /**
     * Send waitlist update
     */
    public function sendWaitlistUpdate(Registration $registration, int $position): void
    {
        // Send email with current waitlist position
    }

    /**
     * Send promotion notification
     */
    public function sendPromotionNotification(Registration $registration): void
    {
        // Send email about promotion from waitlist
        // Include payment deadline, confirmation instructions
    }

    /**
     * Send cancellation confirmation
     */
    public function sendCancellationConfirmation(Registration $registration): void
    {
        // Send email confirming cancellation
    }

    /**
     * Send team update notifications
     */
    public function sendTeamUpdate(Team $team, string $updateType): void
    {
        // Notify all team members about team changes
    }
}
```

---

## Event System

### Domain Events

```php
// Registration Events
class RegistrationCreated
{
    public Registration $registration;
}

class RegistrationCancelled
{
    public Registration $registration;
    public ?string $reason;
}

// Team Events
class TeamFormed
{
    public Team $team;
    public Registration $captain;
}

class TeamMemberAdded
{
    public Team $team;
    public Registration $member;
}

// Draw Events
class DrawExecuted
{
    public Track $track;
    public int $totalRegistrations;
    public int $selectedCount;
    public int $notSelectedCount;
}

class ParticipantSelected
{
    public Registration $registration;
}

class ParticipantNotSelected
{
    public Registration $registration;
}

// Waitlist Events
class EnrolledToWaitlist
{
    public Registration $registration;
    public int $position;
}

class PromotedFromWaitlist
{
    public Registration $registration;
    public int $previousPosition;
}
```

### Event Listeners

```php
// Listen to RegistrationCreated → Send confirmation email
class SendRegistrationConfirmation implements ShouldQueue
{
    public function handle(RegistrationCreated $event): void
    {
        $this->notificationService->sendRegistrationConfirmation(
            $event->registration
        );
    }
}

// Listen to RegistrationCancelled → Promote waitlist
class HandleRegistrationCancellation implements ShouldQueue
{
    public function handle(RegistrationCancelled $event): void
    {
        if ($event->registration->status === 'confirmed') {
            $this->waitlistService->handleCancellation(
                $event->registration
            );
        }
    }
}

// Listen to ParticipantSelected/NotSelected → Send result emails
class SendDrawResultNotification implements ShouldQueue
{
    public function handle(ParticipantSelected|ParticipantNotSelected $event): void
    {
        $selected = $event instanceof ParticipantSelected;
        $this->notificationService->sendDrawResult(
            $event->registration,
            $selected
        );
    }
}

// Listen to PromotedFromWaitlist → Send promotion email
class SendWaitlistPromotionNotification implements ShouldQueue
{
    public function handle(PromotedFromWaitlist $event): void
    {
        $this->notificationService->sendPromotionNotification(
            $event->registration
        );
    }
}
```

---

## API Endpoints

### Public Endpoints

```
POST   /api/registrations           - Create registration
GET    /api/registrations/{id}      - Get registration details
PUT    /api/registrations/{id}      - Update registration
DELETE /api/registrations/{id}      - Cancel registration

GET    /api/tracks                  - List all tracks
GET    /api/tracks/{id}             - Get track details
GET    /api/tracks/{id}/availability - Get available spots

GET    /api/teams/{id}              - Get team details
GET    /api/teams/{id}/members      - Get team members

GET    /api/waitlist/{registrationId}/position - Get waitlist position
```

### Admin Endpoints

```
POST   /api/admin/tracks/{id}/draw          - Execute draw
GET    /api/admin/tracks/{id}/registrations - List registrations
GET    /api/admin/tracks/{id}/statistics    - Get statistics
POST   /api/admin/tracks/{id}/draw/preview  - Preview draw outcome

GET    /api/admin/waitlist/{trackId}        - View waitlist
POST   /api/admin/waitlist/promote          - Manual promotion

GET    /api/admin/teams                     - List all teams
GET    /api/admin/teams/{id}                - Get team details
```

---

## Business Rules

### Registration Rules
1. Participant can only register once per track
2. Registration only allowed during open period
3. Cannot register for full track (unless waitlist available)
4. All required fields must be provided
5. Valid email and phone number required
6. Emergency contact required for participants under 18

### Team Rules
1. Team name must be unique per track
2. Minimum team size: 2 members
3. Maximum team size: 6 members (configurable per track)
4. All team members must register for same track
5. First registrant becomes team captain
6. Team members can join until registration closes
7. Team treated as single unit in draw

### Draw Rules
1. Can only execute once per track
2. Must be after registration closes
3. All pending registrations eligible
4. Teams selected as atomic units
5. Selection is purely random
6. Results are final and cannot be modified
7. Non-selected automatically enrolled to waitlist

### Waitlist Rules
1. Position determined by draw order (random)
2. FIFO promotion when spots available
3. Teams promoted as atomic units
4. Promoted registrations have 48 hours to confirm
5. If not confirmed, next in line promoted
6. Waitlist active until event date

### Cancellation Rules
1. Can cancel anytime before event
2. Confirmed registrations trigger waitlist promotion
3. Team cancellations:
   - Individual can leave team before draw
   - After draw, entire team must cancel or stay
4. Refund policy based on cancellation date

---

## Data Validation

### Registration Request Validation
```php
[
    'track_id' => 'required|exists:tracks,id',
    'first_name' => 'required|string|max:255',
    'last_name' => 'required|string|max:255',
    'email' => 'required|email|max:255',
    'phone' => 'required|string|max:20',
    'emergency_contact' => 'required|string|max:255',
    'team_name' => 'nullable|string|max:100',
    'medical_info' => 'nullable|string|max:1000',
]
```

### Team Validation
```php
[
    'name' => 'required|string|max:100|unique:teams,name,NULL,id,track_id,' . $trackId,
    'track_id' => 'required|exists:tracks,id',
]
```

### Draw Execution Validation
```php
[
    'track_id' => 'required|exists:tracks,id',
    'confirm' => 'required|boolean|accepted',
]
```

---

## Status Enums

### RegistrationStatus
- `pending` - Awaiting draw
- `confirmed` - Selected in draw or promoted from waitlist
- `not_selected` - Not selected in draw
- `waitlisted` - On waitlist
- `cancelled` - Cancelled by participant or admin

### TrackStatus
- `draft` - Not yet published
- `open` - Registration open
- `closed` - Registration closed, awaiting draw
- `draw_completed` - Draw executed
- `event_completed` - Event finished

### TeamStatus (if needed)
- `forming` - Still accepting members
- `complete` - Ready for draw
- `confirmed` - Selected in draw
- `not_selected` - Not selected in draw
