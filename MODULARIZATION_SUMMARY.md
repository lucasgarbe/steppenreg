# Modularization Quick Reference

**TL;DR: Use `app/Domain/` with Filament Plugins**

## Why This Approach?

- Simple PSR-4 autoloading (no composer overhead)
- Fast development (instant code changes)
- Perfect for single-deployment apps
- Filament 4 recommended pattern
- Already partially implemented

## Module Structure Template

```
app/Domain/{ModuleName}/
├── {ModuleName}ServiceProvider.php
├── {ModuleName}Plugin.php
├── config/
├── Services/
├── Events/
├── Listeners/
├── Exceptions/
├── Models/ (if domain-specific)
├── Filament/
│   ├── Pages/
│   ├── Resources/
│   └── Widgets/
└── Tests/
```

## Quick Start: New Module

### 1. Create Directory Structure
```bash
mkdir -p app/Domain/Waitlist/{Services,Events,Listeners,Exceptions,Filament/{Pages,Resources,Widgets},Tests}
```

### 2. Create Service Provider
```php
<?php

namespace App\Domain\Waitlist;

use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class WaitlistServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Services\WaitlistService::class);
    }

    public function boot(): void
    {
        if (! config('steppenreg.features.waitlist', true)) {
            return;
        }

        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->plugin(WaitlistPlugin::make());
            }
        });
    }
}
```

### 3. Create Filament Plugin
```php
<?php

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
        $panel
            ->discoverPages(
                in: __DIR__ . '/Filament/Pages',
                for: 'App\\Domain\\Waitlist\\Filament\\Pages'
            )
            ->discoverResources(
                in: __DIR__ . '/Filament/Resources',
                for: 'App\\Domain\\Waitlist\\Filament\\Resources'
            )
            ->discoverWidgets(
                in: __DIR__ . '/Filament/Widgets',
                for: 'App\\Domain\\Waitlist\\Filament\\Widgets'
            );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
```

### 4. Register in bootstrap/providers.php
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

### 5. Add Feature Toggle
```php
// config/steppenreg.php
return [
    'features' => [
        'starting_numbers' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
        'draw' => env('STEPPENREG_DRAW_ENABLED', true),
        'waitlist' => env('STEPPENREG_WAITLIST_ENABLED', true), // NEW
    ],
];
```

## Module Checklist

Creating a new module:

- [ ] Create directory structure
- [ ] Create ServiceProvider
- [ ] Create Plugin (if has Filament UI)
- [ ] Add to bootstrap/providers.php
- [ ] Add feature toggle to config/steppenreg.php
- [ ] Create service classes
- [ ] Add events/listeners if needed
- [ ] Create Filament components
- [ ] Write tests
- [ ] Add README.md to module directory

## Common Patterns

### Service Registration
```php
// ServiceProvider::register()
$this->app->singleton(Services\MyService::class);
```

### Event Listeners
```php
// ServiceProvider::boot()
Event::listen(
    SomeEvent::class,
    SomeListener::class
);
```

### Conditional Registration
```php
// ServiceProvider::boot()
if (! config('steppenreg.features.my_module', true)) {
    return;
}
```

### Inter-Module Communication
```php
// Use events, not direct calls
event(new RegistrationDrawn($registration));

// Listen in dependent module
Event::listen(RegistrationDrawn::class, AssignStartingNumber::class);
```

## Testing

```bash
# All tests
./vendor/bin/sail artisan test

# Module tests
./vendor/bin/sail artisan test app/Domain/Waitlist/Tests

# Single test
./vendor/bin/sail artisan test --filter=WaitlistTest
```

## Migration Priority

1. **Phase 1:** Add plugins to existing domains (Draw, StartingNumber)
2. **Phase 2:** Extract Teams domain
3. **Phase 3:** Extract MailTemplates domain
4. **Phase 4:** Identify additional domains

## Core vs Domain Decision

**Keep in app/ (Core):**
- Registration model (central to app)
- User model
- Frontend controllers
- Core observers
- Application bootstrap

**Move to app/Domain/ (Feature):**
- Optional functionality
- Has feature toggle
- Self-contained business logic
- Clear domain boundary

## When NOT to Modularize

- Simple CRUD with no business logic
- Code used everywhere (utilities)
- Less than 3 related classes
- No clear domain boundary

## Need Help?

See full research: `MODULARIZATION_RESEARCH.md`

Key sections:
- Section 6: Complete module example
- Section 7: Migration path
- Section 8: Trade-offs analysis
