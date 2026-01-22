# Laravel 12 + Filament 4 Modularization Research Report

**Project:** Steppenreg - Cycling Event Registration System
**Stack:** Laravel 12.46, Filament 4.5.2, PHP 8.4, PostgreSQL
**Date:** January 21, 2026

---

## Executive Summary

**RECOMMENDED APPROACH: app/Domain/ with Filament Plugin Pattern**

This modular architecture uses domain-based modules within `app/Domain/` with Filament plugins for UI integration. It's the most idiomatic approach for Laravel 12 + Filament 4 single-tenant applications.

**Key Benefits:**
- Native Laravel autoloading (PSR-4 `App\` namespace)
- Zero composer complexity for single-deployment apps
- Filament's plugin system for clean UI integration
- Simple feature toggles via config
- Easy team collaboration with clear boundaries
- Gradual migration path from existing structure

---

## 1. Modularization Approaches Comparison

### Approach A: app/Domain/ Pattern (RECOMMENDED)

**Structure:**
```
app/
└── Domain/
    └── StartingNumber/
        ├── StartingNumberServiceProvider.php
        ├── StartingNumberPlugin.php (NEW)
        ├── Services/
        ├── Events/
        ├── Listeners/
        ├── Exceptions/
        ├── Filament/
        │   ├── Pages/
        │   ├── Widgets/
        │   └── Resources/
        └── Models/ (if domain-specific)
```

**Pros:**
- Simple PSR-4 autoloading (already configured)
- No composer overhead or path repositories
- Fast development cycle (no composer dump-autoload)
- Perfect for single-deployment applications
- Native IDE support without special configuration
- Easy debugging and code navigation

**Cons:**
- Not reusable across projects without extraction
- Modules are not independently versionable
- Cannot use in other apps without copying code

**When to Use:**
- Single-tenant deployments
- Domain complexity warrants separation
- Team boundaries align with domains
- No requirement for cross-project reuse

---

### Approach B: app-modules/ with Composer Packages

**Structure:**
```
app-modules/
└── starting-number/
    ├── composer.json
    ├── src/
    │   ├── StartingNumberServiceProvider.php
    │   ├── StartingNumberPlugin.php
    │   ├── Services/
    │   └── Filament/
    └── config/
        └── starting-number.php
```

**composer.json root:**
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "app-modules/*"
        }
    ],
    "require": {
        "steppenreg/starting-number": "@dev"
    }
}
```

**Pros:**
- True package isolation with own dependencies
- Reusable across projects
- Independent versioning
- Can publish to Packagist
- Clear dependency boundaries

**Cons:**
- Requires `composer update` after changes
- More complex initial setup
- Path repository management overhead
- Slower development iteration
- Extra composer.json maintenance per module
- Overkill for single-deployment apps

**When to Use:**
- Building reusable packages for multiple projects
- Need independent versioning
- Want to publish to Packagist
- Multi-tenant SaaS with per-customer modules

---

### Approach C: packages/ Directory Pattern

**Structure:**
```
packages/
└── starting-number/
    ├── composer.json
    ├── src/
    └── config/
```

**Analysis:** Same as Approach B, just different directory name. No functional difference. The `app-modules/` name is more descriptive for internal modules.

---

## 2. Recommended Approach: app/Domain/ with Filament Plugins

### Why This Is Most Idiomatic for Your Use Case

1. **Laravel 12 Alignment:** Uses standard PSR-4 autoloading without composer complexity
2. **Filament 4 Best Practice:** Official docs recommend plugin pattern for modular UI
3. **Single Deployment:** No need for package-level isolation
4. **Development Velocity:** Instant code changes without composer overhead
5. **Team Collaboration:** Clear domain boundaries with simple structure
6. **Current State:** Already partially implemented (StartingNumber, Draw)

---

## 3. Filament Integration: Plugin Pattern

### Why Filament Plugins Over Direct Registration

**Direct Registration (Current):**
```php
// AdminPanelProvider.php
->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
```

**Problems:**
- All Filament components always loaded
- No module-level feature toggles
- Tight coupling to single panel
- Cannot configure per-module

**Plugin Pattern (Recommended):**
```php
// App\Domain\Draw\DrawPlugin.php
class DrawPlugin implements Plugin
{
    public function register(Panel $panel): void
    {
        $panel
            ->discoverPages(
                in: __DIR__ . '/Filament/Pages',
                for: 'App\Domain\Draw\Filament\Pages'
            )
            ->discoverWidgets(
                in: __DIR__ . '/Filament/Widgets',
                for: 'App\Domain\Draw\Filament\Widgets'
            );
    }
}
```

**Benefits:**
- Conditional registration per module
- Self-contained Filament integration
- Easy to enable/disable entire UI
- Module controls its own discovery
- Multi-panel support (admin, staff, etc.)

---

## 4. Panel Discovery Patterns

### Pattern 1: Panel::configureUsing() (RECOMMENDED)

**Location:** Module ServiceProvider's `boot()` method

```php
// App\Domain\Draw\DrawServiceProvider.php
use Filament\Panel;

public function boot(): void
{
    if (! config('steppenreg.features.draw', true)) {
        return;
    }

    // Register plugin for all panels (or conditionally)
    Panel::configureUsing(function (Panel $panel): void {
        if ($panel->getId() === 'admin') {
            $panel->plugin(DrawPlugin::make());
        }
    });
}
```

**Benefits:**
- No need to modify AdminPanelProvider
- Module is self-registering
- Easy to add/remove modules
- Respects feature flags

---

### Pattern 2: Explicit Plugin Registration

**Location:** AdminPanelProvider

```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ... existing config
        ->plugin(DrawPlugin::make())
        ->plugin(StartingNumberPlugin::make());
}
```

**Benefits:**
- Explicit and visible
- Central control over modules
- Easy to see all registered modules

**Tradeoffs:**
- Requires editing provider when adding modules
- Feature flags must be checked in plugin

---

### Recommendation: Use Panel::configureUsing()

For a modular architecture, self-registering modules reduce coupling and make modules truly independent.

---

## 5. Configuration and Toggleability Patterns

### Current Pattern (Good Foundation)

```php
// config/steppenreg.php
return [
    'features' => [
        'starting_numbers' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
    ],
];
```

### Enhanced Module Configuration Pattern

```php
// config/steppenreg.php
return [
    'features' => [
        'starting_numbers' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
        'draw' => env('STEPPENREG_DRAW_ENABLED', true),
        'waitlist' => env('STEPPENREG_WAITLIST_ENABLED', true),
    ],
];
```

### Per-Module Config Files

```php
// config/starting-numbers.php (published by module)
return [
    'enabled' => env('STARTING_NUMBERS_ENABLED', true),
    'start_from' => env('STARTING_NUMBERS_START', 1),
    'padding' => env('STARTING_NUMBERS_PADDING', 3), // 001, 002
    'reserved_numbers' => [],
];
```

### Feature Toggle Implementation Levels

**Level 1: Service Provider Registration (Current)**
```php
// StartingNumberServiceProvider::boot()
if (! config('steppenreg.features.starting_numbers', true)) {
    return;
}
```

**Level 2: Event Listener Registration**
```php
Event::listen(
    RegistrationDrawn::class,
    AssignStartingNumberOnDrawn::class
);
```

**Level 3: Filament UI Registration**
```php
Panel::configureUsing(function (Panel $panel): void {
    if (config('steppenreg.features.starting_numbers')) {
        $panel->plugin(StartingNumberPlugin::make());
    }
});
```

### Recommendation: Three-Level Toggle

1. **Config flag** - Master switch
2. **Service provider** - Checks flag, registers domain logic
3. **Filament plugin** - Checks flag, registers UI components

---

## 6. Concrete Module Structure Example

### Complete Module: Waitlist Domain

```
app/Domain/Waitlist/
├── WaitlistServiceProvider.php
├── WaitlistPlugin.php
├── config/
│   └── waitlist.php
├── database/
│   └── migrations/
│       └── 2026_01_21_create_waitlist_tables.php
├── Events/
│   ├── RegistrationAddedToWaitlist.php
│   └── WaitlistPromoted.php
├── Exceptions/
│   └── WaitlistFullException.php
├── Filament/
│   ├── Pages/
│   │   └── ManageWaitlist.php
│   ├── Resources/
│   │   └── WaitlistResource.php
│   └── Widgets/
│       └── WaitlistStatsWidget.php
├── Listeners/
│   └── AddToWaitlistOnNotDrawn.php
├── Models/
│   └── WaitlistEntry.php
├── Services/
│   └── WaitlistService.php
└── Tests/
    ├── Feature/
    │   └── WaitlistPromotionTest.php
    └── Unit/
        └── WaitlistServiceTest.php
```

---

### File: WaitlistServiceProvider.php

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist;

use App\Domain\Draw\Events\RegistrationNotDrawn;
use App\Domain\Waitlist\Listeners\AddToWaitlistOnNotDrawn;
use Filament\Panel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WaitlistServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module services
        $this->app->singleton(Services\WaitlistService::class);

        // Merge module config
        $this->mergeConfigFrom(
            __DIR__ . '/config/waitlist.php',
            'waitlist'
        );
    }

    public function boot(): void
    {
        // Check if feature is enabled
        if (! config('steppenreg.features.waitlist', true)) {
            return;
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Publish config
        $this->publishes([
            __DIR__ . '/config/waitlist.php' => config_path('waitlist.php'),
        ], 'waitlist-config');

        // Register event listeners
        Event::listen(
            RegistrationNotDrawn::class,
            AddToWaitlistOnNotDrawn::class
        );

        // Register Filament plugin (self-registering pattern)
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->plugin(WaitlistPlugin::make());
            }
        });
    }
}
```

---

### File: WaitlistPlugin.php

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist;

use Filament\Contracts\Plugin;
use Filament\Panel;

class WaitlistPlugin implements Plugin
{
    public function getId(): string
    {
        return 'waitlist';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        // Discover Filament resources in this module
        $panel
            ->discoverPages(
                in: __DIR__ . '/Filament/Pages',
                for: 'App\\Domain\\Waitlist\\Filament\\Pages'
            )
            ->discoverWidgets(
                in: __DIR__ . '/Filament/Widgets',
                for: 'App\\Domain\\Waitlist\\Filament\\Widgets'
            )
            ->discoverResources(
                in: __DIR__ . '/Filament/Resources',
                for: 'App\\Domain\\Waitlist\\Filament\\Resources'
            );
    }

    public function boot(Panel $panel): void
    {
        // Register any additional panel-specific logic
        // Example: Custom middleware, navigation groups, etc.
    }
}
```

