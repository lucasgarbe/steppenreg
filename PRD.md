# Product Requirements Document: Domain Modularization
## StartingNumber & Draw Domain Plugin Integration

**Project:** Steppenreg Domain Modularization  
**Version:** 1.0.0  
**Created:** January 21, 2026  
**Status:** 📋 Awaiting Human Review  
**Estimated Effort:** 13-18 hours

---

## Executive Summary

Modularize the StartingNumber and Draw domains into self-contained, toggleable modules with Filament plugin integration. Each domain will have its own configuration, UI components, tests, and migrations that can be enabled/disabled per installation.

**Core Principles:**
- ✅ Registration, Teams, and Mail remain core application features
- ✅ StartingNumber and Draw become optional, self-contained modules
- ✅ Each module can be independently enabled/disabled
- ✅ Clean separation of concerns with clear interfaces
- ✅ Event-driven communication (already implemented correctly)

---

## Architecture Analysis

### Current Coupling: Draw ↔ StartingNumber ✅ ALREADY DECOUPLED

**Analysis Result:** The domains are **already properly decoupled** using event-driven architecture:

```
Draw Domain (Publisher)
  └─ Emits: RegistrationDrawn event
         ↓
     [Event Bus]
         ↓
StartingNumber Domain (Subscriber)
  └─ Listens: RegistrationDrawn event
  └─ Action: Assigns starting number (if enabled)
```

**Key Points:**
- Draw has **ZERO** imports from StartingNumber
- Draw emits events without knowing subscribers
- StartingNumber subscribes via Laravel's event system
- StartingNumber checks feature toggle before acting
- Draw works perfectly without StartingNumber domain

**Conclusion:** No coupling changes needed - architecture is correct!

---

## Project Scope

### In Scope
- ✅ StartingNumber domain enhancement (UI + config)
- ✅ Draw domain modularization (service provider + plugin)
- ✅ Filament plugin integration for both domains
- ✅ Domain-specific configurations
- ✅ UI components for StartingNumber (settings page + widget)
- ✅ Migration management within domains
- ✅ Comprehensive test coverage
- ✅ Documentation for each domain

### Out of Scope
- ❌ Modularization of Registration, Teams, or Mail (core features)
- ❌ Waitlist domain (future phase)
- ❌ Database schema changes to existing tables
- ❌ Refactoring of core application logic
- ❌ Changes to Draw/StartingNumber coupling (already optimal)

---

## Human Review Decisions

| Question | Decision | Implementation |
|----------|----------|----------------|
| Q1: Range Config | C - Both UI and file | Settings page updates config file |
| Q2: Overflow Bucket | A - Global bucket | 9001-9999 range for all overflows |
| Q3: Draw Dependencies | Already decoupled | No changes needed - event-driven |
| Q4: Documentation | A - In domain dir | `app/Domain/{Name}/README.md` |
| Q5: Timeline | Approved | 13-18 hours total effort |

---

## Architecture Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| **StartingNumber UI** | Settings page + Statistics widget | Need configuration interface + visibility |
| **Draw Widget Registration** | Explicit registration in plugin | Clear component registration |
| **Configuration Location** | Domain-specific config files | All settings in domain directory |
| **Migration Files** | Domain-specific with publishing | Disabled domains don't create tables |
| **Testing Strategy** | Integrated with main test suite | phpunit.xml includes domain tests |
| **Coupling Strategy** | Event-driven (no changes) | Already properly implemented |

---

## Domain Specifications

### StartingNumber Domain

**Purpose:** Assign and manage starting numbers for drawn registrations

**Current State:**
```
app/Domain/StartingNumber/
├── StartingNumberServiceProvider.php  ✅ EXISTS
├── Services/StartingNumberService.php  ✅ EXISTS
├── Events/                             ✅ EXISTS (2 events)
├── Listeners/                          ✅ EXISTS (1 listener)
└── Exceptions/                         ✅ EXISTS (1 exception)
```

**Enhancements Needed:**
- 🆕 StartingNumberPlugin.php (Filament integration)
- 🆕 config/starting-numbers.php (domain config)
- 🆕 Filament/Pages/ManageStartingNumbers.php (settings UI)
- 🆕 Filament/Widgets/StartingNumberStatsWidget.php (statistics)
- 🆕 Tests/ directory (unit + feature tests)
- 🆕 README.md (domain documentation)
- 🔄 Update StartingNumberServiceProvider (register plugin)

**Features:**
- Automatic assignment on draw (existing)
- Configurable number ranges per track (NEW)
- Overflow bucket for exceeding ranges (NEW)
- Settings page for configuration (NEW)
- Statistics widget showing assignment status (NEW)

**Dependencies:**
- **Listens to:** `App\Domain\Draw\Events\RegistrationDrawn`
- **Uses:** `App\Models\Registration` (core model)
- **Event-driven:** No direct coupling to Draw domain

**Configuration Schema:**
```php
// app/Domain/StartingNumber/config/starting-numbers.php
return [
    'enabled' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
    
    // Per-track number ranges
    'tracks' => [
        // Configured via UI or manually
        // Example: 'track-1' => ['start' => 1, 'end' => 500]
    ],
    
    // Global overflow bucket
    'overflow' => [
        'enabled' => true,
        'start' => 9001,
        'end' => 9999,
    ],
    
    // Number format
    'format' => [
        'padding' => 4,        // 0001, 0002, etc.
        'prefix' => '',        // Optional prefix (e.g., 'BIB-')
    ],
    
    // Assignment strategy
    'strategy' => 'sequential',  // 'sequential' | 'random'
];
```

---

### Draw Domain

**Purpose:** Execute lottery-based selection for oversubscribed tracks

**Current State:**
```
app/Domain/Draw/
├── Services/DrawService.php            ✅ EXISTS
├── Models/Draw.php                     ✅ EXISTS
├── Events/                             ✅ EXISTS (3 events)
├── Exceptions/                         ✅ EXISTS (2 exceptions)
└── Filament/
    ├── Pages/ManageDraw.php            ✅ EXISTS
    └── Widgets/                        ✅ EXISTS (2 widgets)
```

**Enhancements Needed:**
- 🆕 DrawServiceProvider.php (domain service provider)
- 🆕 DrawPlugin.php (Filament integration)
- 🆕 config/draw.php (domain config)
- 🆕 database/migrations/ (move from main migrations)
- 🆕 Tests/ directory (unit + feature tests)
- 🆕 README.md (domain documentation)

**Features:**
- Random selection algorithm with team atomicity (existing)
- Execution interface (ManageDraw page) (existing)
- Statistics widgets (existing)
- Event-driven notification system (existing)

**Dependencies:**
- **Uses:** `App\Models\Registration`, `App\Models\Team` (core models)
- **Emits:** `RegistrationDrawn`, `RegistrationNotDrawn`, `DrawExecuted`
- **Event-driven:** No dependencies on other domains

**Configuration Schema:**
```php
// app/Domain/Draw/config/draw.php
return [
    'enabled' => env('STEPPENREG_DRAW_ENABLED', true),
    
    // Default spots calculation
    'default_spots' => null,  // null = use track capacity
    
    // Team handling
    'team_atomicity' => true,  // Teams selected as units
    
    // Permissions
    'permissions' => [
        'execute_draw' => ['admin'],
        'preview_draw' => ['admin', 'manager'],
    ],
    
    // Notifications
    'notifications' => [
        'send_on_drawn' => true,
        'send_on_not_drawn' => true,
    ],
    
    // Algorithm parameters
    'algorithm' => [
        'shuffle_seed' => null,  // For reproducible testing
    ],
];
```

---

## Implementation Phases

### Phase 1: Foundation & Planning ⏳ IN PROGRESS
**Goal:** Establish project structure and validate approach  
**Estimated Time:** 1 hour

### Phase 2: StartingNumber Domain Enhancement 📋 PLANNED
**Goal:** Add UI components, config, and complete modularization  
**Estimated Time:** 4-6 hours

### Phase 3: Draw Domain Modularization 📋 PLANNED
**Goal:** Create service provider, plugin, and full modularization  
**Estimated Time:** 4-6 hours

### Phase 4: Integration & Testing 📋 PLANNED
**Goal:** Cross-domain integration and comprehensive testing  
**Estimated Time:** 2-3 hours

### Phase 5: Documentation & Cleanup 📋 PLANNED
**Goal:** Final documentation and code cleanup  
**Estimated Time:** 2 hours

---

## Phase 1: Foundation & Planning ⏳ IN PROGRESS

**Goal:** Establish project structure, planning documents, and validate approach

---

### Task 1.1: Create PRD Document ✅ COMPLETED
**Status:** ✅ Complete  
**Assignee:** AI Agent  
**Priority:** P0 (Blocker)  
**Estimated Time:** 30 minutes  
**Actual Time:** 45 minutes

**Description:**
Create comprehensive Product Requirements Document (PRD.md) that tracks all phases, tasks, acceptance criteria, and progress.

**Acceptance Criteria:**
- [x] PRD.md file created in project root
- [x] All phases defined with clear goals
- [x] Tasks include status, priority, acceptance criteria
- [x] Architecture decisions documented
- [x] Domain specifications clearly outlined
- [x] Progress tracking system in place
- [x] Coupling analysis completed
- [x] Human review questions answered

**Implementation Notes:**
- File location: `/Users/lucasgarbe/Code/steppenreg/PRD.md`
- Format: Markdown with emoji status indicators
- Coupling analysis: Domains already properly decoupled via events
- Review required before Phase 2

**Deliverables:**
- ✅ PRD.md created
- ✅ Coupling analysis documented
- ✅ Architecture validated

