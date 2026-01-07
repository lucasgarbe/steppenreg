# Gender Category Per-Event Implementation

## Overview

This document describes the implementation of gender category-specific registration opening dates for events.

## Implementation Date

January 7, 2026

## Objectives

Allow administrators to configure different registration opening dates for different gender categories (FLINTA*, Open/All Gender) on a per-event basis.

## Changes Made

### 1. Event Model (`app/Models/Event.php`)

Added the following methods to manage gender categories:

- `getGenderCategories()` - Retrieves gender categories from event settings or returns defaults
- `getDefaultGenderCategories()` - Returns default gender category configuration
- `isGenderCategoryOpen(string $gender)` - Checks if a specific gender category is currently open for registration
- `getGenderCategoryOpeningDate(string $gender)` - Gets the opening date for a specific gender category
- `getAvailableGenderCategories()` - Returns only the currently open gender categories
- `getNextGenderCategoryOpening()` - Returns information about the next gender category that will open

### 2. Registration Model (`app/Models/Registration.php`)

Added validation method:

- `canRegisterForGender()` - Validates if the registration's gender category is currently open

### 3. Database Migration

Created migration: `database/migrations/2026_01_07_112735_add_gender_categories_to_events.php`

This migration populates the `Event.settings` JSON column with default gender categories for all existing events, migrating data from the legacy EventSettings system.

### 4. Event Factory (`database/factories/EventFactory.php`)

Created factory for testing purposes with default event attributes.

### 5. Filament Admin Interface (`app/Filament/Resources/Events/EventResource.php`)

Added a new "Gender Categories" section to the Event form with:

- Toggle switches to enable/disable each gender category
- Text input for custom labels
- DateTimePicker for setting gender-specific registration opening dates
- Helpful descriptions explaining the functionality

The section is collapsible and collapsed by default to avoid cluttering the form.

### 6. Registration Controller (`app/Http/Controllers/PublicRegistrationController.php`)

Updated both `create()` and `store()` methods to:

- Fetch the active event
- Check gender category availability
- Validate gender selection against open categories
- Display appropriate messages based on availability
- Pass available gender categories to the view

### 7. Frontend Views

#### Updated `resources/views/public/registration/create.blade.php`

- Modified gender dropdown to show which categories are available
- Display opening dates for unavailable categories
- Show next opening information

#### Created `resources/views/public/registration/not-yet-open.blade.php`

New view for when no gender categories are open yet, showing:

- Information about upcoming openings
- Countdown to next category opening
- List of all upcoming gender category openings

### 8. Migration Command (`app/Console/Commands/MigrateGenderCategorySettings.php`)

Created artisan command `event:migrate-gender-settings` to:

- Migrate gender category settings from legacy EventSettings to per-event configuration
- Handle existing events gracefully
- Provide detailed output about migration progress

Usage:
```bash
php artisan event:migrate-gender-settings
```

### 9. Unit Tests (`tests/Unit/EventGenderCategoryTest.php`)

Comprehensive test suite covering:

- Default gender category retrieval
- Configured gender category retrieval
- Gender category availability checks
- Event registration window validation
- Gender-specific date validation
- Opening date retrieval
- Available categories filtering
- Next opening calculation

## Data Structure

Gender categories are stored in the `Event.settings` JSON column with the following structure:

```json
{
  "gender_categories": {
    "flinta": {
      "enabled": true,
      "label": "FLINTA*",
      "registration_opens_at": "2026-03-01 10:00:00"
    },
    "all_gender": {
      "enabled": true,
      "label": "Open/All Gender",
      "registration_opens_at": "2026-03-08 10:00:00"
    }
  }
}
```

## Business Logic

### Registration Opening Rules

A gender category is considered "open" when ALL of the following conditions are met:

1. The event status is 'active'
2. The event's `registration_opens_at` has passed (if set)
3. The event's `registration_closes_at` has not passed (if set)
4. The gender category is enabled
5. The gender category's `registration_opens_at` has passed (if set)

If no gender-specific `registration_opens_at` is set, the event's `registration_opens_at` is used.

### Validation Flow

1. User accesses registration form
2. System checks if event is active and registration is open
3. System determines which gender categories are available
4. User selects a gender category
5. On form submission, server validates that the selected gender category is still open
6. Registration is created if all validations pass

## Admin Workflow

1. Navigate to Events in Filament admin
2. Edit an event
3. Expand the "Gender Categories" section
4. For each gender category:
   - Enable/disable the category
   - Set a custom label (optional)
   - Set a registration opening date (optional - defaults to event opening date)
5. Save the event

## Migration from Legacy System

The implementation is backward compatible with the legacy `EventSettings` system. The migration command transfers gender-specific dates from EventSettings to individual events.

To migrate:

```bash
php artisan event:migrate-gender-settings
```

## Testing

Run unit tests:

```bash
php artisan test --filter=EventGenderCategoryTest
```

All tests verify:
- Correct behavior of gender category availability logic
- Proper handling of edge cases (missing dates, disabled categories, etc.)
- Accurate calculation of next opening dates

## Future Enhancements

Potential improvements for future iterations:

1. Track-Level Gender Categories - Allow different tracks to have different gender category settings
2. Custom Gender Categories - Allow admins to define custom categories beyond flinta/all_gender
3. Capacity per Gender Category - Set different capacities for each gender category
4. Gender Category-Specific Emails - Send different confirmation emails based on gender category
5. Frontend Countdown Timer - Add live countdown to next gender category opening

## Notes

- This implementation uses JSON storage for flexibility and ease of modification
- The system gracefully falls back to defaults if settings are missing
- All datetime comparisons use Carbon for reliable timezone handling
- The implementation maintains backward compatibility with existing EventSettings