---

### File: Services/WaitlistService.php

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist\Services;

use App\Domain\Waitlist\Events\RegistrationAddedToWaitlist;
use App\Domain\Waitlist\Events\WaitlistPromoted;
use App\Domain\Waitlist\Exceptions\WaitlistFullException;
use App\Domain\Waitlist\Models\WaitlistEntry;
use App\Models\Registration;
use Illuminate\Support\Collection;

class WaitlistService
{
    public function __construct(
        private int $maxWaitlistSize = 100
    ) {}

    public function addToWaitlist(Registration $registration): WaitlistEntry
    {
        $currentSize = WaitlistEntry::where('track_id', $registration->track_id)
            ->whereNull('promoted_at')
            ->count();

        if ($currentSize >= $this->maxWaitlistSize) {
            throw new WaitlistFullException(
                "Waitlist for track {$registration->track_id} is full"
            );
        }

        $entry = WaitlistEntry::create([
            'registration_id' => $registration->id,
            'track_id' => $registration->track_id,
            'added_at' => now(),
            'position' => $currentSize + 1,
        ]);

        event(new RegistrationAddedToWaitlist($entry));

        return $entry;
    }

    public function promoteFromWaitlist(int $trackId, int $count = 1): Collection
    {
        $entries = WaitlistEntry::where('track_id', $trackId)
            ->whereNull('promoted_at')
            ->orderBy('position')
            ->limit($count)
            ->get();

        $promoted = collect();

        foreach ($entries as $entry) {
            $entry->update([
                'promoted_at' => now(),
            ]);

            $entry->registration->update([
                'draw_status' => 'drawn',
                'drawn_at' => now(),
            ]);

            event(new WaitlistPromoted($entry));
            $promoted->push($entry);
        }

        return $promoted;
    }

