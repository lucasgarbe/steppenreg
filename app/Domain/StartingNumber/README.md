# StartingNumber Domain

**Version:** 1.0.0  
**Status:** ✅ Fully Implemented

---

## Purpose

Automatically assign starting numbers to registrations that are drawn in the lottery system. Provides configurable number ranges per track, overflow handling, and a management UI for administrators.

---

## Features

- **Automatic Assignment:** Numbers assigned automatically when registrations are drawn
- **Per-Track Ranges:** Configure specific number ranges for each track
- **Global Overflow Bucket:** Fallback range when track ranges are exhausted
- **Flexible Formatting:** Configurable padding, prefix, and suffix
- **Assignment Strategies:** Sequential or random number assignment
- **Reserved Numbers:** Exclude specific numbers from assignment
- **Settings Page UI:** Intuitive interface for configuration
- **Statistics Widget:** Real-time dashboard widget showing assignment status
- **Feature Toggle:** Can be enabled/disabled per installation

---

## Installation

### 1. Publish Configuration

```bash
php artisan vendor:publish --tag=starting-numbers-config
```

This creates `config/starting-numbers.php` with default settings.

### 2. Configure Feature Toggle

In `config/steppenreg.php`:

```php
'features' => [
    'starting_numbers' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
],
```

Or via `.env`:

```env
STEPPENREG_STARTING_NUMBERS_ENABLED=true
```

### 3. Configure via UI

Visit `/admin/starting-numbers` to configure:
- Track number ranges
- Overflow bucket settings
- Number formatting
- Assignment strategy

---

## Configuration

### Via Settings Page (Recommended)

1. Navigate to **Configuration > Starting Numbers** in admin panel
2. Add track ranges with start/end numbers
3. Configure overflow bucket (optional)
4. Set formatting options
5. Choose assignment strategy
6. Save configuration

### Via Config File

Edit `config/starting-numbers.php`:

```php
return [
    'enabled' => true,
    
    'tracks' => [
        1 => [
            'name' => '50km Track',
            'start' => 1,
            'end' => 500,
        ],
        2 => [
            'name' => '100km Track',
            'start' => 501,
            'end' => 1000,
        ],
    ],
    
    'overflow' => [
        'enabled' => true,
        'start' => 9001,
        'end' => 9999,
    ],
    
    'format' => [
        'padding' => 4,      // 0001, 0042, 1234
        'prefix' => '',      // 'BIB-' → BIB-0001
        'suffix' => '',      // '-A' → 0001-A
    ],
    
    'strategy' => 'sequential',  // 'sequential' or 'random'
    
    'reserved' => [1, 13, 666],  // Numbers to never assign
    
    'auto_assign' => true,              // Assign on draw
    'allow_manual_override' => true,    // Allow admin changes
];
```

---

## Events

### Listens To

- **`App\Domain\Draw\Events\RegistrationDrawn`**
  - Triggered when a registration is selected in the draw
  - Automatically assigns a starting number if enabled

### Emits

- **`App\Domain\StartingNumber\Events\StartingNumberAssigned`**
  - Triggered after a number is successfully assigned
  - Payload: `$registration`, `$number`

- **`App\Domain\StartingNumber\Events\StartingNumberCleared`**
  - Triggered when a number is removed from a registration
  - Payload: `$registration`, `$number`

---

## Dependencies

### Core Models

- **`App\Models\Registration`** - Uses `starting_number` field

### Other Domains

- **`App\Domain\Draw`** - Listens to `RegistrationDrawn` event

**Dependency Direction:** StartingNumber → Draw (one-way, event-driven)

---

## Usage

### Automatic Assignment

Numbers are assigned automatically when the Draw domain executes a draw and emits `RegistrationDrawn` events. No manual intervention required.

### Programmatic Usage

```php
use App\Domain\StartingNumber\Services\StartingNumberService;

$service = app(StartingNumberService::class);

// Assign a number
$number = $service->assignNumber($registration);

// Clear a number
$service->clearNumber($registration);

// Check if number available
$available = $service->isNumberAvailable($number, $trackId);
```

### Feature Toggle Check

```php
if (config('steppenreg.features.starting_numbers', true)) {
    // Feature is enabled
}

// Or check domain config
if (config('starting-numbers.enabled', true)) {
    // Same check
}
```

---

## UI Components

### Settings Page

**Location:** `/admin/starting-numbers`  
**Access:** Authenticated users (requires `manage_starting_numbers` permission)

