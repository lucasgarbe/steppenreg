# Steppenreg Modular Architecture Diagram

## Recommended Architecture: app/Domain/ with Filament Plugins

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Laravel Application                          │
│                         (Single Deployment)                          │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                ┌───────────────────┴───────────────────┐
                │                                       │
        ┌───────▼────────┐                     ┌───────▼────────┐
        │   app/ (Core)   │                     │  app/Domain/   │
        │                 │                     │   (Modules)    │
        └─────────────────┘                     └────────────────┘
                │                                       │
    ┌───────────┼───────────┐          ┌────────────┬──┴──┬────────────┐
    │           │           │          │            │     │            │
┌───▼───┐  ┌───▼───┐  ┌────▼────┐  ┌──▼───┐  ┌────▼──┐ ┌▼────┐  ┌────▼────┐
│Models │  │ Http  │  │Providers│  │ Draw │  │Start- │ │Teams│  │Waitlist │
│       │  │       │  │         │  │      │  │Number │ │     │  │(future) │
└───────┘  └───────┘  └─────────┘  └──────┘  └───────┘ └─────┘  └─────────┘
```

## Module Internal Structure

```
app/Domain/{ModuleName}/
│
├── {ModuleName}ServiceProvider.php ◄─── Registers with Laravel
│   │
│   ├── register(): Binds services
│   └── boot(): Registers events, migrations, config
│
├── {ModuleName}Plugin.php ◄───────────── Registers with Filament
│   │
│   └── register(Panel): Discovers UI components
│
├── Services/ ◄─────────────────────────── Business Logic Layer
│   └── {ModuleName}Service.php
│
├── Events/ ◄───────────────────────────── Domain Events
│   ├── SomethingHappened.php
│   └── SomethingElseOccurred.php
│
├── Listeners/ ◄────────────────────────── Event Handlers
│   └── HandleSomething.php
│
├── Exceptions/ ◄───────────────────────── Domain Exceptions
│   └── {ModuleName}Exception.php
│
├── Models/ ◄───────────────────────────── Domain Models (optional)
│   └── {DomainModel}.php
│
├── Filament/ ◄─────────────────────────── Admin UI Components
│   ├── Pages/
│   │   └── Manage{Feature}.php
│   ├── Resources/
│   │   └── {Resource}Resource.php
│   └── Widgets/
│       └── {Module}StatsWidget.php
│
├── config/ ◄───────────────────────────── Module Configuration
│   └── {module}.php
│
└── Tests/ ◄────────────────────────────── Module Tests
    ├── Feature/
    └── Unit/
```

## Registration Flow

```
1. Bootstrap
   │
   bootstrap/providers.php
   │
   ├─► AppServiceProvider::register()
   │   └─► Core services
   │
   └─► {Module}ServiceProvider::register()
       └─► Module services (singleton binding)
       
2. Boot Phase
   │
   {Module}ServiceProvider::boot()
   │
   ├─► Check feature flag: config('steppenreg.features.{module}')
   │   │
   │   └─► IF DISABLED: return early
   │
   ├─► Load migrations, config, views
   │
   ├─► Register event listeners
   │   │
   │   Event::listen(SomeEvent::class, SomeListener::class)
   │
   └─► Register Filament Plugin
       │
       Panel::configureUsing(fn($panel) => 
           $panel->plugin({Module}Plugin::make())
       )

3. Filament Panel Configuration
   │
   {Module}Plugin::register(Panel $panel)
   │
   └─► Discover Filament components
       │
       ├─► discoverPages()
       ├─► discoverResources()
       └─► discoverWidgets()
```

## Inter-Module Communication

```
┌──────────────┐         Event         ┌──────────────┐
│ Draw Module  │────────────────────────►│ Starting     │
│              │  RegistrationDrawn     │ Number       │
└──────────────┘                        │ Module       │
       │                                └──────────────┘
       │ Event                                 │
       │ RegistrationNotDrawn                  │
       ▼                                       │
┌──────────────┐                               │
│ Waitlist     │                               │
│ Module       │                               │
└──────────────┘                               │
       │                                       │
       │ Event                                 │
       │ WaitlistPromoted                      │
       │                                       │
       └───────────────────────────────────────┘

KEY: Modules communicate via Events (loose coupling)
     No direct service calls between modules