---

### Task 1.2: Human Review & Approval 🚧 CURRENT TASK
**Status:** 🚧 Blocked - Awaiting Human Review  
**Assignee:** Human Reviewer  
**Priority:** P0 (Blocker)  
**Estimated Time:** 30 minutes

**Description:**
Human review of PRD.md to validate approach, modify tasks if needed, and approve progression to Phase 2.

**Acceptance Criteria:**
- [x] PRD.md reviewed by human
- [x] Architecture decisions approved or modified
- [x] Task breakdown validated
- [x] Acceptance criteria reviewed
- [x] Timeline confirmed reasonable
- [x] Any changes incorporated
- [x] Explicit approval to proceed to Phase 2

**Action Required:**
👤 **HUMAN REVIEW NEEDED** - Please review this PRD.md file and:
1. Read through all phases and tasks
2. Verify acceptance criteria are clear
3. Validate timeline (13-18 hours)
4. Check domain specifications
5. Edit this file directly if changes needed
6. Mark criteria above as complete when approved
7. Notify AI to proceed to Task 1.3

**Review Checklist:**
- [x] Phase breakdown makes sense
- [x] StartingNumber enhancements appropriate
- [x] Draw modularization approach correct
- [x] Configuration schemas acceptable
- [x] UI components meet requirements
- [x] Testing strategy sufficient
- [x] Documentation plan adequate
- [x] No critical items missing

**Questions to Consider:**
- Does the Settings Page design meet your needs?
- Is the Statistics Widget sufficient?
- Are the configuration options comprehensive?
- Do the migrations strategy work for your deployment model?
- Any additional features needed?

---

### Task 1.3: Analyze Current Codebase 📋 NOT STARTED
**Status:** 📋 Not Started  
**Assignee:** AI Agent  
**Priority:** P0 (Blocker)  
**Estimated Time:** 30 minutes

**Description:**
Deep analysis of existing domain code to understand current structure, dependencies, and potential issues before modularization.

**Acceptance Criteria:**
- [ ] All StartingNumber files reviewed
- [ ] All Draw domain files reviewed
- [ ] Dependencies mapped (core model usage)
- [ ] Current Filament registration patterns documented
- [ ] Potential breaking changes identified
- [ ] Migration complexity assessed
- [ ] Test coverage gaps identified
- [ ] Analysis document created

**Analysis Areas:**
1. **Current Filament Registration:**
   - How ManageDraw page currently registered
   - How widgets currently discovered
   - Potential double-registration issues

2. **Event Listener Setup:**
   - Verify event registration mechanism
   - Check listener dependencies
   - Validate feature toggle behavior

3. **Service Dependencies:**
   - DrawService dependencies on core models
   - StartingNumberService dependencies
   - Singleton registration verification

4. **Model Relationships:**
   - Registration → Draw relationship
   - Registration.starting_number field
   - Draw model relationships

5. **Configuration Usage:**
   - Current config file usage
   - Feature toggle implementation
   - Environment variable support

6. **Test Coverage:**
   - Existing tests for Draw
   - Existing tests for StartingNumber
   - Integration test gaps

**Deliverables:**
- Analysis document (add to PRD or separate file)
- Dependency map
- Risk assessment
- Migration complexity notes

---

## Phase 2: StartingNumber Domain Enhancement 📋 PLANNED

**Goal:** Add UI components, configuration, and complete modularization of StartingNumber domain  
**Estimated Time:** 4-6 hours  
**Dependencies:** Phase 1 complete

---

### Task 2.1: Create StartingNumber Configuration 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 30 minutes

**Description:**
Create domain-specific configuration file for starting number management.

**File:** `app/Domain/StartingNumber/config/starting-numbers.php`

**Acceptance Criteria:**
- [ ] Config file created in domain directory
- [ ] Track-specific number ranges structure defined
- [ ] Overflow bucket configuration included
- [ ] Assignment strategy options defined
- [ ] Format options (padding, prefix) included
- [ ] Default values sensible for production
- [ ] Configuration documented with comments
- [ ] Config follows Laravel conventions
- [ ] Environment variable support added

**Configuration Structure:**
```php
<?php

return [
    // Feature toggle (also in main config)
    'enabled' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
    
    // Per-track number ranges
    // Configured via Settings Page or manually here
    'tracks' => [
        // Example configuration:
        // 'track-uuid-1' => [
        //     'start' => 1,
        //     'end' => 500,
        //     'name' => '50km Track',
        // ],
    ],
    
    // Global overflow bucket for numbers exceeding track ranges
    'overflow' => [
        'enabled' => true,
        'start' => 9001,
        'end' => 9999,
    ],
    
    // Number formatting
    'format' => [
        'padding' => 4,           // Leading zeros: 0001, 0002
        'prefix' => '',           // Optional prefix: 'BIB-0001'
        'suffix' => '',           // Optional suffix: '0001-A'
    ],
    
    // Assignment strategy
    'strategy' => 'sequential',   // 'sequential' | 'random'
    
    // Reserved numbers (not assigned)
    'reserved' => [
        // Example: [1, 13, 666, 999]
    ],
];
```

**Implementation Notes:**
- Config will be merged in ServiceProvider
- Settings Page will update this config
- Track UUIDs/IDs used as keys

---

### Task 2.2: Create StartingNumber Filament Plugin 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 45 minutes

**Description:**
Create Filament plugin for StartingNumber domain to register UI components.

**File:** `app/Domain/StartingNumber/StartingNumberPlugin.php`

**Acceptance Criteria:**
- [ ] Plugin implements `Filament\Contracts\Plugin`
- [ ] `getId()` returns 'starting-numbers'
- [ ] `make()` static method for fluent instantiation
- [ ] `register()` discovers Pages and Widgets
- [ ] Settings page explicitly registered
- [ ] Statistics widget explicitly registered
- [ ] Plugin respects feature toggle
- [ ] Navigation group set to 'Configuration' or similar
- [ ] Navigation icons set appropriately
- [ ] Navigation sort order specified

**Code Template:**
```php
<?php

namespace App\Domain\StartingNumber;

use Filament\Contracts\Plugin;
use Filament\Panel;

class StartingNumberPlugin implements Plugin
{
    public function getId(): string
    {
        return 'starting-numbers';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! config('starting-numbers.enabled', true)) {
            return;
        }

        $panel
            ->pages([
                Filament\Pages\ManageStartingNumbers::class,
            ])
            ->widgets([
                Filament\Widgets\StartingNumberStatsWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Boot logic if needed
    }
}
```

**Implementation Notes:**
- Plugin registered in ServiceProvider via `Panel::configureUsing()`
- Feature toggle checked before registration
- Components explicitly registered (not discovered)

---

### Task 2.3: Create Settings Page 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P1 (High)  
**Estimated Time:** 2-3 hours

**Description:**
Create Filament settings page for configuring starting number ranges and overflow bucket.

**File:** `app/Domain/StartingNumber/Filament/Pages/ManageStartingNumbers.php`

**Acceptance Criteria:**
- [ ] Page extends Filament Page or SettingsPage
- [ ] Form includes track selection dropdown
- [ ] Number range inputs (start/end) per track
- [ ] Overflow bucket configuration section
- [ ] Format options (padding, prefix, suffix)
- [ ] Assignment strategy radio/select
- [ ] Reserved numbers input (comma-separated)
- [ ] Validation prevents overlapping ranges
- [ ] Validation ensures overflow doesn't overlap
- [ ] Save updates config file via Spatie Settings or direct
- [ ] Success notifications on save
- [ ] Error notifications with details
- [ ] Permission checks (admin only)
- [ ] Preview of formatted numbers
- [ ] Help text for each field

**UI Layout:**
```
┌─ Manage Starting Numbers ─────────────────────────────┐
│                                                        │
│ Track Number Ranges                                   │
│ ┌────────────────────────────────────────────────┐   │
│ │ Track: [50km Track ▼]                          │   │
│ │ Start Number: [1    ]  End Number: [500    ]  │   │
│ │ [+ Add Track Range]                            │   │
│ └────────────────────────────────────────────────┘   │
│                                                        │
│ Overflow Bucket                                       │
│ ┌────────────────────────────────────────────────┐   │
│ │ ☑ Enable overflow bucket                       │   │
│ │ Start: [9001]  End: [9999]                     │   │
│ └────────────────────────────────────────────────┘   │
│                                                        │
│ Number Format                                         │
│ ┌────────────────────────────────────────────────┐   │
│ │ Padding: [4] digits (e.g., 0001)               │   │
│ │ Prefix: [      ] (optional)                    │   │
│ │ Preview: BIB-0042                              │   │
│ └────────────────────────────────────────────────┘   │
│                                                        │
│ Assignment Strategy                                   │
│ ┌────────────────────────────────────────────────┐   │
│ │ ○ Sequential  ● Random                         │   │
│ └────────────────────────────────────────────────┘   │
│                                                        │
│ Reserved Numbers (comma-separated)                    │
│ ┌────────────────────────────────────────────────┐   │
│ │ [1, 13, 666                                ]   │   │
│ └────────────────────────────────────────────────┘   │
│                                                        │
│                              [Cancel] [Save Settings] │
└────────────────────────────────────────────────────────┘
```

**Form Components:**
- Repeater for track ranges
- Toggle for overflow
- Text inputs for numbers
- Select for strategy
- Tags input for reserved numbers

**Validation Rules:**
- Start < End for each range
- No overlapping ranges
- Overflow doesn't overlap any track range
- Reserved numbers within valid ranges

**Implementation Notes:**
- Use Filament Forms API
- Store to config file or Spatie Settings
- Real-time validation
- Preview updates on change

