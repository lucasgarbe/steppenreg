# Steppenreg DDD-Lite Architecture Plan

## Overview

Transform Steppenreg into an open-source event registration platform using a **Domain-Driven Design Lite** approach. This keeps the simplicity of a Laravel monolith while providing clear feature boundaries and extensibility.

## Goals

- Make codebase open-source ready
- Enable feature-based toggling
- Improve code organization and maintainability
- Lower barrier to contribution
- Stay within Laravel conventions

## Current State vs. Target State

### Current Structure
```
app/
├── Http/Controllers/
├── Models/
├── Services/
├── Jobs/
├── Observers/
├── Filament/
└── Providers/
```

### Target Structure
```
app/
├── Core/                          # Shared infrastructure
│   ├── Models/                    # Base models & contracts
│   ├── Contracts/                 # Interfaces
│   ├── Services/                  # Shared services
│   └── Support/                   # Helpers, traits
│
├── Features/                      # Feature modules
│   ├── Registration/              # Core registration
│   ├── Draw/                      # Lottery system
│   ├── Teams/                     # Team management
│   ├── Waitlist/                  # Waitlist + withdrawals
│   ├── StartingNumbers/           # Number assignment
│   └── MailTemplates/             # Email system
│
├── Filament/                      # Admin UI
├── Http/                          # Shared HTTP layer
└── Providers/                     # Service providers
```

## Feature Module Structure

Each feature follows a consistent structure:

```
Features/{FeatureName}/
├── Models/                        # Feature-specific models
├── Services/                      # Business logic services
├── Http/
│   ├── Controllers/               # Feature controllers
│   └── Requests/                  # Form requests
├── Jobs/                          # Queue jobs
├── Events/                        # Laravel events
├── Listeners/                     # Event listeners
├── Filament/                      # Admin resources
│   ├── Resources/
│   └── Widgets/
├── routes.php                     # Feature routes (optional)
└── FeatureServiceProvider.php    # Feature provider
```

## Feature Modules Breakdown

### 1. Core Module
**Location:** `app/Core/`

**Purpose:** Shared infrastructure and base abstractions

**Contents:**
- Base model traits
- Core contracts/interfaces
- Shared services (settings, localization)
- Common helpers and utilities

### 2. Registration Feature
**Location:** `app/Features/Registration/`

**Purpose:** Core participant registration functionality

**Components:**
- `Models/Registration.php` - Main registration model
- `Services/RegistrationService.php` - Registration business logic
- `Http/Controllers/PublicRegistrationController.php`
- `Jobs/SendRegistrationConfirmation.php`
- `Events/RegistrationCreated.php`
- `Filament/Resources/RegistrationResource.php`

**Dependencies:** Core

### 3. Draw Feature
**Location:** `app/Features/Draw/`

**Purpose:** Lottery/draw system for participant selection

**Components:**
- `Services/DrawService.php` - Draw execution logic
- `Jobs/PerformDraw.php`
- `Events/DrawPerformed.php`
- `Filament/Pages/ManageDraw.php`
- `Filament/Widgets/DrawStatsWidget.php`

**Dependencies:** Registration

### 4. Teams Feature
**Location:** `app/Features/Teams/`

**Purpose:** Team-based registration management

**Components:**
- `Models/Team.php`
- `Services/TeamService.php`
- `Rules/TeamValidationRules.php`
- `Filament/Resources/TeamResource.php`

**Dependencies:** Registration

### 5. Waitlist Feature
**Location:** `app/Features/Waitlist/`

**Purpose:** Waitlist management and withdrawal requests

**Components:**
- `Models/WaitlistEntry.php`
- `Models/WithdrawalRequest.php`
- `Services/WaitlistService.php`
- `Http/Controllers/WaitlistController.php`
- `Jobs/ProcessWaitlist.php`
- routes.php (public waitlist routes)

**Dependencies:** Registration, Draw