    public function getWaitlistPosition(Registration $registration): ?int
    {
        return WaitlistEntry::where('registration_id', $registration->id)
            ->whereNull('promoted_at')
            ->value('position');
    }
}
```

---

### File: Filament/Pages/ManageWaitlist.php

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist\Filament\Pages;

use App\Domain\Waitlist\Services\WaitlistService;
use App\Settings\EventSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ManageWaitlist extends Page
{
    protected static string $view = 'domain.waitlist.pages.manage-waitlist';

    protected static ?string $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationGroup = 'Registration';

    protected static ?string $navigationLabel = 'Waitlist';

    protected static ?int $navigationSort = 40;

    public function promoteAction(): Action
    {
        return Action::make('promote')
            ->form([
                Select::make('track_id')
                    ->label('Track')
                    ->options(function () {
                        $tracks = app(EventSettings::class)->tracks ?? [];
                        $options = [];
                        foreach ($tracks as $track) {
                            $options[$track['id']] = $track['name'];
                        }
                        return $options;
                    })
                    ->required(),
                TextInput::make('count')
                    ->label('Number to Promote')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),
            ])
            ->action(function (array $data, WaitlistService $service) {
                $promoted = $service->promoteFromWaitlist(
                    $data['track_id'],
                    $data['count']
                );

                Notification::make()
                    ->title('Promoted from Waitlist')
                    ->body("Promoted {$promoted->count()} registrations")
                    ->success()
                    ->send();
            });
    }
}
```