```

## Feature Toggle Flow

```
.env
│
STEPPENREG_DRAW_ENABLED=true
│
▼
config/steppenreg.php
│
'features' => [
    'draw' => env('STEPPENREG_DRAW_ENABLED', true),
]
│
▼
DrawServiceProvider::boot()
│
if (! config('steppenreg.features.draw', true)) {
    return; // ◄─── Short-circuit: No events, no UI
}
│
▼
┌─────────────────────────────────┐
│ Module Fully Activated          │
│ - Event listeners registered    │
│ - Filament UI available         │
│ - Services available in DI      │
└─────────────────────────────────┘
```

## Filament UI Integration

```
AdminPanelProvider
│
└─► panel(Panel $panel)
    │
    ├─► discoverPages(in: app_path('Filament/Pages')) ◄─── Core pages
    │
    └─► Plugins automatically register via Panel::configureUsing()
        │
        ├─► DrawPlugin::register()
        │   └─► discoverPages(in: Domain/Draw/Filament/Pages)
        │
        ├─► TeamsPlugin::register()
        │   └─► discoverResources(in: Domain/Teams/Filament/Resources)
        │
        └─► WaitlistPlugin::register()
            └─► discoverWidgets(in: Domain/Waitlist/Filament/Widgets)

Result: Filament automatically discovers all components
        - Core components from app/Filament/
        - Module components from app/Domain/{Module}/Filament/
```

## Directory Structure Comparison

### Current State
```
app/
├── Domain/
│   ├── StartingNumber/    ✓ Good structure
│   │   └── StartingNumberServiceProvider.php
│   └── Draw/              ✓ Good structure
│       ├── Filament/      ✓ UI in module
│       └── Services/
├── Filament/              ⚠ Mixed concerns
│   ├── Pages/
│   ├── Resources/
│   │   ├── Teams/         ← Should be in Domain/Teams
│   │   ├── MailTemplates/ ← Should be in Domain/MailTemplates
│   │   └── Registrations/ ← Core (can stay)
│   └── Widgets/
├── Models/                ✓ Core models
└── Services/              ⚠ Mixed (some should be in domains)
```

### Target State
```
app/
├── Domain/
│   ├── StartingNumber/
│   │   ├── StartingNumberServiceProvider.php
│   │   ├── StartingNumberPlugin.php          ← NEW
│   │   └── Services/
│   ├── Draw/
│   │   ├── DrawServiceProvider.php           ← ADD
│   │   ├── DrawPlugin.php                    ← NEW
│   │   ├── Services/
│   │   └── Filament/
│   ├── Teams/                                ← EXTRACTED
│   │   ├── TeamsServiceProvider.php          ← NEW
│   │   ├── TeamsPlugin.php                   ← NEW
│   │   ├── Models/
│   │   │   └── Team.php                      ← MOVED
│   │   └── Filament/
│   │       └── Resources/                    ← MOVED
│   ├── MailTemplates/                        ← EXTRACTED
│   │   ├── MailTemplatesServiceProvider.php  ← NEW
│   │   ├── MailTemplatesPlugin.php           ← NEW
│   │   ├── Services/                         ← MOVED
│   │   └── Filament/
│   │       └── Resources/                    ← MOVED
│   └── Waitlist/                             ← NEW MODULE
│       ├── WaitlistServiceProvider.php
│       ├── WaitlistPlugin.php
│       ├── Services/
│       └── Filament/
├── Filament/              ✓ Core only
│   ├── Pages/
│   │   └── Dashboard.php
│   ├── Resources/
│   │   └── Registrations/ ← Core resource
│   └── Widgets/
│       └── RegistrationStats.php
├── Models/                ✓ Core models
│   ├── Registration.php
│   └── User.php
└── Services/              ✓ Core services only
```

## Data Flow Example: Registration Draw

```
1. Admin triggers draw
   │
   ManageDraw Page (Draw Module UI)
   │
   ▼
2. Call DrawService
   │
   DrawService::executeDraw()
   │
   ├─► Create Draw record
   ├─► Update registrations
   │
   └─► Emit events
       │
       ├─► RegistrationDrawn (foreach drawn)
       │   │
       │   └─► StartingNumber Module listens
       │       │
       │       AssignStartingNumberOnDrawn::handle()
       │       │
       │       └─► StartingNumberService::assign()
       │
       └─► RegistrationNotDrawn (foreach not drawn)
           │
           └─► Waitlist Module listens (future)
               │
               AddToWaitlistOnNotDrawn::handle()
               │
               └─► WaitlistService::addToWaitlist()
```

## Testing Structure

```
tests/
├── Feature/
│   ├── DrawFeatureTest.php           ← Integration tests
│   └── RegistrationFlowTest.php
│
└── Unit/
    └── Services/
        └── MarkdownRendererTest.php