### 6. StartingNumbers Feature
**Location:** `app/Features/StartingNumbers/`

**Purpose:** Automated starting number assignment

**Components:**
- `Services/StartingNumberService.php`
- `Jobs/AssignStartingNumbers.php`
- Configuration for numbering schemes

**Dependencies:** Registration, Draw

### 7. MailTemplates Feature
**Location:** `app/Features/MailTemplates/`

**Purpose:** Flexible email template system

**Components:**
- `Models/MailTemplate.php`
- `Models/MailLog.php`
- `Services/MailTemplateService.php`
- `Services/MailVariableResolver.php`
- `Mail/TemplateBasedEmail.php`
- `Filament/Resources/MailTemplateResource.php`

**Dependencies:** Core

## Configuration-Based Feature Control

### config/steppenreg.php
```php
return [
    'features' => [
        'teams' => env('STEPPENREG_TEAMS_ENABLED', true),
        'draw' => env('STEPPENREG_DRAW_ENABLED', true),
        'waitlist' => env('STEPPENREG_WAITLIST_ENABLED', true),
        'starting_numbers' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
        'mail_templates' => env('STEPPENREG_MAIL_TEMPLATES_ENABLED', true),
    ],
    
    'models' => [
        'registration' => \App\Features\Registration\Models\Registration::class,
        'team' => \App\Features\Teams\Models\Team::class,
    ],
    
    'providers' => [
        \App\Features\Registration\RegistrationServiceProvider::class,
        \App\Features\Draw\DrawServiceProvider::class,
        \App\Features\Teams\TeamsServiceProvider::class,
        \App\Features\Waitlist\WaitlistServiceProvider::class,
        \App\Features\StartingNumbers\StartingNumbersServiceProvider::class,
        \App\Features\MailTemplates\MailTemplatesServiceProvider::class,
    ],
];
```

### Feature Service Provider Pattern
```php
namespace App\Features\Draw;

use Illuminate\Support\ServiceProvider;

class DrawServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DrawService::class);
    }
    
    public function boot(): void
    {
        if (!config('steppenreg.features.draw')) {
            return;
        }
        
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        
        if (file_exists(__DIR__.'/routes.php')) {
            $this->loadRoutesFrom(__DIR__.'/routes.php');
        }
    }
}
```

## Migration Strategy

### Phase 1: Preparation (Week 1)
- [ ] Create feature module directories
- [ ] Create Core namespace
- [ ] Set up configuration file
- [ ] Document current dependencies

### Phase 2: Core Extraction (Week 2)
- [ ] Move shared models to Core
- [ ] Create contracts/interfaces
- [ ] Extract common services
- [ ] Create base traits

### Phase 3: Feature Extraction (Weeks 3-4)
- [ ] Extract Registration feature
- [ ] Extract Draw feature
- [ ] Extract Teams feature
- [ ] Extract Waitlist feature
- [ ] Extract StartingNumbers feature
- [ ] Extract MailTemplates feature

### Phase 4: Integration (Week 5)
- [ ] Create feature service providers
- [ ] Wire up configuration system
- [ ] Update routes
- [ ] Update Filament resources
- [ ] Fix namespaces and imports

### Phase 5: Testing (Week 6)
- [ ] Write feature tests
- [ ] Write integration tests
- [ ] Test feature toggles
- [ ] Manual testing

### Phase 6: Documentation (Week 7)
- [ ] Architecture documentation
- [ ] Feature documentation
- [ ] Configuration guide
- [ ] Contribution guidelines
- [ ] API documentation

## File Migration Map

### Models
```
app/Models/Registration.php         → app/Features/Registration/Models/Registration.php
app/Models/Team.php                 → app/Features/Teams/Models/Team.php
app/Models/WaitlistEntry.php        → app/Features/Waitlist/Models/WaitlistEntry.php
app/Models/WithdrawalRequest.php    → app/Features/Waitlist/Models/WithdrawalRequest.php
app/Models/MailTemplate.php         → app/Features/MailTemplates/Models/MailTemplate.php
app/Models/MailLog.php              → app/Features/MailTemplates/Models/MailLog.php
```