---

### File: config/waitlist.php

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Waitlist Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('WAITLIST_ENABLED', true),

    'max_size' => env('WAITLIST_MAX_SIZE', 100),

    'auto_promote' => env('WAITLIST_AUTO_PROMOTE', false),

    'notification' => [
        'added' => true,
        'promoted' => true,
        'position_change' => false,
    ],
];
```

---

### Registration in bootstrap/providers.php

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,

    // Domain Service Providers
    App\Domain\StartingNumber\StartingNumberServiceProvider::class,
    App\Domain\Draw\DrawServiceProvider::class,
    App\Domain\Waitlist\WaitlistServiceProvider::class, // NEW
];
```

---

### Enhanced config/steppenreg.php

```php
<?php

return [
    'features' => [
        'starting_numbers' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
        'draw' => env('STEPPENREG_DRAW_ENABLED', true),
        'waitlist' => env('STEPPENREG_WAITLIST_ENABLED', true), // NEW
    ],
];
```

---

## 7. Migration Path from Current Structure

### Current State Assessment

**Already Modularized:**
- `app/Domain/StartingNumber/` - Service provider with feature toggle
- `app/Domain/Draw/` - Includes Filament components but no plugin

**Needs Modularization:**
- `app/Filament/` - Shared Filament components (3654 lines)
- `app/Models/` - Core models (Registration, Team, etc.)
- `app/Services/` - Application services
- `app/Mail/` - Email functionality
- `app/Http/Controllers/` - Frontend controllers

---

### Migration Strategy: Incremental Extraction

#### Phase 1: Create Filament Plugins for Existing Domains (Week 1)

**StartingNumber:**
1. Create `StartingNumberPlugin.php`
2. Update `StartingNumberServiceProvider` to register plugin
3. No Filament UI to move (backend-only module)

**Draw:**
1. Create `DrawPlugin.php`
2. Move registration logic from `AdminPanelProvider` (if any)
3. Already has Filament components in correct location

**Tasks:**
```bash
# Create plugin files
touch app/Domain/StartingNumber/StartingNumberPlugin.php
touch app/Domain/Draw/DrawPlugin.php

# Update service providers
# (Manual edit as shown in examples above)

# Test that Filament UI still works
./vendor/bin/sail artisan serve
```

---

#### Phase 2: Extract Clear Domains from app/ (Weeks 2-4)

**Candidate Modules:**

1. **Teams Domain**
   - Extract from: `app/Filament/Resources/Teams/`
   - Extract from: `app/Models/Team.php`
   - New location: `app/Domain/Teams/`
   - Complexity: Medium (existing Filament resources)