---

### Task 2.4: Create Statistics Widget 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P2 (Medium)  
**Estimated Time:** 1-2 hours

**Description:**
Create Filament widget showing starting number assignment statistics.

**File:** `app/Domain/StartingNumber/Filament/Widgets/StartingNumberStatsWidget.php`

**Acceptance Criteria:**
- [ ] Widget extends Filament StatsOverviewWidget or similar
- [ ] Displays total numbers assigned
- [ ] Shows available numbers per track
- [ ] Indicates overflow bucket usage
- [ ] Displays assignment completion percentage
- [ ] Shows last assignment timestamp
- [ ] Responsive design (mobile-friendly)
- [ ] Can be placed on dashboard
- [ ] Refreshes on page load
- [ ] Cached for performance (5 minutes)
- [ ] Click to navigate to settings page
- [ ] Color-coded status (green/yellow/red)

**Statistics to Display:**
1. **Total Assigned:** Count of all assigned numbers
2. **By Track:** Breakdown per track with progress bars
3. **Overflow Usage:** Count and percentage of overflow used
4. **Available:** Total remaining numbers
5. **Last Assignment:** Timestamp of last number assigned
6. **Completion Rate:** Percentage of capacity used

**Widget Layout:**
```
┌─ Starting Numbers ─────────────────────────────┐
│                                                 │
│  📊 Total Assigned: 487 / 1500 (32%)          │
│                                                 │
│  Track Breakdown:                               │
│  • 50km:  234/500  [████████░░░] 47%          │
│  • 100km: 189/500  [███████░░░░] 38%          │
│  • 200km: 64/500   [██░░░░░░░░░] 13%          │
│                                                 │
│  🪣 Overflow: 0/999 (0%)                       │
│  🕒 Last Assigned: 2 minutes ago               │
│                                                 │
│  [⚙️ Configure]                                 │
└─────────────────────────────────────────────────┘
```

**Implementation Notes:**
- Query Registration model for counts
- Cache results for 5 minutes
- Use Filament's built-in stat components
- Add click handler to settings page

---

### Task 2.5: Update StartingNumber Service Provider 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 30 minutes

**Description:**
Update existing service provider to register config and plugin.

**File:** `app/Domain/StartingNumber/StartingNumberServiceProvider.php` (UPDATE)

**Acceptance Criteria:**
- [ ] Registers domain config via `mergeConfigFrom()`
- [ ] Publishes config via `publishes()` with tag
- [ ] Registers Filament plugin via `Panel::configureUsing()`
- [ ] Maintains existing event listener registration
- [ ] Checks feature toggle before plugin registration
- [ ] Service singleton registration preserved
- [ ] Boot order doesn't break dependencies
- [ ] Config publishing tag: 'starting-numbers-config'
- [ ] Code follows Laravel conventions

**Current Code:**
```php
public function register(): void
{
    $this->app->singleton(StartingNumberService::class);
}

public function boot(): void
{
    if (! config('steppenreg.features.starting_numbers', true)) {
        return;
    }

    Event::listen(
        RegistrationDrawn::class,
        AssignStartingNumberOnDrawn::class
    );
}
```

**Updated Code:**
```php
public function register(): void
{
    // Register service
    $this->app->singleton(StartingNumberService::class);
    
    // Merge domain config
    $this->mergeConfigFrom(
        __DIR__.'/config/starting-numbers.php',
        'starting-numbers'
    );
}

public function boot(): void
{
    // Check feature toggle
    if (! config('steppenreg.features.starting_numbers', true)) {
        return;
    }
    
    // Publish config
    $this->publishes([
        __DIR__.'/config/starting-numbers.php' => config_path('starting-numbers.php'),
    ], 'starting-numbers-config');
    
    // Register event listener
    Event::listen(
        RegistrationDrawn::class,
        AssignStartingNumberOnDrawn::class
    );
    
    // Register Filament plugin
    Panel::configureUsing(function (Panel $panel): void {
        if ($panel->getId() === 'admin') {
            $panel->plugin(StartingNumberPlugin::make());
        }
    });
}
```

**Implementation Notes:**
- Config merge happens in `register()`
- Plugin registration in `boot()`
- Feature toggle checked early
- Only register for 'admin' panel

---

### Task 2.6: Create Domain Tests 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P1 (High)  
**Estimated Time:** 2-3 hours

**Description:**
Create comprehensive test suite for StartingNumber domain.

**Directory:** `app/Domain/StartingNumber/Tests/`

**Acceptance Criteria:**
- [ ] Test directory structure created
- [ ] Unit tests for StartingNumberService
- [ ] Test sequential number assignment
- [ ] Test random number assignment
- [ ] Test overflow bucket handling
- [ ] Test track-specific ranges
- [ ] Test format options (padding, prefix)
- [ ] Test reserved numbers exclusion
- [ ] Feature tests for Settings Page
- [ ] Feature tests for Stats Widget
- [ ] Test config validation
- [ ] Test feature toggle behavior
- [ ] Test event listener (RegistrationDrawn)
- [ ] Test graceful handling when disabled
- [ ] All tests pass
- [ ] Test coverage > 80% for domain
- [ ] Tests run via `php artisan test`

**Test Files Structure:**
```
app/Domain/StartingNumber/Tests/
├── Unit/
│   ├── StartingNumberServiceTest.php
│   ├── NumberFormatTest.php
│   ├── OverflowBucketTest.php
│   └── RangeValidationTest.php
└── Feature/
    ├── StartingNumberAssignmentTest.php
    ├── SettingsPageTest.php
    ├── StatsWidgetTest.php
    ├── EventListenerTest.php
    └── FeatureToggleTest.php
```

**Key Test Cases:**

**StartingNumberServiceTest:**
- Sequential assignment increases by 1
- Random assignment within range
- Overflow used when range exhausted
- Reserved numbers skipped
- Format applied correctly
- Thread-safe assignment (no duplicates)

**SettingsPageTest:**
- Page accessible by admin
- Form saves to config
- Validation prevents overlaps
- Preview updates correctly
- Unauthorized users blocked

**StatsWidgetTest:**
- Widget displays correct counts
- Track breakdown accurate
- Overflow usage calculated
- Performance acceptable (cached)

**EventListenerTest:**
- Number assigned on RegistrationDrawn
- Respects feature toggle
- Emits StartingNumberAssigned event
- Doesn't crash if range exhausted

**Implementation Notes:**
- Use factories for test data
- Mock events where appropriate
- Test with various configurations
- Use RefreshDatabase trait

---

### Task 2.7: Create Domain README 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P2 (Medium)  
**Estimated Time:** 45 minutes

**Description:**
Create comprehensive documentation for StartingNumber domain.

**File:** `app/Domain/StartingNumber/README.md`

**Acceptance Criteria:**
- [ ] Domain purpose clearly explained
- [ ] Features list complete
- [ ] Configuration options documented
- [ ] UI components documented (with screenshots if possible)
- [ ] Events emitted/listened documented
- [ ] Dependencies on core models listed
- [ ] How to enable/disable documented
- [ ] Configuration examples provided
- [ ] Testing instructions included
- [ ] Common issues and solutions listed
- [ ] Code examples for programmatic use
- [ ] API reference for public methods

**Document Structure:**
```markdown
# StartingNumber Domain

## Purpose
Automatically assign starting numbers to registrations that are drawn in the lottery.

## Features
- Automatic assignment on draw
- Configurable per-track number ranges
- Global overflow bucket
- Multiple format options
- Sequential or random assignment
- Reserved number exclusion
- Settings UI for configuration
- Statistics widget

## Configuration

### Via Settings Page
[Screenshot of Settings Page]

### Via Config File
[Code example]

## Events

### Listens To
- `App\Domain\Draw\Events\RegistrationDrawn`

### Emits
- `App\Domain\StartingNumber\Events\StartingNumberAssigned`
- `App\Domain\StartingNumber\Events\StartingNumberCleared`

## Dependencies
- `App\Models\Registration` (core model)

## Enable/Disable
[Instructions]

## Programmatic Usage
[Code examples]

## Testing
[Test commands]

## Troubleshooting
[Common issues]
```

**Implementation Notes:**
- Use clear language
- Provide screenshots if UI exists
- Include code examples
- Keep up-to-date with changes

---

## Phase 3: Draw Domain Modularization 📋 PLANNED

**Goal:** Create service provider, plugin, and complete modularization of Draw domain  
**Estimated Time:** 4-6 hours  
**Dependencies:** Phase 2 complete

---

### Task 3.1: Create Draw Configuration 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 30 minutes

**Description:**
Create domain-specific configuration file for draw management.

**File:** `app/Domain/Draw/config/draw.php`

**Acceptance Criteria:**
- [ ] Config file created in domain directory
- [ ] Default available spots configuration
- [ ] Team atomicity rules defined
- [ ] Execution permission settings included
- [ ] Notification preferences defined
- [ ] Algorithm parameters configurable
- [ ] Default values production-ready
- [ ] Documentation comments clear
- [ ] Environment variable support