### Services
```
app/Services/StartingNumberService.php  → app/Features/StartingNumbers/Services/StartingNumberService.php
app/Services/MailTemplateService.php    → app/Features/MailTemplates/Services/MailTemplateService.php
app/Services/MailVariableResolver.php   → app/Features/MailTemplates/Services/MailVariableResolver.php
```

### Controllers
```
app/Http/Controllers/PublicRegistrationController.php → app/Features/Registration/Http/Controllers/PublicRegistrationController.php
app/Http/Controllers/WaitlistController.php           → app/Features/Waitlist/Http/Controllers/WaitlistController.php
```

### Jobs
```
app/Jobs/Mail/SendRegistrationConfirmation.php  → app/Features/Registration/Jobs/SendRegistrationConfirmation.php
app/Jobs/Mail/SendDrawNotification.php          → app/Features/Draw/Jobs/SendDrawNotification.php
app/Jobs/Mail/SendWaitlistConfirmation.php      → app/Features/Waitlist/Jobs/SendWaitlistConfirmation.php
```

### Filament Resources
```
app/Filament/Resources/Registrations/    → app/Features/Registration/Filament/Resources/RegistrationResource/
app/Filament/Resources/Teams/            → app/Features/Teams/Filament/Resources/TeamResource/
app/Filament/Resources/MailTemplates/    → app/Features/MailTemplates/Filament/Resources/MailTemplateResource/
```

## Contracts & Interfaces

### Core Contracts
```php
// app/Core/Contracts/Registerable.php
interface Registerable
{
    public function canRegister(): bool;
    public function register(array $data): Registration;
}

// app/Core/Contracts/Drawable.php
interface Drawable
{
    public function draw(int $count): Collection;
    public function getDrawStats(): array;
}

// app/Core/Contracts/HasCapacity.php
interface HasCapacity
{
    public function hasCapacity(): bool;
    public function getRemainingSpots(): int;
}

// app/Core/Contracts/NotificationChannel.php
interface NotificationChannel
{
    public function send(Notifiable $notifiable, Notification $notification): void;
}
```

## Benefits of This Approach

### For Development
- Clear feature boundaries
- Easier to locate code
- Reduced coupling
- Better testability

### For Open Source
- Easy to understand for contributors
- Follows Laravel conventions
- Simple setup (no package management)
- Feature toggles for flexibility

### For Users
- Can disable unused features
- Clear documentation per feature
- Easier to customize
- Lower complexity

## Future Considerations

### When to Extract to Packages
Only create separate Composer packages if:
1. Another project specifically needs it
2. It's truly generic and reusable
3. Community demand justifies the complexity
4. You want to monetize as add-ons

### Potential Standalone Packages
- MailTemplates system (generic enough)
- Starting number management (niche but reusable)

### Keep as Features
- Draw system (too specific)
- Waitlist (too coupled to registration)
- Teams (too coupled to registration)

## Success Criteria

- [ ] All features have clear boundaries
- [ ] Features can be toggled on/off via config
- [ ] No circular dependencies between features
- [ ] 80%+ test coverage
- [ ] Comprehensive documentation
- [ ] Easy contributor onboarding
- [ ] Demo application showcasing all features
- [ ] 10+ GitHub stars in first month
- [ ] 3+ external contributors in first quarter

## Resources & References

### Laravel Projects Using Similar Architecture
- Monica CRM (domains in /app)
- Cachet (feature-based structure)
- Invoice Ninja (modular monolith)

### Documentation
- Laravel Beyond CRUD by Spatie
- Domain-Driven Design principles
- Laravel package development guide

---

**Last Updated:** December 2024
**Status:** Planning Phase
**Target Launch:** Q1 2025