2. **MailTemplates Domain**
   - Extract from: `app/Filament/Resources/MailTemplates/`
   - Extract from: `app/Services/MailTemplateService.php`
   - Extract from: `app/Services/MailVariableResolver.php`
   - New location: `app/Domain/MailTemplates/`
   - Complexity: Medium (services + Filament)

3. **Registrations Domain** (Core - Later Phase)
   - Keep in: `app/Models/Registration.php` (Core model)
   - Extract: Advanced registration logic if needed
   - Complexity: High (central to application)

**Example: Teams Module Structure**
```
app/Domain/Teams/
├── TeamsServiceProvider.php
├── TeamsPlugin.php
├── Models/
│   └── Team.php (moved from app/Models/)
├── Filament/
│   ├── Resources/
│   │   └── TeamResource.php (moved from app/Filament/Resources/Teams/)
│   ├── Schemas/
│   │   └── TeamForm.php
│   └── Tables/
│       └── TeamsTable.php
└── Services/
    └── TeamService.php (if needed)
```

---

#### Phase 3: Define Core vs Domain Boundaries (Week 5)

**Core (Stays in app/):**
- `app/Models/Registration.php` - Central model
- `app/Models/User.php` - Authentication
- `app/Http/Controllers/` - Public frontend
- `app/Observers/RegistrationObserver.php` - Core behavior
- `app/Providers/AppServiceProvider.php` - Application bootstrap

**Domain (Move to app/Domain/):**
- Feature-specific logic
- Optional functionality with feature flags
- Self-contained business capabilities
- Filament admin UI components

---

#### Phase 4: Refactor Shared Filament Components (Week 6)

**Dashboard and Widgets:**

Option A: Keep in Core (if truly shared)
```
app/Filament/
├── Pages/
│   └── Dashboard.php (aggregates domain widgets)
└── Widgets/
    └── RegistrationStats.php (core metrics)
```

Option B: Move to Domain (if domain-specific)
```
app/Domain/Analytics/
└── Filament/
    ├── Pages/
    │   └── Dashboard.php
    └── Widgets/
        └── RegistrationStats.php
```

**Recommendation:** Start with Option A, refactor to Option B only if analytics becomes a standalone domain.

---

### Step-by-Step Migration Checklist

**For Each Module:**

- [ ] Create module directory structure
- [ ] Create ServiceProvider with `register()` and `boot()`
- [ ] Create Filament Plugin (if has UI)
- [ ] Move Services classes
- [ ] Move Events and Listeners
- [ ] Move Exceptions
- [ ] Move Models (if domain-specific)
- [ ] Move Filament components (Pages, Resources, Widgets)
- [ ] Move database migrations (optional, can stay in database/)
- [ ] Create module config file
- [ ] Add feature toggle to config/steppenreg.php
- [ ] Register ServiceProvider in bootstrap/providers.php
- [ ] Update imports in dependent code
- [ ] Run tests: `./vendor/bin/sail artisan test`
- [ ] Update documentation

---

## 8. Trade-offs Analysis

### app/Domain/ Pattern

**Pros:**
- ✓ Simple autoloading (PSR-4 App\ namespace)
- ✓ Fast development (no composer update)
- ✓ Native IDE support
- ✓ Easy debugging
- ✓ Single-deployment optimized
- ✓ Gradual migration path
- ✓ Team-friendly structure

**Cons:**
- ✗ Not reusable without extraction
- ✗ No independent versioning
- ✗ All modules share dependencies
- ✗ Cannot publish to Packagist

**Risk Mitigation:**
- If future reuse needed, extract to composer package
- Use clear module boundaries now for easier extraction later
- Document module dependencies explicitly

---

### app-modules/ Composer Packages

**Pros:**
- ✓ True package isolation
- ✓ Reusable across projects
- ✓ Independent versioning
- ✓ Publishable to Packagist
- ✓ Clear dependency boundaries

**Cons:**
- ✗ Requires composer update after changes
- ✗ Complex initial setup
- ✗ Slower development iteration
- ✗ Path repository management
- ✗ Multiple composer.json files
- ✗ Overkill for single app

**Risk Mitigation:**
- Only use for genuinely reusable components
- Consider after app/Domain/ proves insufficient

---

### Hybrid Approach