**Features:**
- Add/edit track number ranges
- Configure overflow bucket
- Set number formatting
- Choose assignment strategy
- Define reserved numbers
- Real-time preview of formatted numbers
- Validation prevents overlapping ranges

### Statistics Widget

**Location:** Admin Dashboard  
**Updates:** Every 30 seconds

**Displays:**
- Total numbers assigned
- Track-by-track breakdown with progress bars
- Overflow bucket usage
- Assignment completion percentage
- Last assignment timestamp
- 7-day assignment trend chart

---

## Testing

### Run All Tests

```bash
# All tests
php artisan test

# Domain tests only
php artisan test app/Domain/StartingNumber/Tests
```

### Test Coverage

- **Unit Tests:** `Tests/Unit/`
  - StartingNumberService functionality
  - Number formatting logic
  - Range validation
  - Strategy implementation

- **Feature Tests:** `Tests/Feature/`
  - Settings page access
  - Configuration updates
  - Event listener behavior
  - Feature toggle functionality

---

## Troubleshooting

### Numbers Not Being Assigned

1. **Check Feature Toggle:**
   ```bash
   php artisan config:show steppenreg.features.starting_numbers
   ```

2. **Verify Event Listener:**
   ```bash
   php artisan event:list | grep RegistrationDrawn
   ```

3. **Check Service Provider:**
   ```bash
   php artisan about | grep StartingNumber
   ```

### Overlapping Ranges Error

- Track ranges must not overlap
- Overflow range must not overlap with any track range
- Use Settings Page validation to catch overlaps

### Config Not Loading

1. Clear config cache:
   ```bash
   php artisan config:clear
   ```

2. Verify config published:
   ```bash
   ls -la config/starting-numbers.php
   ```

3. Re-publish if needed:
   ```bash
   php artisan vendor:publish --tag=starting-numbers-config --force
   ```

### Widget Not Showing

1. Check feature toggle enabled
2. Verify plugin registered
3. Clear view cache:
   ```bash
   php artisan view:clear
   ```

---

## Technical Details

### File Structure

```
app/Domain/StartingNumber/
├── config/
│   └── starting-numbers.php       (Domain configuration)
├── Events/
│   ├── StartingNumberAssigned.php
│   └── StartingNumberCleared.php
├── Exceptions/
│   └── NoAvailableNumberException.php
├── Filament/
│   ├── Pages/
│   │   └── ManageStartingNumbers.php   (Settings UI)
│   └── Widgets/
│       └── StartingNumberStatsWidget.php (Dashboard widget)
├── Listeners/
│   └── AssignStartingNumberOnDrawn.php  (Event subscriber)
├── Services/
│   └── StartingNumberService.php         (Business logic)
├── Tests/
│   ├── Unit/
│   │   └── StartingNumberServiceTest.php
│   └── Feature/
│       └── SettingsPageTest.php
├── StartingNumberPlugin.php              (Filament plugin)
├── StartingNumberServiceProvider.php     (Laravel provider)
└── README.md                              (This file)
```

### Service Provider Registration

Registered in `bootstrap/providers.php`:

```php
App\Domain\StartingNumber\StartingNumberServiceProvider::class,
```

### Filament Plugin Registration

Automatically registered for the 'admin' panel via `Panel::configureUsing()` in the service provider.

---

## Future Enhancements

Potential features for future versions:

- [ ] Bulk number assignment/clearing
- [ ] Number history/audit log
- [ ] Number swap/exchange functionality
- [ ] Export assigned numbers to CSV
- [ ] Email notifications when numbers assigned
- [ ] QR code generation with starting number
- [ ] Import number ranges from file
- [ ] Multi-format support (alphanumeric)

---

## Support

### Documentation

- Main Documentation: `/docs/rework/`
- Laravel Docs: https://laravel.com/docs
- Filament Docs: https://filamentphp.com/docs

### Common Commands

```bash
# Publish config
php artisan vendor:publish --tag=starting-numbers-config

# Clear caches
php artisan optimize:clear

# Run tests
php artisan test app/Domain/StartingNumber/Tests

# Check configuration
php artisan config:show starting-numbers
```

---

## Changelog

### v1.0.0 (2026-01-21)

- Initial implementation
- Settings Page for configuration
- Statistics Widget for dashboard
- Event-driven automatic assignment
- Per-track number ranges
- Global overflow bucket
- Flexible number formatting
- Sequential and random strategies
- Reserved numbers support
- Feature toggle support

---

**Domain Status:** ✅ Production Ready  
**Maintainer:** Steppenreg Development Team  
**Last Updated:** January 21, 2026