**Configuration Structure:**
```php
<?php

return [
    // Feature toggle (also in main config)
    'enabled' => env('STEPPENREG_DRAW_ENABLED', true),
    
    // Default spots calculation
    // null = use track capacity from database
    'default_spots' => null,
    
    // Team handling
    'team_atomicity' => true,  // Teams selected as atomic units
    
    // Permissions (who can execute/preview draws)
    'permissions' => [
        'execute_draw' => ['admin'],
        'preview_draw' => ['admin', 'manager'],
        'view_results' => ['admin', 'manager', 'staff'],
    ],
    
    // Notification settings
    'notifications' => [
        'send_on_drawn' => true,        // Email drawn participants
        'send_on_not_drawn' => true,    // Email not drawn participants
        'batch_size' => 50,             // Emails per batch
        'delay_between_batches' => 10,  // Seconds
    ],
    
    // Algorithm parameters
    'algorithm' => [
        'shuffle_seed' => null,  // For reproducible testing (null = random)
        'max_iterations' => 1000, // Safety limit
    ],
    
    // UI settings
    'ui' => [
        'show_statistics' => true,
        'show_preview' => true,
        'require_confirmation' => true,
    ],
];
```

**Implementation Notes:**
- Merged in DrawServiceProvider
- Published with tag 'draw-config'
- Used by DrawService and ManageDraw page

---

### Task 3.2: Create Draw Service Provider 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 45 minutes

**Description:**
Create service provider for Draw domain to register services, config, and plugin.

**File:** `app/Domain/Draw/DrawServiceProvider.php` (NEW)

**Acceptance Criteria:**
- [ ] Extends Laravel ServiceProvider
- [ ] Registers DrawService as singleton
- [ ] Registers domain config via `mergeConfigFrom()`
- [ ] Publishes domain config with tag
- [ ] Publishes migrations with tag
- [ ] Registers Filament plugin via `Panel::configureUsing()`
- [ ] Checks feature toggle before registration
- [ ] Boot order correct (after core providers)
- [ ] No event listeners needed (Draw doesn't listen to anything)
- [ ] Code follows Laravel conventions

**Code Template:**
```php
<?php

namespace App\Domain\Draw;

use App\Domain\Draw\Services\DrawService;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class DrawServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register service
        $this->app->singleton(DrawService::class);
        
        // Merge domain config
        $this->mergeConfigFrom(
            __DIR__.'/config/draw.php',
            'draw'
        );
    }

    public function boot(): void
    {
        // Check feature toggle
        if (! config('steppenreg.features.draw', true)) {
            return;
        }
        
        // Publish config
        $this->publishes([
            __DIR__.'/config/draw.php' => config_path('draw.php'),
        ], 'draw-config');
        
        // Publish migrations
        $this->publishes([
            __DIR__.'/database/migrations' => database_path('migrations'),
        ], 'draw-migrations');
        
        // Register Filament plugin
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->plugin(DrawPlugin::make());
            }
        });
    }
}
```

**Implementation Notes:**
- Service registered in `register()`
- Plugin registered in `boot()`
- Two publish tags: config and migrations
- Only registers for 'admin' panel

---

### Task 3.3: Create Draw Filament Plugin 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 45 minutes

**Description:**
Create Filament plugin for Draw domain to register pages and widgets.

**File:** `app/Domain/Draw/DrawPlugin.php` (NEW)

**Acceptance Criteria:**
- [ ] Plugin implements `Filament\Contracts\Plugin`
- [ ] `getId()` returns 'draw'
- [ ] `make()` static method for fluent instantiation
- [ ] `register()` explicitly registers ManageDraw page
- [ ] Explicitly registers DrawStatsWidget
- [ ] Explicitly registers TrackStatsWidget
- [ ] Navigation group set to 'Registration'
- [ ] Navigation sort order appropriate
- [ ] Plugin respects feature toggle
- [ ] Livewire components registered if custom ones exist
- [ ] Code follows Filament conventions

**Code Template:**
```php
<?php

namespace App\Domain\Draw;

use App\Domain\Draw\Filament\Pages\ManageDraw;
use App\Domain\Draw\Filament\Widgets\DrawStatsWidget;
use App\Domain\Draw\Filament\Widgets\TrackStatsWidget;
use Filament\Contracts\Plugin;
use Filament\Panel;

class DrawPlugin implements Plugin
{
    public function getId(): string
    {
        return 'draw';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! config('draw.enabled', true)) {
            return;
        }

        $panel
            ->pages([
                ManageDraw::class,
            ])
            ->widgets([
                DrawStatsWidget::class,
                TrackStatsWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Boot logic if needed
        // Register Livewire components if custom
    }
}
```

**Components Registered:**
- ManageDraw page (existing)
- DrawStatsWidget (existing)
- TrackStatsWidget (existing)

**Implementation Notes:**
- Explicit registration (not discovery)
- Feature toggle checked in register()
- Plugin registered in DrawServiceProvider

---

### Task 3.4: Move Migrations to Domain 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P1 (High)  
**Estimated Time:** 30 minutes

**Description:**
Move draw-related migrations to domain directory and setup publishing.

**Source:** `database/migrations/*_create_draws_table.php`  
**Target:** `app/Domain/Draw/database/migrations/`

**Acceptance Criteria:**
- [ ] Migration directory created in domain
- [ ] `create_draws_table` migration moved/copied
- [ ] ServiceProvider publishes migrations correctly
- [ ] Original migration kept in database/migrations/ (for existing installs)
- [ ] Migration runs successfully via `php artisan migrate`
- [ ] Migration tag 'draw-migrations' works
- [ ] Fresh install works correctly
- [ ] Existing install upgrade works
- [ ] Rollback works correctly
- [ ] Migration timestamp preserved

**Migration File:**
```
app/Domain/Draw/database/migrations/
└── YYYY_MM_DD_HHMMSS_create_draws_table.php
```

**Publishing Command:**
```bash
php artisan vendor:publish --tag=draw-migrations
```

**Steps:**
1. Create directory: `app/Domain/Draw/database/migrations/`
2. Copy migration file (keep original for backward compatibility)
3. Update DrawServiceProvider to publish
4. Test fresh migration
5. Test on existing database (should skip if already run)

**Implementation Notes:**
- Keep original migration for existing deployments
- New deployments will publish from domain
- Migration uses same timestamp to avoid duplicates
- Add note in migration header about domain ownership

---

### Task 3.5: Update Draw Filament Components 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P1 (High)  
**Estimated Time:** 1-2 hours

**Description:**
Verify and update Draw Filament components for plugin registration.

**Files:**
- `app/Domain/Draw/Filament/Pages/ManageDraw.php`
- `app/Domain/Draw/Filament/Widgets/DrawStatsWidget.php`
- `app/Domain/Draw/Filament/Widgets/TrackStatsWidget.php`

**Acceptance Criteria:**
- [ ] All components namespaced correctly
- [ ] Navigation configured appropriately
- [ ] Navigation group matches plugin setting
- [ ] Navigation sort order set
- [ ] No global discovery conflicts
- [ ] Components work when plugin registered
- [ ] Components hidden when plugin disabled
- [ ] Livewire components registered if needed
- [ ] Routes work correctly
- [ ] Authorization checks in place
- [ ] No breaking changes to functionality
- [ ] UI looks correct
- [ ] All interactions work

**ManageDraw Page Updates:**
```php
// Verify navigation settings
protected static ?string $navigationGroup = 'Registration';
protected static ?int $navigationSort = 30;

// Verify authorization
public static function canAccess(): bool
{
    return auth()->user()->can('execute_draw');
}
```

**Widget Updates:**
```php
// Verify widgets can be placed on dashboard
protected static bool $isLazy = false; // Or true if heavy

// Verify polling/refresh behavior
protected static ?string $pollingInterval = null;
```

**Testing Checklist:**
- [ ] ManageDraw page loads correctly
- [ ] Draw execution still works
- [ ] Widgets display on dashboard
- [ ] Navigation menu shows page
- [ ] Permissions respected
- [ ] No console errors
- [ ] No route conflicts

**Implementation Notes:**
- Minimal changes needed (already in domain)
- Mostly verification and navigation tweaks
- Ensure plugin registration works
- Test with feature toggle disabled

---

### Task 3.6: Create Domain Tests 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P1 (High)  
**Estimated Time:** 3-4 hours

**Description:**
Create comprehensive test suite for Draw domain.

**Directory:** `app/Domain/Draw/Tests/`

**Acceptance Criteria:**
- [ ] Test directory structure created
- [ ] Unit tests for DrawService
- [ ] Test selection algorithm logic
- [ ] Test team atomicity (all or none)
- [ ] Test spot allocation accuracy
- [ ] Test draw execution flow
- [ ] Test event dispatching (3 event types)
- [ ] Test exception handling
- [ ] Feature tests for ManageDraw page
- [ ] Test widgets display correctly
- [ ] Test permissions (who can execute)
- [ ] Test feature toggle behavior
- [ ] Integration test with StartingNumber
- [ ] Test with various team/individual mixes
- [ ] All tests pass
- [ ] Test coverage > 80% for domain
- [ ] Performance tests for large datasets
- [ ] Tests run via `php artisan test`

**Test Files Structure:**
```
app/Domain/Draw/Tests/
├── Unit/
│   ├── DrawServiceTest.php
│   ├── SelectionAlgorithmTest.php
│   ├── TeamAtomicityTest.php
│   └── SpotAllocationTest.php
└── Feature/
    ├── DrawExecutionTest.php
    ├── ManageDrawPageTest.php
    ├── DrawWidgetsTest.php
    ├── DrawPermissionsTest.php
    ├── DrawEventsTest.php
    ├── FeatureToggleTest.php
    └── StartingNumberIntegrationTest.php
```

**Key Test Cases:**

**DrawServiceTest:**
- Service registered as singleton
- executeDraw() creates Draw model
- Draw record includes all metadata
- Prevents duplicate draws for same track
- Handles insufficient registrations gracefully

**SelectionAlgorithmTest:**
- Random selection is truly random
- Selection respects available spots
- Individuals selected correctly
- Teams selected as units
- Mixed teams/individuals works
- Overflow handled (when oversubscribed)
- Undersubscribed draws work

**TeamAtomicityTest:**
- Full team selected or none
- Partial team never selected
- Team size counted correctly
- Mixed team sizes work
- Large teams handled

**DrawExecutionTest:**
- Full draw flow executes
- Registrations updated correctly
- Events dispatched correctly
- Transactions rollback on error
- Drawn_at timestamp set
- Draw_status updated

**ManageDrawPageTest:**
- Page accessible by admin
- Form submission executes draw
- Success notification shown
- Error handling works
- Results displayed correctly
- Unauthorized users blocked

**DrawEventsTest:**
- RegistrationDrawn event dispatched for each selected
- RegistrationNotDrawn event dispatched for each not selected
- DrawExecuted event dispatched once
- Event data correct
- Listeners can be registered

**StartingNumberIntegrationTest:**
- Draw execution triggers number assignment
- Numbers assigned to drawn registrations
- Not drawn registrations have no numbers
- Integration works when both enabled
- Draw works when StartingNumber disabled

**Performance Tests:**
- Draw with 1000 registrations completes in <10 seconds
- Draw with 100 teams completes in <10 seconds
- Memory usage acceptable

**Implementation Notes:**
- Use factories for test data
- Create various registration scenarios
- Mock events where appropriate
- Test edge cases (0 spots, 0 registrations)
- Use RefreshDatabase trait

---

### Task 3.7: Create Domain README 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P2 (Medium)  
**Estimated Time:** 45 minutes

**Description:**
Create comprehensive documentation for Draw domain.

**File:** `app/Domain/Draw/README.md`

**Acceptance Criteria:**
- [ ] Domain purpose clearly explained
- [ ] Draw algorithm documented in detail
- [ ] Team atomicity explained
- [ ] Configuration options documented
- [ ] UI components listed (page + widgets)
- [ ] Events emitted documented
- [ ] Dependencies on core models listed
- [ ] How to enable/disable documented
- [ ] Testing instructions included
- [ ] Common issues and solutions listed
- [ ] Code examples for programmatic use
- [ ] API reference for public methods
- [ ] Migration instructions included

**Document Structure:**
```markdown
# Draw Domain

## Purpose
Execute lottery-based selection for oversubscribed cycling event tracks.

## Features
- Random selection algorithm
- Team atomicity (all or none)
- Fair distribution
- Event-driven notifications
- Execution UI (ManageDraw page)
- Statistics widgets
- Audit trail (Draw model)

## How It Works

### Selection Algorithm
[Detailed explanation of algorithm]
[Diagrams if helpful]

### Team Atomicity
[Explanation of how teams are handled]

## Configuration

### Via Config File
[Code example]

### Environment Variables
[List of env vars]

## Events

### Emits
- `App\Domain\Draw\Events\RegistrationDrawn`
- `App\Domain\Draw\Events\RegistrationNotDrawn`
- `App\Domain\Draw\Events\DrawExecuted`

### Event Data
[Structure of each event]

## Dependencies
- `App\Models\Registration` (core model)
- `App\Models\Team` (core model)

## Enable/Disable
[Instructions]

## UI Components

### ManageDraw Page
[Screenshot and usage]

### Widgets
[Description of each widget]

## Programmatic Usage
[Code examples]

## Testing
[Test commands]

## Migrations
[How to publish and run]

## Troubleshooting
[Common issues]
```

**Implementation Notes:**
- Document the algorithm clearly
- Provide visual diagrams if helpful
- Include code examples
- Keep up-to-date

---

## Phase 4: Integration & Testing 📋 PLANNED

**Goal:** Ensure cross-domain integration works and comprehensive testing passes  
**Estimated Time:** 2-3 hours  
**Dependencies:** Phases 2 & 3 complete

---

### Task 4.1: Update Main Configuration 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 15 minutes

**Description:**
Update main application config to include Draw feature toggle.

**File:** `config/steppenreg.php` (UPDATE)

**Acceptance Criteria:**
- [ ] Draw feature toggle added
- [ ] Both toggles documented with comments
- [ ] Environment variable support for both
- [ ] Default values set correctly (both true)
- [ ] Config cache works correctly (`php artisan config:cache`)
- [ ] Config clear works (`php artisan config:clear`)
- [ ] Documentation updated

**Current Config:**
```php
'features' => [
    'starting_numbers' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
],
```

**Updated Config:**
```php
'features' => [
    /*
     * Starting Numbers
     *
     * When enabled, registrations that are drawn will automatically
     * receive a starting number. Disable this to run events without
     * starting number assignment.
     *
     * UI: Settings page for number range configuration
     * Widget: Starting number statistics widget
     */
    'starting_numbers' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),

    /*
     * Draw System
     *
     * When enabled, provides lottery-based selection system for
     * oversubscribed tracks. Includes draw execution UI and widgets.
     *
     * UI: ManageDraw page for executing draws
     * Widgets: Draw statistics and track statistics
     */
    'draw' => env('STEPPENREG_DRAW_ENABLED', true),
],
```

**Environment Variables:**
```env
# Feature Toggles
STEPPENREG_STARTING_NUMBERS_ENABLED=true
STEPPENREG_DRAW_ENABLED=true
```

**Implementation Notes:**
- Test with both features enabled
- Test with each disabled independently
- Test with both disabled
- Verify config cache behavior

---

### Task 4.2: Register Draw Service Provider 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 15 minutes

**Description:**
Register Draw service provider in application bootstrap.

**File:** `bootstrap/providers.php` (UPDATE)

**Acceptance Criteria:**
- [ ] DrawServiceProvider added to providers array
- [ ] Provider order correct (after StartingNumber)
- [ ] Application boots successfully
- [ ] Config cache works (`php artisan config:cache`)
- [ ] Route cache works (`php artisan route:cache`)
- [ ] No boot errors in logs
- [ ] DrawService resolvable from container

**Current Providers:**
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,

    // Domain Service Providers
    App\Domain\StartingNumber\StartingNumberServiceProvider::class,
];
```

**Updated Providers:**
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,

    // Domain Service Providers
    App\Domain\StartingNumber\StartingNumberServiceProvider::class,
    App\Domain\Draw\DrawServiceProvider::class,
];
```