**Strategy:** Start with app/Domain/, extract to packages when needed

```
app/Domain/          (internal modules)
└── Draw/
app-modules/         (reusable packages)
└── payment-gateway/ (used in multiple projects)
```

**When to Extract:**
- Module needed in second project
- External team wants to use
- Open-source contribution opportunity
- Independent release cycle required

---

## 9. Testing Strategy for Modular Architecture

### Module-Specific Tests

```
app/Domain/Waitlist/
└── Tests/
    ├── Feature/
    │   ├── WaitlistPromotionTest.php
    │   └── WaitlistNotificationTest.php
    └── Unit/
        ├── WaitlistServiceTest.php
        └── WaitlistEntryTest.php
```

### PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<testsuites>
    <testsuite name="Domain: Waitlist">
        <directory>app/Domain/Waitlist/Tests</directory>
    </testsuite>
    <testsuite name="Domain: Draw">
        <directory>app/Domain/Draw/Tests</directory>
    </testsuite>
</testsuites>
```

### Run Tests Per Module

```bash
# All tests
./vendor/bin/sail artisan test

# Single module
./vendor/bin/sail artisan test --testsuite="Domain: Waitlist"

# Specific test
./vendor/bin/sail artisan test --filter=WaitlistPromotionTest
```

---

## 10. Documentation and Conventions

### Module README Pattern

```markdown
# Waitlist Domain

Event registration waitlist management system.

## Features

- Automatic waitlist placement for non-drawn registrations
- Position tracking
- Promotion workflow
- Email notifications

## Configuration

config/waitlist.php

## Toggle Feature

.env
STEPPENREG_WAITLIST_ENABLED=true

## Services

- WaitlistService: Core business logic
- WaitlistNotificationService: Email handling

## Events

- RegistrationAddedToWaitlist
- WaitlistPromoted

## Filament

- Pages: ManageWaitlist
- Widgets: WaitlistStatsWidget

## Database

- waitlist_entries table

## Dependencies

- Draw domain (listens to RegistrationNotDrawn event)
```

---

### Naming Conventions

**Directories:**
- PascalCase: `app/Domain/StartingNumber/`
- Plural for collections: `Services/`, `Events/`, `Listeners/`

**Files:**
- PascalCase classes: `WaitlistService.php`
- Suffixes: `ServiceProvider`, `Plugin`, `Service`, `Exception`

**Namespaces:**
```php
App\Domain\{ModuleName}\{Layer}\{ClassName}
```

**Examples:**
```
App\Domain\Waitlist\Services\WaitlistService
App\Domain\Waitlist\Filament\Pages\ManageWaitlist
App\Domain\Waitlist\Events\WaitlistPromoted
```

---

## 11. Architecture Decision Records (ADRs)

### ADR-001: Choose app/Domain/ Over app-modules/

**Status:** Accepted

**Context:**
- Single-deployment application
- No multi-tenant requirements
- No cross-project reuse needed currently

**Decision:**
Use `app/Domain/` pattern with PSR-4 autoloading

**Consequences:**
- Fast development cycle
- Simple autoloading
- Cannot reuse without extraction
- Easy to refactor to packages later if needed

---

### ADR-002: Use Filament Plugin Pattern for UI

**Status:** Accepted

**Context:**
- Filament 4 recommends plugin pattern
- Need conditional UI registration
- Want module self-contained

**Decision:**
Each domain module with UI creates Filament Plugin

**Consequences:**
- Clean separation of domain logic and UI
- Feature toggles work at UI level
- Modules self-register with panels
- Easy to test without UI

---

### ADR-003: Self-Registering Modules with Panel::configureUsing()

**Status:** Accepted

**Context:**
- Want modules to be truly independent
- Reduce coupling to AdminPanelProvider
- Easy to add/remove modules

**Decision:**
Modules use `Panel::configureUsing()` in ServiceProvider

**Consequences:**
- No need to edit AdminPanelProvider per module
- Modules control own registration
- Slightly less explicit than direct registration
- Feature flags checked at module level

---

## 12. Future Considerations

### When to Consider app-modules/ Packages

**Triggers:**
1. Second project needs same module
2. External teams want to use module
3. Open-source plan for module
4. Need independent release cycles

**Migration Path:**
1. Copy `app/Domain/{Module}/` to `app-modules/{module}/src/`
2. Create `composer.json` for module
3. Add path repository to root composer.json
4. Update namespaces from `App\Domain\` to `Vendor\Module\`
5. Require module in root composer.json
6. Remove old app/Domain/ directory
7. Update imports project-wide

---

### Multi-Panel Support

**Current:** Single admin panel

**Future:** Add staff/customer panels

```php
// Module ServiceProvider
Panel::configureUsing(function (Panel $panel): void {
    match ($panel->getId()) {
        'admin' => $panel->plugin(
            WaitlistPlugin::make()->canPromote()
        ),
        'staff' => $panel->plugin(
            WaitlistPlugin::make()->viewOnly()
        ),
        default => null,
    };
});
```

---

### Event-Driven Architecture

**Current:** Domain events between modules

**Future:** Event bus for better decoupling

```php
// Instead of direct event listening
Event::listen(RegistrationNotDrawn::class, AddToWaitlistOnNotDrawn::class);

