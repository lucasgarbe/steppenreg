# Modularization Implementation Examples

Practical code examples for implementing the recommended modular architecture.

## Example 1: Upgrading Existing Draw Module

### Step 1: Create DrawServiceProvider.php

**File:** `app/Domain/Draw/DrawServiceProvider.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Draw;

use App\Domain\Draw\Services\DrawService;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class DrawServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register draw service as singleton
        $this->app->singleton(DrawService::class);
    }

    public function boot(): void
    {
        // Check feature toggle
        if (! config('steppenreg.features.draw', true)) {
            return;
        }

        // Load migrations (optional - can stay in database/migrations)
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Register Filament plugin for admin panel
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->plugin(DrawPlugin::make());
            }
        });
    }
}
```

### Step 2: Create DrawPlugin.php

**File:** `app/Domain/Draw/DrawPlugin.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Draw;

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
        $panel
            ->discoverPages(
                in: __DIR__ . '/Filament/Pages',
                for: 'App\\Domain\\Draw\\Filament\\Pages'
            )
            ->discoverWidgets(
                in: __DIR__ . '/Filament/Widgets',
                for: 'App\\Domain\\Draw\\Filament\\Widgets'
            );
    }

    public function boot(Panel $panel): void
    {
        // Optional: Register navigation groups or other panel customizations
    }
}
```

### Step 3: Update bootstrap/providers.php

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,

    // Domain Service Providers
    App\Domain\StartingNumber\StartingNumberServiceProvider::class,
    App\Domain\Draw\DrawServiceProvider::class, // ADD THIS
];
```

### Step 4: Update config/steppenreg.php

```php
<?php

return [
    'features' => [
        'starting_numbers' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
        'draw' => env('STEPPENREG_DRAW_ENABLED', true), // ADD THIS
    ],
];
```

### Step 5: Test

```bash
# Start server
./vendor/bin/sail artisan serve

# Visit admin panel
# Navigate to "Manage Draw" page
# Verify page loads correctly

# Disable feature
# In .env: STEPPENREG_DRAW_ENABLED=false
# Restart server
# Verify "Manage Draw" no longer appears in navigation
```

---

## Example 2: Creating New Teams Module

### Current Structure (Before)
```
app/
├── Models/
│   └── Team.php
└── Filament/
    └── Resources/
        └── Teams/
            ├── TeamResource.php
            ├── Schemas/
            └── Tables/
```

### Target Structure (After)
```
app/Domain/Teams/
├── TeamsServiceProvider.php
├── TeamsPlugin.php
├── Models/
│   └── Team.php
├── Services/
│   └── TeamService.php
└── Filament/
    ├── Resources/
    │   └── TeamResource.php
    ├── Schemas/
    │   └── TeamForm.php
    └── Tables/
        └── TeamsTable.php
```

### Implementation

#### 1. Create Directory Structure

```bash
mkdir -p app/Domain/Teams/{Models,Services,Filament/{Resources,Schemas,Tables}}
```

#### 2. Create TeamsServiceProvider.php

**File:** `app/Domain/Teams/TeamsServiceProvider.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Teams;

use App\Domain\Teams\Services\TeamService;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class TeamsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TeamService::class);
    }

    public function boot(): void
    {
        if (! config('steppenreg.features.teams', true)) {
            return;
        }

        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->plugin(TeamsPlugin::make());
            }
        });
    }
}
```

#### 3. Create TeamsPlugin.php

**File:** `app/Domain/Teams/TeamsPlugin.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Teams;

use Filament\Contracts\Plugin;
use Filament\Panel;

class TeamsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'teams';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__ . '/Filament/Resources',
            for: 'App\\Domain\\Teams\\Filament\\Resources'
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
```

#### 4. Move Team Model

```bash
# Move file
git mv app/Models/Team.php app/Domain/Teams/Models/Team.php
```

**Update namespace in file:**
```php
<?php

declare(strict_types=1);

namespace App\Domain\Teams\Models; // CHANGED from App\Models