**Verification Commands:**
```bash
# Clear all caches
php artisan optimize:clear

# Test service resolution
php artisan tinker
>>> app(\App\Domain\Draw\Services\DrawService::class)
>>> app(\App\Domain\StartingNumber\Services\StartingNumberService::class)

# Test config
php artisan tinker
>>> config('draw.enabled')
>>> config('starting-numbers.enabled')
>>> config('steppenreg.features.draw')

# Cache and test
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Implementation Notes:**
- Add after StartingNumber (order matters)
- Test application boots
- Verify services resolve
- Check no duplicate registrations

---

### Task 4.3: Update phpunit.xml 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 15 minutes

**Description:**
Update PHPUnit configuration to include domain test directories.

**File:** `phpunit.xml` (UPDATE)

**Acceptance Criteria:**
- [ ] Domain test directories added to testsuites
- [ ] Tests discoverable via `php artisan test`
- [ ] Tests discoverable via `vendor/bin/phpunit`
- [ ] Test namespaces work correctly
- [ ] Coverage reports include domain code
- [ ] All tests run successfully
- [ ] No duplicate test execution
- [ ] Filters work (--filter, --testsuite)

**Current Configuration:**
```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
</testsuites>
```

**Updated Configuration:**
```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
        <directory>app/Domain/*/Tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
        <directory>app/Domain/*/Tests/Feature</directory>
    </testsuite>
</testsuites>