// Use event bus pattern
EventBus::subscribe('draw.registration.not_drawn', [
    AddToWaitlistOnNotDrawn::class,
    SendNotDrawnEmail::class,
]);
```

---

## 13. Recommended Next Steps

### Immediate (This Week)

1. **Create plugins for existing domains**
   - [ ] Create `StartingNumberPlugin.php`
   - [ ] Create `DrawPlugin.php`
   - [ ] Update service providers to use `Panel::configureUsing()`
   - [ ] Test Filament UI still works

2. **Enhance config/steppenreg.php**
   - [ ] Add `draw` feature flag
   - [ ] Document feature toggle pattern
   - [ ] Add example .env variables

3. **Create module documentation template**
   - [ ] Add README.md to existing domains
   - [ ] Document dependencies between modules

---

### Short-Term (Next 2-4 Weeks)

1. **Extract Teams domain**
   - Clear boundary
   - Self-contained functionality
   - Good practice module

2. **Extract MailTemplates domain**
   - Services + Filament UI
   - Good example of full module

3. **Write migration guide**
   - Document process for team
   - Create checklist
   - Add to AGENTS.md

---

### Long-Term (Next Quarter)

1. **Evaluate additional domains**
   - Analytics/Reporting
   - Payments (if added)
   - Export functionality

2. **Consider event bus pattern**
   - If inter-module events grow complex
   - Better than direct event dependencies

3. **Review for package extraction**
   - Any module needed elsewhere?
   - Open-source opportunities?

---

## 14. References

### Official Documentation

- [Filament 4 Modular Architecture](https://filamentphp.com/docs/4.x/advanced/modular-architecture)
- [Laravel 12 Package Development](https://laravel.com/docs/12.x/packages)
- [Laravel 12 Service Providers](https://laravel.com/docs/12.x/providers)
- [Filament 4 Plugins](https://filamentphp.com/docs/4.x/plugins/getting-started)

### Community Resources

- [Laravel Modules Package](https://github.com/nWidart/laravel-modules) - Alternative approach
- [Orchestral Testbench](https://github.com/orchestral/testbench) - For package testing

### Example Projects

- Filament Plugins Directory: https://filamentphp.com/plugins
- Look for official plugins source code for patterns

---

## 15. Conclusion

**For Steppenreg, the `app/Domain/` pattern with Filament Plugins is the optimal choice.**

**Key Takeaways:**
1. Use `app/Domain/{ModuleName}/` structure
2. Create Filament Plugin for each module with UI
3. Use `Panel::configureUsing()` for self-registering modules
4. Implement feature toggles in config/steppenreg.php
5. Start with existing domains (Draw, StartingNumber)
6. Incrementally extract new domains (Teams, MailTemplates)
7. Keep core models and services in `app/`
8. Only move to composer packages if reuse needed

**This approach provides:**
- Clean architecture with clear boundaries
- Fast development velocity
- Easy feature toggles
- Team collaboration support
- Simple testing strategy
- Gradual migration path
- Future flexibility

**The modular architecture will support Steppenreg's growth while maintaining Laravel 12 and Filament 4 best practices.**

---

*End of Report*