use Illuminate\Database\Eloquent\Model;
// ... rest of file
```

#### 5. Move Filament Resources

```bash
# Move resources
git mv app/Filament/Resources/Teams/* app/Domain/Teams/Filament/Resources/
```

**Update TeamResource.php:**
```php
<?php

namespace App\Domain\Teams\Filament\Resources; // CHANGED

use App\Domain\Teams\Models\Team; // CHANGED
use Filament\Resources\Resource;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;
    
    // ... rest of file
}
```

#### 6. Update Imports Throughout Codebase

**Find all references:**
```bash
# Find Team model imports
grep -r "use App\\\\Models\\\\Team" app/

# Find TeamResource imports
grep -r "use App\\\\Filament\\\\Resources\\\\Teams" app/
```

**Example update in Registration.php:**
```php
<?php

namespace App\Models;

use App\Domain\Teams\Models\Team; // CHANGED from App\Models\Team

class Registration extends Model
{
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```

#### 7. Create TeamService (New Business Logic Layer)

**File:** `app/Domain/Teams/Services/TeamService.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Teams\Services;

use App\Domain\Teams\Models\Team;
use App\Models\Registration;
use Illuminate\Support\Collection;

class TeamService
{
    public function createTeam(array $data): Team
    {
        return Team::create([
            'name' => $data['name'],
            'track_id' => $data['track_id'],
        ]);
    }

    public function addMember(Team $team, Registration $registration): void
    {
        // Validate track matches
        if ($team->track_id !== $registration->track_id) {
            throw new \InvalidArgumentException(
                'Registration track must match team track'
            );
        }

        $registration->update(['team_id' => $team->id]);
    }

    public function getTeamMembers(Team $team): Collection
    {
        return $team->registrations()
            ->orderBy('created_at')
            ->get();
    }

    public function isTeamFull(Team $team, int $maxMembers = 4): bool
    {
        return $team->registrations()->count() >= $maxMembers;
    }
}
```

#### 8. Register Provider and Update Config

```php
// bootstrap/providers.php
return [
    // ...
    App\Domain\Teams\TeamsServiceProvider::class, // ADD
];

// config/steppenreg.php
'features' => [
    // ...
    'teams' => env('STEPPENREG_TEAMS_ENABLED', true), // ADD
];
```

#### 9. Run Tests

```bash
# Run all tests
./vendor/bin/sail artisan test

# Fix any import errors that appear
```

---

## Example 3: Creating Brand New Waitlist Module

### Full Implementation from Scratch

#### 1. Create Structure

```bash
mkdir -p app/Domain/Waitlist/{Models,Services,Events,Exceptions,Filament/{Pages,Widgets},Tests/{Feature,Unit}}
```

#### 2. Create Model

**File:** `app/Domain/Waitlist/Models/WaitlistEntry.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist\Models;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    protected $fillable = [
        'registration_id',
        'track_id',
        'added_at',
        'promoted_at',
        'position',
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'promoted_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function isPromoted(): bool
    {
        return $this->promoted_at !== null;
    }
}
```

#### 3. Create Migration

**File:** `app/Domain/Waitlist/database/migrations/2026_01_21_000000_create_waitlist_entries_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('track_id');
            $table->timestamp('added_at');
            $table->timestamp('promoted_at')->nullable();
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index(['track_id', 'position']);
            $table->index('promoted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
```

#### 4. Create Events

**File:** `app/Domain/Waitlist/Events/RegistrationAddedToWaitlist.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist\Events;

use App\Domain\Waitlist\Models\WaitlistEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RegistrationAddedToWaitlist
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WaitlistEntry $entry
    ) {}
}
```

**File:** `app/Domain/Waitlist/Events/WaitlistPromoted.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist\Events;

use App\Domain\Waitlist\Models\WaitlistEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaitlistPromoted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WaitlistEntry $entry
    ) {}
}
```

#### 5. Create Service

**File:** `app/Domain/Waitlist/Services/WaitlistService.php`

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
        // Check if registration already on waitlist
        $existing = WaitlistEntry::where('registration_id', $registration->id)
            ->whereNull('promoted_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Check waitlist capacity
        $currentSize = WaitlistEntry::where('track_id', $registration->track_id)
            ->whereNull('promoted_at')
            ->count();

        if ($currentSize >= $this->maxWaitlistSize) {
            throw new WaitlistFullException(
                "Waitlist for track {$registration->track_id} is full"
            );
        }

        // Add to waitlist
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
            $entry->update(['promoted_at' => now()]);

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

    public function getWaitlistStats(int $trackId): array
    {
        $total = WaitlistEntry::where('track_id', $trackId)
            ->whereNull('promoted_at')
            ->count();

        $promoted = WaitlistEntry::where('track_id', $trackId)
            ->whereNotNull('promoted_at')
            ->count();

        return [
            'total_waiting' => $total,
            'total_promoted' => $promoted,
            'capacity' => $this->maxWaitlistSize,
            'available_spots' => $this->maxWaitlistSize - $total,
        ];
    }
}
```

#### 6. Create Exception

**File:** `app/Domain/Waitlist/Exceptions/WaitlistFullException.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist\Exceptions;

use Exception;

class WaitlistFullException extends Exception
{
}
```

#### 7. Create Listener (Integration with Draw Module)

**File:** `app/Domain/Waitlist/Listeners/AddToWaitlistOnNotDrawn.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist\Listeners;

use App\Domain\Draw\Events\RegistrationNotDrawn;
use App\Domain\Waitlist\Exceptions\WaitlistFullException;
use App\Domain\Waitlist\Services\WaitlistService;
use Illuminate\Support\Facades\Log;

class AddToWaitlistOnNotDrawn
{
    public function __construct(
        private WaitlistService $waitlistService
    ) {}

    public function handle(RegistrationNotDrawn $event): void
    {
        try {
            $this->waitlistService->addToWaitlist($event->registration);

            Log::info('Registration added to waitlist', [
                'registration_id' => $event->registration->id,
                'track_id' => $event->registration->track_id,
            ]);
        } catch (WaitlistFullException $e) {
            Log::warning('Waitlist full, could not add registration', [
                'registration_id' => $event->registration->id,
                'track_id' => $event->registration->track_id,
            ]);
        }
    }
}
```

#### 8. Create Filament Page

**File:** `app/Domain/Waitlist/Filament/Pages/ManageWaitlist.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist\Filament\Pages;

use App\Domain\Waitlist\Models\WaitlistEntry;
use App\Domain\Waitlist\Services\WaitlistService;
use App\Settings\EventSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageWaitlist extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.manage-waitlist';

    protected static ?string $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationGroup = 'Registration';

    protected static ?string $navigationLabel = 'Waitlist';

    protected static ?int $navigationSort = 40;

    public function table(Table $table): Table
    {
        return $table
            ->query(WaitlistEntry::query()->whereNull('promoted_at')->orderBy('position'))
            ->columns([
                TextColumn::make('position')
                    ->label('Position')
                    ->sortable(),
                TextColumn::make('registration.name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('registration.email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('track_id')
                    ->label('Track')
                    ->formatStateUsing(function ($state) {
                        $tracks = app(EventSettings::class)->tracks ?? [];
                        $track = collect($tracks)->firstWhere('id', $state);
                        return $track['name'] ?? "Track {$state}";
                    }),
                TextColumn::make('added_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('position');
    }

    public function promoteAction(): Action
    {
        return Action::make('promote')
            ->label('Promote from Waitlist')
            ->form([
                Select::make('track_id')
                    ->label('Track')
                    ->options(function () {
                        $tracks = app(EventSettings::class)->tracks ?? [];
                        return collect($tracks)->pluck('name', 'id');
                    })
                    ->required(),
                TextInput::make('count')
                    ->label('Number to Promote')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(10)
                    ->required(),
            ])
            ->action(function (array $data) {
                $service = app(WaitlistService::class);
                $promoted = $service->promoteFromWaitlist(
                    $data['track_id'],
                    $data['count']
                );

                Notification::make()
                    ->title('Promoted from Waitlist')
                    ->body("Promoted {$promoted->count()} registrations from waitlist")
                    ->success()
                    ->send();

                $this->dispatch('$refresh');
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->promoteAction(),
        ];
    }
}
```

#### 9. Create View

**File:** `resources/views/filament/pages/manage-waitlist.blade.php`

```blade
<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
```

#### 10. Create Service Provider

**File:** `app/Domain/Waitlist/WaitlistServiceProvider.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Waitlist;

use App\Domain\Draw\Events\RegistrationNotDrawn;
use App\Domain\Waitlist\Listeners\AddToWaitlistOnNotDrawn;
use App\Domain\Waitlist\Services\WaitlistService;
use Filament\Panel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WaitlistServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WaitlistService::class, function () {
            return new WaitlistService(
                maxWaitlistSize: config('waitlist.max_size', 100)
            );
        });
    }

    public function boot(): void
    {
        if (! config('steppenreg.features.waitlist', true)) {
            return;
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Register event listeners
        Event::listen(
            RegistrationNotDrawn::class,
            AddToWaitlistOnNotDrawn::class
        );

        // Register Filament plugin
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->plugin(WaitlistPlugin::make());
            }
        });
    }
}
```

#### 11. Create Plugin

**File:** `app/Domain/Waitlist/WaitlistPlugin.php`

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
        $panel->discoverPages(
            in: __DIR__ . '/Filament/Pages',
            for: 'App\\Domain\\Waitlist\\Filament\\Pages'
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
```

#### 12. Create Tests

**File:** `app/Domain/Waitlist/Tests/Unit/WaitlistServiceTest.php`

```php
<?php

namespace App\Domain\Waitlist\Tests\Unit;

use App\Domain\Waitlist\Exceptions\WaitlistFullException;
use App\Domain\Waitlist\Models\WaitlistEntry;
use App\Domain\Waitlist\Services\WaitlistService;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaitlistServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_registration_to_waitlist(): void
    {
        $registration = Registration::factory()->create([
            'draw_status' => 'not_drawn',
            'track_id' => 1,
        ]);

        $service = new WaitlistService(maxWaitlistSize: 100);
        $entry = $service->addToWaitlist($registration);

        $this->assertInstanceOf(WaitlistEntry::class, $entry);
        $this->assertEquals($registration->id, $entry->registration_id);
        $this->assertEquals(1, $entry->position);
    }

    public function test_throws_exception_when_waitlist_full(): void
    {
        $this->expectException(WaitlistFullException::class);

        // Create 5 existing entries
        WaitlistEntry::factory()->count(5)->create([
            'track_id' => 1,
            'promoted_at' => null,
        ]);

        $registration = Registration::factory()->create([
            'track_id' => 1,
        ]);

        // Try to add with max size of 5
        $service = new WaitlistService(maxWaitlistSize: 5);
        $service->addToWaitlist($registration);
    }

    public function test_can_promote_from_waitlist(): void
    {
        WaitlistEntry::factory()->count(3)->create([
            'track_id' => 1,
            'promoted_at' => null,
        ]);

        $service = new WaitlistService();
        $promoted = $service->promoteFromWaitlist(trackId: 1, count: 2);

        $this->assertCount(2, $promoted);
        $this->assertNotNull($promoted->first()->promoted_at);
    }
}
```

#### 13. Register and Configure

```php
// bootstrap/providers.php
return [
    // ...
    App\Domain\Waitlist\WaitlistServiceProvider::class, // ADD
];

// config/steppenreg.php
'features' => [
    // ...
    'waitlist' => env('STEPPENREG_WAITLIST_ENABLED', true), // ADD
];

// .env
STEPPENREG_WAITLIST_ENABLED=true
```

#### 14. Run Migrations and Tests

```bash
# Run migration
./vendor/bin/sail artisan migrate

# Run tests
./vendor/bin/sail artisan test app/Domain/Waitlist/Tests

# Start server and check UI
./vendor/bin/sail artisan serve
```

---

## Common Patterns Reference

### Pattern: Conditional Feature Registration

```php
public function boot(): void
{
    if (! config('steppenreg.features.my_module', true)) {
        return; // Short-circuit entire module
    }
    
    // Register everything else...
}
```

### Pattern: Event Listener Registration

```php
use Illuminate\Support\Facades\Event;

Event::listen(
    SomeEvent::class,
    SomeListener::class
);
```

### Pattern: Service Singleton with Constructor Injection

```php
$this->app->singleton(MyService::class, function ($app) {
    return new MyService(
        dependency: $app->make(SomeDependency::class),
        config: config('my-module.setting')
    );
});
```

### Pattern: Multi-Panel Plugin Registration

```php
Panel::configureUsing(function (Panel $panel): void {
    match ($panel->getId()) {
        'admin' => $panel->plugin(MyPlugin::make()->withFullAccess()),
        'staff' => $panel->plugin(MyPlugin::make()->readOnly()),
        default => null,
    };
});
```

### Pattern: Module Configuration Publishing

```php
$this->publishes([
    __DIR__ . '/config/my-module.php' => config_path('my-module.php'),
], 'my-module-config');
```

---

## Troubleshooting

### Issue: Plugin UI Not Appearing

**Check:**
1. Provider registered in `bootstrap/providers.php`
2. Feature flag enabled in config
3. Plugin registered in `Panel::configureUsing()`
4. Namespaces correct in `discoverPages()`
5. Cache cleared: `php artisan config:clear`

### Issue: Class Not Found Errors After Moving

**Solution:**
```bash
# Clear autoload cache
composer dump-autoload

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Issue: Events Not Firing Between Modules

**Check:**
1. Both modules' providers registered
2. Event listener registered in `boot()`
3. Feature flags enabled for both modules
4. Event class fully qualified in listener

### Issue: Migration Already Exists

**Solution:**
```bash
# If moving migrations to module directory, delete old ones first
rm database/migrations/2024_xx_xx_xxxx_create_waitlist_entries_table.php

# Then load from module
$this->loadMigrationsFrom(__DIR__ . '/database/migrations');
```

---

## Next Steps

1. Review full research: `MODULARIZATION_RESEARCH.md`
2. Check architecture diagrams: `ARCHITECTURE_DIAGRAM.md`
3. See quick reference: `MODULARIZATION_SUMMARY.md`
4. Start with Phase 1: Add plugins to existing domains
5. Gradually extract new modules following examples above