<!-- Optional: Separate domain test suite -->
<testsuites>
    <testsuite name="Domain">
        <directory>app/Domain/*/Tests</directory>
    </testsuite>
</testsuites>
```

**Test Commands:**
```bash
# Run all tests
php artisan test

# Run only unit tests
php artisan test --testsuite=Unit

# Run only domain tests
php artisan test --testsuite=Domain

# Run specific domain tests
php artisan test --filter=StartingNumber
php artisan test --filter=Draw

# Run with coverage
php artisan test --coverage
```

**Implementation Notes:**
- Wildcard pattern `app/Domain/*/Tests/Unit` includes all domains
- Separate Domain testsuite for convenience
- Verify coverage reports include domain code
- Test filters work correctly

---

### Task 4.4: Remove Global Discovery Conflicts 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P1 (High)  
**Estimated Time:** 30 minutes

**Description:**
Update AdminPanelProvider to prevent double-registration of domain components.

**File:** `app/Providers/Filament/AdminPanelProvider.php` (UPDATE)

**Acceptance Criteria:**
- [ ] Global discovery excludes domain directories
- [ ] Domain components only loaded via plugins
- [ ] No duplicate navigation items
- [ ] No duplicate widgets on dashboard
- [ ] No duplicate pages
- [ ] No route conflicts
- [ ] Application works with domains enabled
- [ ] Application works with domains disabled
- [ ] No Livewire component conflicts

**Current Discovery:**
```php
->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
```

**Issue:**
The current discovery will find:
- `app/Filament/Pages/*` (good - core pages)
- `app/Domain/Draw/Filament/Pages/*` (bad - registered via plugin!)
- `app/Filament/Widgets/*` (good - core widgets)
- `app/Domain/Draw/Filament/Widgets/*` (bad - registered via plugin!)

**Solution Options:**

**Option A: Explicit Exclusion (Recommended)**
```php
->discoverPages(
    in: app_path('Filament/Pages'),
    for: 'App\Filament\Pages'
)
// Domain pages registered via plugins
->discoverWidgets(
    in: app_path('Filament/Widgets'),
    for: 'App\Filament\Widgets'
)
// Domain widgets registered via plugins
```

**Option B: Custom Discovery Logic**
```php
->pages(
    collect(File::files(app_path('Filament/Pages')))
        ->map(fn ($file) => 'App\\Filament\\Pages\\' . $file->getFilenameWithoutExtension())
        ->filter(fn ($class) => is_subclass_of($class, Page::class))
        ->toArray()
)
```

**Recommendation:** Option A - Current discovery already works correctly because:
1. `app/Filament/Pages/` doesn't include domain subdirectories
2. `app/Domain/Draw/Filament/Pages/` is a different path
3. Discovery only searches specified directory, not subdirectories recursively

**Action:** Verify current discovery doesn't have conflicts, no changes likely needed!

**Verification:**
```bash
# Check navigation menu for duplicates
# Check dashboard for duplicate widgets
# Check routes for conflicts:
php artisan route:list | grep Draw
php artisan route:list | grep StartingNumber
```

**Implementation Notes:**
- Test thoroughly with feature toggles
- Check Livewire component names
- Verify no route collisions
- Test navigation menu structure

---

### Task 4.5: Integration Testing 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 1-2 hours

**Description:**
Test cross-domain integration and feature toggles comprehensively.

**Test File:** `tests/Feature/DomainIntegrationTest.php` (NEW)

**Acceptance Criteria:**
- [ ] All integration test scenarios pass
- [ ] Both domains enabled works (normal operation)
- [ ] Draw enabled, StartingNumber disabled works
- [ ] Draw disabled, StartingNumber enabled N/A (StartingNumber needs Draw events)
- [ ] Both domains disabled works
- [ ] Draw execution triggers StartingNumber assignment
- [ ] Draw without StartingNumber works correctly
- [ ] No broken events when domain disabled
- [ ] UI components hidden when disabled
- [ ] Config toggles work correctly
- [ ] No errors in logs
- [ ] Database integrity maintained
- [ ] Transactions work correctly

**Test Scenarios:**

**Scenario 1: Both Enabled (Normal Operation)**
```php
test('draw execution assigns starting numbers when both enabled', function () {
    // Given both features enabled
    config(['steppenreg.features.draw' => true]);
    config(['steppenreg.features.starting_numbers' => true]);
    
    // When a draw is executed
    $result = app(DrawService::class)->executeDraw(...);
    
    // Then starting numbers are assigned
    expect($result->registrations()->drawn())
        ->each->toHaveKey('starting_number');
});
```

**Scenario 2: Draw Enabled, StartingNumber Disabled**
```php
test('draw executes without starting numbers when disabled', function () {
    // Given draw enabled, starting numbers disabled
    config(['steppenreg.features.draw' => true]);
    config(['steppenreg.features.starting_numbers' => false]);
    
    // When a draw is executed
    $result = app(DrawService::class)->executeDraw(...);
    
    // Then no starting numbers assigned
    expect($result->registrations()->drawn())
        ->each(fn($reg) => expect($reg->starting_number)->toBeNull());
});
```

**Scenario 3: UI Components Hidden**
```php
test('domain UI hidden when features disabled', function () {
    // Given features disabled
    config(['steppenreg.features.draw' => false]);
    config(['steppenreg.features.starting_numbers' => false]);
    
    // When admin visits dashboard
    $response = $this->actingAs($admin)->get('/admin');
    
    // Then domain components not rendered
    $response->assertDontSee('Manage Draw');
    $response->assertDontSee('Starting Numbers');
});
```

**Scenario 4: Event Graceful Handling**
```php
test('starting number listener ignored when disabled', function () {
    // Given starting numbers disabled
    config(['steppenreg.features.starting_numbers' => false]);
    
    // When RegistrationDrawn event fired
    event(new RegistrationDrawn($registration));
    
    // Then no error thrown, no number assigned
    expect($registration->fresh()->starting_number)->toBeNull();
});
```

**Scenario 5: Database Migrations**
```php
test('migrations run cleanly with features disabled', function () {
    // Test fresh migration with domains disabled
    // Verify tables created/not created based on config
});
```

**Additional Tests:**
- Config cache with feature toggles
- Service resolution with features disabled
- Plugin registration conditional on config
- Navigation menu reflects enabled features
- Widget visibility based on features
- Route availability based on features

**Implementation Notes:**
- Use RefreshDatabase trait
- Test with various registration scenarios
- Verify event listeners work
- Check no memory leaks
- Verify database transactions

---

### Task 4.6: Performance Testing 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P2 (Medium)  
**Estimated Time:** 1 hour

**Description:**
Verify modularization doesn't impact application performance.

**Test File:** `tests/Performance/DomainPerformanceTest.php` (NEW)

**Acceptance Criteria:**
- [ ] Page load times unchanged (<200ms regression)
- [ ] Draw execution time unchanged (<5s for 1000 registrations)
- [ ] Starting number assignment time acceptable (<1s for 500)
- [ ] No N+1 queries introduced
- [ ] Memory usage acceptable (<50MB increase)
- [ ] Query count monitored and acceptable
- [ ] Dashboard load time acceptable (<1s)
- [ ] Config overhead minimal

**Benchmarks:**

**Draw Execution Performance:**
```php
test('draw execution performance for 1000 registrations', function () {
    $registrations = Registration::factory()->count(1000)->create();
    
    $start = microtime(true);
    $result = app(DrawService::class)->executeDraw($trackId, 500);
    $duration = microtime(true) - $start;
    
    expect($duration)->toBeLessThan(5.0); // < 5 seconds
});
```

**Starting Number Assignment Performance:**
```php
test('starting number assignment performance', function () {
    $registrations = Registration::factory()->count(500)->create();
    
    $start = microtime(true);
    foreach ($registrations as $reg) {
        app(StartingNumberService::class)->assignNumber($reg);
    }
    $duration = microtime(true) - $start;
    
    expect($duration)->toBeLessThan(1.0); // < 1 second total
});
```

**Query Count Monitoring:**
```php
test('no N+1 queries in draw execution', function () {
    $registrations = Registration::factory()->count(100)->create();
    
    DB::enableQueryLog();
    app(DrawService::class)->executeDraw($trackId, 50);
    $queries = DB::getQueryLog();
    
    // Should be constant queries regardless of N
    expect(count($queries))->toBeLessThan(20);
});
```

**Dashboard Performance:**
```php
test('dashboard loads quickly with domain widgets', function () {
    $start = microtime(true);
    $response = $this->actingAs($admin)->get('/admin');
    $duration = microtime(true) - $start;
    
    expect($duration)->toBeLessThan(1.0); // < 1 second
});
```

**Memory Usage:**
```php
test('memory usage acceptable during draw', function () {
    $before = memory_get_usage();
    
    $registrations = Registration::factory()->count(1000)->create();
    app(DrawService::class)->executeDraw($trackId, 500);
    
    $after = memory_get_usage();
    $increase = ($after - $before) / 1024 / 1024; // MB
    
    expect($increase)->toBeLessThan(50); // < 50MB increase
});
```

**Implementation Notes:**
- Run on production-like data volumes
- Use Laravel Telescope for query monitoring
- Profile with Xdebug or Blackfire
- Compare before/after modularization
- Document any regressions

---

## Phase 5: Documentation & Cleanup 📋 PLANNED

**Goal:** Finalize documentation and clean up implementation  
**Estimated Time:** 2 hours  
**Dependencies:** Phase 4 complete

---

### Task 5.1: Update Main Documentation 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P1 (High)  
**Estimated Time:** 45 minutes

**Description:**
Update main project documentation to reflect modular architecture.

**Files:**
- `README.md` (UPDATE)
- `docs/rework/laravel-implementation-guide.md` (UPDATE)
- `docs/rework/system-architecture.md` (UPDATE)

**Acceptance Criteria:**
- [ ] README mentions modular domains
- [ ] Feature toggles documented in README
- [ ] Installation instructions updated
- [ ] Configuration guide updated
- [ ] Architecture diagrams updated
- [ ] Domain separation explained
- [ ] Links to domain READMEs added
- [ ] Contributing guide mentions domains

**README Updates:**
```markdown
## Features

### Core Features
- Registration management (individuals and teams)
- Email notifications with customizable templates
- Multi-language support

### Optional Modules
The following features can be enabled/disabled per installation:

- **Draw System** - Lottery-based selection for oversubscribed tracks
- **Starting Numbers** - Automatic number assignment with configurable ranges

See [Configuration](#configuration) for how to enable/disable modules.

## Configuration

### Feature Toggles

Enable or disable optional modules in `config/steppenreg.php`:

```php
'features' => [
    'draw' => true,              // Enable draw system
    'starting_numbers' => true,  // Enable starting numbers
],
```

Or via environment variables:

```env
STEPPENREG_DRAW_ENABLED=true
STEPPENREG_STARTING_NUMBERS_ENABLED=true
```

## Architecture

Steppenreg uses a modular domain-driven architecture:

- **Core**: Registration, Teams, Mail (always enabled)
- **Domains**: Optional, self-contained modules
  - [Draw](app/Domain/Draw/README.md) - Lottery system
  - [StartingNumber](app/Domain/StartingNumber/README.md) - Number assignment

Each domain includes:
- Business logic (Services, Events, Listeners)
- UI components (Filament Pages, Widgets)
- Configuration
- Tests
- Documentation
```

**Implementation Notes:**
- Keep README concise, link to detailed docs
- Update architecture diagrams
- Mention future modules (Waitlist)
- Update installation instructions

---

### Task 5.2: Create Migration Guide 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P2 (Medium)  
**Estimated Time:** 30 minutes

**Description:**
Create guide for migrating existing deployments to modular structure.

**File:** `docs/MIGRATION_GUIDE.md` (NEW)

**Acceptance Criteria:**
- [ ] Step-by-step migration instructions
- [ ] Rollback procedure documented
- [ ] Breaking changes listed (if any)
- [ ] Config changes documented
- [ ] Database migration steps
- [ ] Testing checklist included
- [ ] Troubleshooting section
- [ ] Timeline estimate provided

**Document Structure:**
```markdown
# Migration Guide: Modular Architecture

## Overview
This guide covers migrating from the monolithic structure to the modular domain architecture.

## Breaking Changes
- None (fully backward compatible)

## Migration Steps

### Step 1: Update Codebase
```bash
git pull origin main
composer install
npm install && npm run build
```

### Step 2: Publish Domain Configs
```bash
php artisan vendor:publish --tag=draw-config
php artisan vendor:publish --tag=starting-numbers-config
```

### Step 3: Publish Domain Migrations (Optional)
```bash
# Only needed for fresh installs without existing draws table
php artisan vendor:publish --tag=draw-migrations
php artisan migrate
```

### Step 4: Clear Caches
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 5: Verify Installation
```bash
php artisan test
```

Visit `/admin` and verify:
- [ ] ManageDraw page visible
- [ ] StartingNumber settings page visible
- [ ] Widgets display correctly
- [ ] Draw execution works
- [ ] Number assignment works

## Rollback Procedure

If issues arise:

```bash
git revert <commit-hash>
composer install
php artisan optimize:clear
```

## Configuration Migration

Existing configs remain valid. New domain configs are optional.

## Troubleshooting

### Issue: Duplicate navigation items
**Solution:** Clear route cache

### Issue: Widgets not displaying
**Solution:** Check feature toggles in config

### Issue: Tests failing
**Solution:** Update phpunit.xml

## Timeline
- Small deployments: 15-30 minutes
- Large deployments: 30-60 minutes
```

**Implementation Notes:**
- Clear, step-by-step instructions
- Anticipate common issues
- Provide rollback plan
- Test on staging first

---

### Task 5.3: Create Deployment Checklist 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P2 (Medium)  
**Estimated Time:** 15 minutes

**Description:**
Create checklist for deploying modular version to production.

**File:** `docs/DEPLOYMENT_CHECKLIST.md` (NEW)

**Acceptance Criteria:**
- [ ] Pre-deployment checks listed
- [ ] Deployment steps documented
- [ ] Post-deployment verification
- [ ] Rollback procedure
- [ ] Environment variable setup
- [ ] Config publishing steps
- [ ] Testing requirements

**Document Structure:**
```markdown
# Deployment Checklist: Modular Architecture

## Pre-Deployment

- [ ] Code reviewed and approved
- [ ] All tests passing locally
- [ ] Database backup created
- [ ] Staging environment tested
- [ ] Feature toggles configured
- [ ] Environment variables set
- [ ] Documentation reviewed

## Deployment Steps

### 1. Enable Maintenance Mode
```bash
php artisan down --message="Upgrading to modular architecture"
```

### 2. Pull Latest Code
```bash
git pull origin main
```

### 3. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
npm ci && npm run build
```

### 4. Publish Configs (if needed)
```bash
php artisan vendor:publish --tag=draw-config
php artisan vendor:publish --tag=starting-numbers-config
```

### 5. Run Migrations
```bash
php artisan migrate --force
```

### 6. Clear & Cache
```bash
php artisan optimize
```

### 7. Disable Maintenance Mode
```bash
php artisan up
```

## Post-Deployment Verification

- [ ] Application loads without errors
- [ ] ManageDraw page accessible
- [ ] StartingNumber settings accessible
- [ ] Dashboard widgets display
- [ ] Test draw execution (staging data)
- [ ] Test number assignment
- [ ] Check logs for errors
- [ ] Monitor performance
- [ ] Verify integrations work

## Environment Variables

Add to `.env`:

```env
STEPPENREG_DRAW_ENABLED=true
STEPPENREG_STARTING_NUMBERS_ENABLED=true
```

## Rollback Procedure

If critical issues:

```bash
# 1. Enable maintenance
php artisan down

# 2. Revert code
git revert <commit-hash>
composer install --optimize-autoloader --no-dev

# 3. Restore database (if needed)
# mysql < backup.sql

# 4. Clear caches
php artisan optimize:clear
php artisan optimize

# 5. Disable maintenance
php artisan up
```

## Monitoring

After deployment, monitor:
- Error logs (Sentry/Bugsnag)
- Performance metrics
- User feedback
- Database queries
- Memory usage

## Support

If issues arise:
- Check logs: `storage/logs/laravel.log`
- Run diagnostics: `php artisan about`
- Contact: [support email]
```

**Implementation Notes:**
- Clear, actionable checklist format
- Assume production deployment
- Include rollback plan
- Provide support contacts

---

### Task 5.4: Code Review & Cleanup 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P1 (High)  
**Estimated Time:** 1 hour

**Description:**
Final code review and cleanup before considering implementation complete.

**Acceptance Criteria:**
- [ ] All code follows Laravel conventions
- [ ] All code follows project style guide (Pint)
- [ ] No debug statements left (dd, dump, etc.)
- [ ] No commented-out code
- [ ] No TODO/FIXME without issues
- [ ] All imports optimized (no unused imports)
- [ ] No unused methods/classes
- [ ] Proper type hints everywhere
- [ ] PHPDoc blocks for public methods
- [ ] No hardcoded values (use config)
- [ ] No magic numbers/strings
- [ ] Error messages user-friendly
- [ ] Consistent naming conventions

**Tools:**
```bash
# Run Laravel Pint (code style)
./vendor/bin/pint

# Run PHPStan (static analysis)
./vendor/bin/phpstan analyse

# Run tests
php artisan test

# Check for TODOs
grep -r "TODO\|FIXME\|XXX\|HACK" app/Domain/

# Check for debug statements
grep -r "dd(\|dump(\|var_dump\|print_r" app/Domain/
```

**Review Checklist:**

**StartingNumber Domain:**
- [ ] StartingNumberPlugin.php clean
- [ ] ManageStartingNumbers.php clean
- [ ] StartingNumberStatsWidget.php clean
- [ ] StartingNumberService.php clean
- [ ] Config file clean
- [ ] Tests clean
- [ ] README accurate

**Draw Domain:**
- [ ] DrawPlugin.php clean
- [ ] DrawServiceProvider.php clean
- [ ] ManageDraw.php clean
- [ ] Widgets clean
- [ ] DrawService.php clean
- [ ] Config file clean
- [ ] Tests clean
- [ ] README accurate

**General:**
- [ ] No duplicate code
- [ ] DRY principle followed
- [ ] SOLID principles followed
- [ ] Consistent error handling
- [ ] Proper exception types
- [ ] Transaction usage correct
- [ ] Event dispatching correct

**Implementation Notes:**
- Run Pint automatically
- Address all PHPStan issues
- Remove all debug code
- Ensure consistency across domains

---

### Task 5.5: Final Testing & Sign-off 📋 NOT STARTED
**Status:** 📋 Not Started  
**Priority:** P0 (Blocker)  
**Estimated Time:** 1-2 hours

**Description:**
Final comprehensive testing before marking project complete.

**Test Scenarios:**

**Scenario 1: Fresh Installation**
```bash
# Clean slate
rm -rf vendor node_modules
rm database/database.sqlite
composer install
npm install && npm run build

# Setup
cp .env.example .env
php artisan key:generate
php artisan migrate

# Publish configs
php artisan vendor:publish --tag=draw-config
php artisan vendor:publish --tag=starting-numbers-config

# Test
php artisan test
```

**Scenario 2: Existing Deployment Upgrade**
```bash
# Simulate upgrade
git stash
git pull origin main
composer install
php artisan migrate
php artisan optimize

# Test
php artisan test
```

**Scenario 3: All Features Enabled**
- Set both features to true
- Execute draw
- Verify numbers assigned
- Check widgets display
- Test settings pages

**Scenario 4: Draw Only**
- Set draw=true, starting_numbers=false
- Execute draw
- Verify no numbers assigned
- Check only draw UI visible

**Scenario 5: StartingNumber Only**
- Set draw=false, starting_numbers=true
- Verify draw UI hidden
- Settings page still accessible
- Manual number assignment works (if applicable)

**Scenario 6: Both Disabled**
- Set both to false
- Verify all domain UI hidden
- Verify no errors
- Core features still work

**Scenario 7: Production-Like Data**
- 1000 registrations
- 50 teams
- Multiple tracks
- Execute draw
- Verify performance
- Check memory usage

**Scenario 8: Edge Cases**
- Draw with 0 registrations
- Draw with 0 available spots
- Number assignment with exhausted range
- Overflow bucket usage
- Reserved numbers handling

**Acceptance Criteria:**
- [ ] All test scenarios pass
- [ ] No errors in logs
- [ ] No performance degradation
- [ ] UI works correctly in all scenarios
- [ ] Feature toggles work correctly
- [ ] Config cache works
- [ ] Route cache works
- [ ] Migrations work (fresh and existing)
- [ ] Tests pass with 100% success
- [ ] Documentation complete and accurate
- [ ] Code review complete
- [ ] Human sign-off received
- [ ] Ready for production deployment

**Final Verification Commands:**
```bash
# Run full test suite
php artisan test --coverage

# Check code style
./vendor/bin/pint --test

# Static analysis
./vendor/bin/phpstan analyse

# Check for issues
php artisan about
php artisan route:list
php artisan config:show

# Performance check
php artisan optimize
```

**Sign-off:**
- **Technical Lead:** _________________ Date: _________
- **Product Owner:** _________________ Date: _________
- **QA/Testing:** _________________ Date: _________

**Implementation Notes:**
- Test thoroughly
- Document any issues found
- Fix before sign-off
- Get explicit approval

---

## Risk Management

### High Priority Risks

**Risk 1: Config Caching Issues**
- **Impact:** High - App may break in production
- **Probability:** Medium
- **Mitigation:** Thorough testing with config:cache enabled
- **Contingency:** Clear cache instructions in troubleshooting

**Risk 2: Migration Conflicts**
- **Impact:** High - Database issues in fresh installs
- **Probability:** Low
- **Mitigation:** Test fresh installs with domains disabled
- **Contingency:** Conditional migration publishing

**Risk 3: Event Coupling Problems**
- **Impact:** Medium - StartingNumber breaks if events fail
- **Probability:** Low (already well-designed)
- **Mitigation:** Graceful error handling in listeners
- **Contingency:** Feature toggle provides escape hatch

**Risk 4: Discovery Conflicts**
- **Impact:** Medium - Duplicate UI components
- **Probability:** Low
- **Mitigation:** Explicit plugin registration, avoid discovery
- **Contingency:** Exclude domain paths from global discovery

### Medium Priority Risks

**Risk 5: Performance Regression**
- **Impact:** Medium - Slower application
- **Probability:** Low (minimal overhead expected)
- **Mitigation:** Performance testing in Phase 4
- **Contingency:** Optimize plugin registration, cache more aggressively

**Risk 6: Test Failures**
- **Impact:** Medium - Unable to verify correctness
- **Probability:** Medium
- **Mitigation:** Comprehensive test writing
- **Contingency:** Fix tests before proceeding to next phase

### Low Priority Risks

**Risk 7: Documentation Drift**
- **Impact:** Low - Confusing for future developers
- **Probability:** Medium
- **Mitigation:** Update docs as part of implementation
- **Contingency:** Regular doc reviews

**Risk 8: Breaking Changes**
- **Impact:** High if occurs
- **Probability:** Very low (designed for backward compatibility)
- **Mitigation:** Maintain existing interfaces
- **Contingency:** Version bump if needed, migration guide

---

## Success Metrics

### Technical Metrics
- ✅ All tests passing (Unit + Feature + Integration)
- ✅ Code coverage > 80% for domain code
- ✅ No performance degradation (< 5% regression)
- ✅ Zero breaking changes to core functionality
- ✅ Clean separation of concerns (no cross-imports)
- ✅ Config cache works correctly
- ✅ Feature toggles work seamlessly

### User Experience Metrics
- ✅ UI components work correctly in all scenarios
- ✅ Configuration interface intuitive
- ✅ Feature toggles transparent to users
- ✅ No errors in production logs
- ✅ Response times acceptable
- ✅ Settings pages easy to use
- ✅ Widgets provide valuable insights

### Development Metrics
- ✅ Clear domain boundaries maintained
- ✅ Easy to add new domains
- ✅ Simple to enable/disable features
- ✅ Maintainable codebase
- ✅ Well-documented (code + user docs)
- ✅ Consistent code style
- ✅ Modular architecture understood by team

### Business Metrics
- ✅ Can deploy without specific features
- ✅ Reduces maintenance burden
- ✅ Enables feature experimentation
- ✅ Facilitates gradual rollout
- ✅ Supports future modularization

---

## Timeline & Milestones

### Week 1: Foundation & StartingNumber
- **Days 1-2:** Phase 1 (Foundation & Planning)
- **Days 3-5:** Phase 2 (StartingNumber Enhancement)
- **Milestone:** StartingNumber fully modular with UI

### Week 2: Draw & Integration
- **Days 6-8:** Phase 3 (Draw Modularization)
- **Days 9-10:** Phase 4 (Integration & Testing)
- **Milestone:** Both domains modular and tested

### Week 3: Documentation & Launch
- **Days 11-12:** Phase 5 (Documentation & Cleanup)
- **Day 13:** Final testing and sign-off
- **Milestone:** Production-ready modular architecture

**Total Time:** ~13-18 hours spread over 2-3 weeks

---

## Dependencies & Blockers

### External Dependencies
- None identified

### Internal Dependencies
- Phase 2 depends on Phase 1 completion
- Phase 3 depends on Phase 2 completion
- Phase 4 depends on Phases 2 & 3 completion
- Phase 5 depends on Phase 4 completion

### Current Blockers
- 🚧 **Task 1.2** - Human review and approval of PRD required before proceeding

---

## Communication Plan

### Status Updates
- Daily: Update PRD.md task statuses
- Weekly: Summary report to stakeholders
- Blockers: Immediate notification

### Review Points
- After each phase: Review with technical lead
- After Phase 4: QA review
- Before Phase 5: Product owner review

### Documentation
- PRD.md: Single source of truth
- Commit messages: Reference PRD task IDs
- PR descriptions: Link to relevant tasks

---

## Review & Approval Status

### Current Status: 🚧 AWAITING HUMAN REVIEW

**Next Action Required:**
👤 Human must review and approve this PRD before implementation begins.

**Review Checklist for Human:**
- [ ] Overall approach makes sense
- [ ] Phase breakdown appropriate
- [ ] Task breakdown sufficient
- [ ] Acceptance criteria clear and measurable
- [ ] StartingNumber UI design acceptable
- [ ] Draw modularization approach correct
- [ ] Configuration approach works
- [ ] Migration strategy acceptable
- [ ] Testing strategy sufficient
- [ ] Timeline realistic (13-18 hours)
- [ ] No critical items missing
- [ ] Risks identified and mitigated
- [ ] Success metrics appropriate

**Approval:**
- **Reviewed by:** _________________
- **Date:** _________________
- **Status:** [ ] ✅ Approved [ ] ✏️ Changes Requested
- **Comments:** _________________

**If Approved:**
Mark Task 1.2 acceptance criteria as complete and notify AI to proceed to Task 1.3.

**If Changes Requested:**
Edit this PRD.md file directly with changes, then notify AI to review updates.

---

## Appendix

### Glossary
- **Domain:** Self-contained module with specific business responsibility
- **Plugin:** Filament's mechanism for registering UI components to panels
- **Feature Toggle:** Configuration flag to enable/disable functionality
- **Core Features:** Registration, Teams, Mail (not modularized)
- **Service Provider:** Laravel class that registers services and bootstraps domains
- **Event-Driven:** Architecture where domains communicate via events, not direct calls

### Related Documents
- `/docs/rework/requirements-specification.md` - Original requirements
- `/docs/rework/system-architecture.md` - Architecture design
- `/docs/rework/implementation-specifications.md` - Implementation details
- `/docs/rework/laravel-implementation-guide.md` - Laravel structure guide
- `MODULARIZATION_RESEARCH.md` - Research on modularization approaches
- `MODULARIZATION_SUMMARY.md` - Quick reference guide
- `ARCHITECTURE_DIAGRAM.md` - Visual architecture diagrams
- `IMPLEMENTATION_EXAMPLES.md` - Code examples

### Version History
- v1.0.0 (2026-01-21) - Initial PRD created
  - Human decisions incorporated (Q1-Q5)
  - Coupling analysis completed
  - All phases and tasks defined
  - Ready for human review

---

**END OF PRD**

**Status:** 📋 Ready for Human Review  
**Next Step:** Human reviews and approves (Task 1.2)  
**After Approval:** AI proceeds to Task 1.3 (Codebase Analysis)

---

## PHASE 1 COMPLETE ✅

**Date:** January 21, 2026  
**Status:** All Phase 1 tasks completed successfully

### Completed Tasks:
- ✅ Task 1.1: PRD Document Created
- ✅ Task 1.2: Human Review and Approval
- ✅ Task 1.3: Codebase Analysis Complete

### Key Findings:
- Event-driven architecture already perfect
- No coupling issues found
- Draw domain needs Service Provider (critical)
- Test coverage minimal (needs work)
- Ready to proceed to Phase 2

### Analysis Document:
See `/docs/CODEBASE_ANALYSIS.md` for complete analysis.

**Next Phase:** Phase 2 - StartingNumber Domain Enhancement


---

## PHASE 2 COMPLETE ✅

**Date:** January 21, 2026  
**Status:** StartingNumber domain fully enhanced and modularized

### Completed Tasks:
- ✅ Task 2.1: Domain configuration file created
- ✅ Task 2.2: Filament plugin created
- ✅ Task 2.3: Settings Page with full UI
- ✅ Task 2.4: Statistics Widget with dashboard
- ✅ Task 2.5: Service Provider updated
- ✅ Task 2.6: Basic test suite created
- ✅ Task 2.7: Comprehensive README

### Deliverables:
1. `config/starting-numbers.php` - Published configuration
2. `StartingNumberPlugin.php` - Filament plugin integration
3. `ManageStartingNumbers.php` - Full-featured settings page
4. `StartingNumberStatsWidget.php` - Real-time statistics widget
5. Updated `StartingNumberServiceProvider.php`
6. Test suite foundation
7. Complete domain README

### Features Implemented:
- Per-track number range configuration
- Global overflow bucket (9001-9999)
- Number formatting (padding, prefix, suffix)
- Sequential/random assignment strategies
- Reserved numbers support
- Real-time preview in settings
- Range overlap validation
- Dashboard statistics with trends
- Feature toggle integration

**Next Phase:** Phase 3 - Draw Domain Modularization