app/Domain/Draw/Tests/
├── Feature/
│   ├── DrawExecutionTest.php         ← Module feature tests
│   └── DrawNotificationTest.php
│
└── Unit/
    └── DrawServiceTest.php           ← Module unit tests

app/Domain/Waitlist/Tests/
├── Feature/
│   └── WaitlistPromotionTest.php
│
└── Unit/
    └── WaitlistServiceTest.php
```

## Configuration Hierarchy

```
config/
├── steppenreg.php          ← Master feature toggles
│   └── features: [
│         'draw' => true,
│         'waitlist' => true,
│       ]
│
├── draw.php                ← Module-specific config (published)
│   └── algorithm: 'random',
│       max_teams: 50
│
└── waitlist.php            ← Module-specific config (published)
    └── max_size: 100,
        auto_promote: false

Override via .env:
STEPPENREG_DRAW_ENABLED=false
DRAW_ALGORITHM=weighted
```

## Dependency Management

```
┌─────────────────────────────────────────────┐
│            Laravel Framework                 │
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │        Filament Admin Panel            │ │
│  └────────────────────────────────────────┘ │
│                                              │
│  ┌────────────────────────────────────────┐ │
│  │           Core Application             │ │
│  │  Models, Controllers, Services         │ │
│  └────────────────────────────────────────┘ │
│                  ▲                           │
│                  │ uses                      │
│  ┌───────────────┴──────────────────────┐  │
│  │         Domain Modules               │  │
│  │                                       │  │
│  │  ┌────────┐  ┌────────┐  ┌────────┐ │  │
│  │  │ Draw   │  │ Teams  │  │Waitlist│ │  │
│  │  └────────┘  └────────┘  └────────┘ │  │
│  │       │          │            │      │  │
│  │       └──────────┴────────────┘      │  │
│  │         Communicate via Events       │  │
│  └──────────────────────────────────────┘  │
└─────────────────────────────────────────────┘

KEY:
- Modules depend on Core (Registration model, etc.)
- Core does NOT depend on modules
- Modules communicate via events (loose coupling)
- Each module can be toggled independently
```

## Migration Phases Visual

```
CURRENT                    PHASE 1                    PHASE 2
---------                  ---------                  ---------

Domain/                    Domain/                    Domain/
├── StartingNumber/        ├── StartingNumber/        ├── StartingNumber/
│   └── ...SP              │   ├── ...SP              │   ├── ...SP
└── Draw/                  │   └── ...Plugin ◄─NEW    │   └── ...Plugin
    └── ...                └── Draw/                  ├── Draw/
                               ├── DrawSP ◄───NEW     │   ├── DrawSP
Filament/                      ├── DrawPlugin ◄NEW    │   └── DrawPlugin
├── Resources/                 └── ...                ├── Teams/ ◄────NEW
│   ├── Teams/                                        │   ├── TeamsSP
│   ├── MailTemplates/     Filament/                  │   ├── TeamsPlugin
│   └── ...                ├── Resources/             │   └── Models/Team
└── ...                    │   ├── Teams/             ├── MailTemplates/ ◄NEW
                           │   ├── MailTemplates/     │   ├── ...SP
                           │   └── ...                │   ├── ...Plugin
                           └── ...                    │   └── Services/
                                                      └── Waitlist/ ◄──NEW
                           Timeline: 1 week               └── ...
                           
                                                      Filament/
                                                      ├── Pages/
                                                      │   └── Dashboard
                                                      └── Resources/
                                                          └── Registrations/
                                                      
                                                      Timeline: 2-4 weeks

Legend:
SP = ServiceProvider
...Plugin = Filament Plugin
◄─NEW = Newly created
◄─ = Moved from another location
```

## Alternative: Composer Packages (NOT Recommended for Now)

```
app-modules/                      ◄─── Overkill for single deployment
├── draw/
│   ├── composer.json             ◄─── Extra maintenance
│   └── src/
└── waitlist/
    ├── composer.json
    └── src/

composer.json (root)
"repositories": [
    {"type": "path", "url": "app-modules/*"}
]
"require": {
    "steppenreg/draw": "@dev"     ◄─── Must run composer update
}

⚠ Only use if:
  - Building multi-project reusable packages
  - Need independent versioning
  - Publishing to Packagist
```

---

**Recommendation: Start with app/Domain/ pattern, migrate to composer packages only when genuinely needed for reuse.**
