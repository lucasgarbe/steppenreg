# Cycling Event Registration System - Architecture

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           PRESENTATION LAYER                             │
│                                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐│
│  │ Registration │  │    Team      │  │    Draw      │  │  Waitlist   ││
│  │  Controller  │  │  Controller  │  │  Controller  │  │ Controller  ││
│  └──────────────┘  └──────────────┘  └──────────────┘  └─────────────┘│
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          APPLICATION LAYER                               │
│                           (Service Layer)                                │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                   REGISTRATION DOMAIN                            │  │
│  │  ┌────────────────────────┐    ┌──────────────────────────┐    │  │
│  │  │  RegistrationService   │    │    TeamService           │    │  │
│  │  │                        │    │                          │    │  │
│  │  │ - register()           │    │ - createOrJoinTeam()     │    │  │
│  │  │ - cancel()             │    │ - validateTeam()         │    │  │
│  │  │ - update()             │    │ - getTeamMembers()       │    │  │
│  │  │ - getByTrack()         │    │ - isTeamComplete()       │    │  │
│  │  └────────────────────────┘    └──────────────────────────┘    │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                      DRAW DOMAIN                                 │  │
│  │  ┌────────────────────────────────────────────────────────────┐ │  │
│  │  │              DrawService                                    │ │  │
│  │  │                                                             │ │  │
│  │  │ - executeDraw(trackId, spots)                              │ │  │
│  │  │ - selectRegistrations()                                    │ │  │
│  │  │ - handleTeamSelection()                                    │ │  │
│  │  │ - getDrawResults()                                         │ │  │
│  │  └────────────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                    WAITLIST DOMAIN                               │  │
│  │  ┌────────────────────────────────────────────────────────────┐ │  │
│  │  │           WaitlistService                                   │ │  │
│  │  │                                                             │ │  │
│  │  │ - enrollFromDraw()                                         │ │  │
│  │  │ - promoteNext(trackId)                                     │ │  │
│  │  │ - handleCancellation()                                     │ │  │
│  │  │ - getWaitlistPosition()                                    │ │  │
│  │  └────────────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                  NOTIFICATION DOMAIN                             │  │
│  │  ┌────────────────────────────────────────────────────────────┐ │  │
│  │  │         NotificationService                                 │ │  │
│  │  │                                                             │ │  │
│  │  │ - sendRegistrationConfirmation()                           │ │  │
│  │  │ - sendDrawResults()                                        │ │  │
│  │  │ - sendWaitlistUpdate()                                     │ │  │
│  │  │ - sendPromotionNotification()                              │ │  │
│  │  └────────────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                            DOMAIN LAYER                                  │
│                                                                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌──────────────┐  │
│  │Registration │  │    Team     │  │   Track     │  │   Waitlist   │  │
│  │   Entity    │  │   Entity    │  │   Entity    │  │    Entry     │  │
│  │             │  │             │  │             │  │    Entity    │  │
│  │ - id        │  │ - id        │  │ - id        │  │ - id         │  │
│  │ - user      │  │ - name      │  │ - name      │  │ - reg_id     │  │
│  │ - track_id  │  │ - track_id  │  │ - capacity  │  │ - track_id   │  │
│  │ - team_id   │  │ - size      │  │ - status    │  │ - position   │  │
│  │ - status    │  │ - captain   │  │             │  │ - enrolled_at│  │
│  │ - details   │  │             │  │             │  │              │  │
│  └─────────────┘  └─────────────┘  └─────────────┘  └──────────────┘  │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                      Domain Events                               │  │
│  │                                                                  │  │
│  │  - RegistrationCreated      - DrawExecuted                      │  │
│  │  - RegistrationCancelled    - ParticipantSelected               │  │
│  │  - TeamFormed               - ParticipantNotSelected            │  │
│  │  - TeamMemberAdded          - EnrolledToWaitlist                │  │
│  │                             - PromotedFromWaitlist              │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                       INFRASTRUCTURE LAYER                               │
│                                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────┐ │
│  │  Database    │  │  Event Bus   │  │ Mail Service │  │   Queue    │ │
│  │  (MySQL)     │  │              │  │              │  │            │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
```

## Domain Interactions Flow

### Registration Phase
```
Participant → RegistrationController → RegistrationService
                                            │
                                            ├─→ Create Registration Entity
                                            ├─→ TeamService.createOrJoinTeam()
                                            ├─→ Publish RegistrationCreated Event
                                            └─→ NotificationService (via Event)
                                                    └─→ Send Confirmation Email
```

### Draw Phase
```
Admin → DrawController → DrawService
                              │
                              ├─→ Get all Pending Registrations
                              ├─→ Group by Teams
                              ├─→ Random Selection (respect team units)
                              ├─→ Update Registration Status
                              ├─→ Publish DrawExecuted, ParticipantSelected/NotSelected
                              └─→ WaitlistService.enrollFromDraw()
                                      │
                                      └─→ NotificationService (via Events)
                                              └─→ Send Result Emails
```

### Waitlist Promotion Flow
```
Cancellation → RegistrationService.cancel()
                    │
                    ├─→ Update Registration Status
                    ├─→ Publish RegistrationCancelled
                    └─→ WaitlistService.promoteNext()
                            │
                            ├─→ Get Next from Waitlist
                            ├─→ Update Registration to Confirmed
                            ├─→ Publish PromotedFromWaitlist
                            └─→ NotificationService
                                    └─→ Send Promotion Email
```

## Key Architectural Principles

1. **Separation of Concerns**: Each domain has clear boundaries and responsibilities
2. **Event-Driven Communication**: Domains communicate through domain events
3. **Single Responsibility**: Each service handles one specific domain concern
4. **Dependency Direction**: Dependencies point inward (Infrastructure → Application → Domain)
5. **Modular Design**: Easy to add new registration types or modify draw logic independently

## Technology Stack Recommendations

- **Framework**: Laravel 11.x
- **Database**: MySQL 8.x
- **Queue**: Redis + Laravel Queue
- **Events**: Laravel Events with Queue listeners
- **Mail**: Laravel Mail with queue
- **Testing**: PHPUnit + Pest
