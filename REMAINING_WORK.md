# Remaining Work for Steppenreg Refactoring

## Current Status: 26/38 Tasks Complete (68%)

---

## HIGH PRIORITY TASKS REMAINING

### Task 24: Clean up RegistrationsTable.php (CRITICAL - IN PROGRESS)

**File:** `app/Filament/Resources/Registrations/Tables/RegistrationsTable.php`  
**Current:** 906 lines  
**Target:** ~620 lines

#### Actions to Remove (Lines 398-549):

1. **Remove `promote_from_waitlist` action** (Lines 398-431)
   - Complete action definition with modal and handler
   
2. **Remove `add_to_waitlist` action** (Lines 433-463)
   - Complete action definition with modal and handler
   
3. **Remove `manual_withdraw` action** (Lines 465-488)
   - Complete action definition with modal and handler
   
4. **Remove `send_withdrawal_link` action** (Lines 532-549)
   - Complete action definition

#### Bulk Actions to Remove (Lines 721-763):

1. **Remove `generate_waitlist_tokens` bulk action** (Lines 721-741)
2. **Remove `generate_withdraw_tokens` bulk action** (Lines 743-763)

#### Column Already Removed:
- ✅ `withdrawalRequest.withdrawal_reason` column removed

#### Column to Update (Lines 121-145):

**Current draw_status column:**
```php
TextColumn::make('draw_status')
    ->label(__('admin.registrations.columns.draw_status'))
    ->badge()
    ->color(fn($record): string => match ($record?->draw_status) {
        'drawn' => $record?->is_withdrawn ? 'danger' : 'success',
        'waitlist' => 'warning',  // REMOVE THIS LINE
        'not_drawn' => 'gray',
        default => 'gray',
    })
    ->formatStateUsing(function ($record): string {
        if ($record?->is_withdrawn) {  // REMOVE THIS CHECK (is_withdrawn doesn't exist)
            return __('admin.registrations.draw_status.withdrawn');
        }
        if ($record?->is_waitlist_registered && $record?->draw_status === 'waitlist') {  // REMOVE THIS
            $position = $record->getWaitlistPosition();
            return __('messages.waitlist') . " #{$position}";
        }
        return match ($record?->draw_status) {
            'drawn' => __('admin.registrations.draw_status.drawn'),
            'waitlist' => __('admin.registrations.draw_status.waitlist'),  // REMOVE THIS
            'not_drawn' => __('admin.registrations.draw_status.not_drawn'),
            default => $record?->draw_status ?? '',
        };
    })
    ->sortable(),
```

**Should become:**
```php
TextColumn::make('draw_status')
    ->label(__('admin.registrations.columns.draw_status'))
    ->badge()
    ->color(fn($record): string => match ($record?->draw_status) {
        'drawn' => 'success',
        'not_drawn' => 'gray',
        default => 'gray',
    })
    ->formatStateUsing(function ($record): string {
        return match ($record?->draw_status) {
            'drawn' => __('admin.registrations.draw_status.drawn'),
            'not_drawn' => __('admin.registrations.draw_status.not_drawn'),
            default => $record?->draw_status ?? '',
        };
    })
    ->sortable(),
```

#### Filter to Update (Around Line 327):

Find the `draw_status` filter and remove 'waitlist' option:

**Current:**
```php
->options([
    'not_drawn' => 'Not Drawn',
    'drawn' => 'Drawn',
    'waitlist' => 'Waitlist',  // REMOVE THIS LINE
])
```

**Should become:**
```php
->options([
    'not_drawn' => 'Not Drawn',
    'drawn' => 'Drawn',
])
```

#### Other References to Clean:

Search for these patterns and remove/update:
- Lines 402, 578, 800: Remove conditions checking for `draw_status === 'waitlist'`
- Any references to `is_withdrawn`, `can_join_waitlist`, `can_withdraw` (these accessors don't exist anymore)

**Search command:**
```bash
grep -n "is_withdrawn\|can_join_waitlist\|can_withdraw" app/Filament/Resources/Registrations/Tables/RegistrationsTable.php
```

---

## MEDIUM PRIORITY TASKS

### Task 30: Update MailTemplateSeeder.php

**File:** `database/seeders/MailTemplateSeeder.php`  
**Current:** 15,595 lines

#### Templates to Remove:

1. **waitlist_registration_success** template
   ```bash
   # Find the template
   grep -n "'key' => 'waitlist_registration_success'" database/seeders/MailTemplateSeeder.php
   ```
   
2. **withdrawal_confirmation** template
   ```bash
   grep -n "'key' => 'withdrawal_confirmation'" database/seeders/MailTemplateSeeder.php
   ```
   
3. **draw_waitlist** template
   ```bash
   grep -n "'key' => 'draw_waitlist'" database/seeders/MailTemplateSeeder.php
   ```

Each template definition is a large array with English and German versions. Delete the entire array for each template.

After removing, refresh the mail templates in the database:
```bash
php artisan db:seed --class=MailTemplateSeeder
```

---

## TEST WRITING TASKS (HIGH PRIORITY)

All test implementations are provided in the initial plan. Copy and create these files:

### Task 32: DrawService Unit Tests

**Create:** `tests/Unit/Services/DrawServiceTest.php`

<details>
<summary>Click to see full test code (provided in initial plan)</summary>

Full implementation was provided in the implementation plan. Tests include:
- test_draw_selects_correct_number_of_registrations
- test_draw_throws_exception_if_already_executed
- test_draw_throws_exception_if_no_registrations
- test_draw_treats_teams_as_atomic_units
- test_draw_respects_available_spots_limit
- test_draw_creates_draw_record_with_correct_data
- test_has_draw_been_executed_returns_correct_value

</details>

### Task 33: Registration Feature Tests

**Create:** `tests/Feature/RegistrationTest.php`

Tests include:
- test_user_can_register_for_event
- test_user_cannot_register_twice_for_same_track
- test_registration_sends_confirmation_email
- test_registration_is_stored_with_correct_initial_status
- test_registration_requires_all_mandatory_fields

### Task 34: Team Registration Feature Tests

**Create:** `tests/Feature/TeamRegistrationTest.php`

Tests include:
- test_user_can_create_new_team
- test_user_can_join_existing_team
- test_team_cannot_exceed_max_size
- test_all_team_members_must_be_on_same_track
- test_teams_are_drawn_as_atomic_units

### Task 35: Draw Execution Feature Tests

**Create:** `tests/Feature/DrawExecutionTest.php`

Tests include:
- test_draw_can_only_be_executed_once_per_track
- test_draw_sends_notifications_to_all_registrations
- test_draw_creates_draw_record_in_database
- test_draw_updates_registration_statuses
- test_draw_links_registrations_to_draw_record
- test_drawn_registrations_have_drawn_at_timestamp

### Task 36: Run Test Suite

```bash
./vendor/bin/sail artisan test
# or
composer test
```

Fix any failures that occur.

---

## LOW PRIORITY TASKS

### Task 27: Create DrawResource.php (Optional)

**File:** `app/Filament/Resources/DrawResource.php`

Full implementation provided in initial plan. Creates a read-only Filament resource to view draw history with statistics.

### Task 31: Remove Translation Keys

Search for and remove waitlist/withdrawal keys:

```bash
# Check if language files exist
ls -la lang/ 2>/dev/null || echo "No lang directory"

# Search for keys
grep -r "waitlist\|withdrawal\|withdraw" lang/ 2>/dev/null
```

Remove any translation keys found.

### Tasks 37-38: Documentation Updates

Update documentation files in `docs/rework/` to remove waitlist references and add draw system documentation. Full implementation of `docs/draw-system.md` is provided in the initial plan.

---

## VERIFICATION CHECKLIST

After completing all tasks:

- [ ] Run migrations: `php artisan migrate`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Test admin panel login and navigation
- [ ] Test draw execution in admin panel
- [ ] Test public registration form
- [ ] Verify no 404 errors on removed routes
- [ ] Run test suite: `composer test`
- [ ] Check for PHP errors in logs
- [ ] Search for remaining references:
  ```bash
  grep -r "WaitlistEntry\|WithdrawalRequest\|waitlist_url\|withdraw_url" app/
  grep -r "generateWaitlistToken\|generateWithdrawToken" app/
  grep -r "joinWaitlist\|withdraw(" app/
  ```

---

## COMPLETED TASKS (26/38)

✅ All database migrations created and run  
✅ Draw domain completely extracted  
✅ Registration model cleaned (489→240 lines)  
✅ WaitlistEntry & WithdrawalRequest models deleted  
✅ MailVariableResolver cleaned  
✅ StartingNumberService cleaned  
✅ WaitlistController deleted  
✅ Routes cleaned  
✅ 3 mail job classes deleted  
✅ SendDrawNotification updated  
✅ ManageWaitlist page deleted  
✅ ManageDraw.php refactored (324→227 lines)  
✅ DrawStatsWidget updated  
✅ RegistrationForm updated  
✅ Waitlist view directory deleted  
✅ Withdraw view directory deleted  
✅ REFACTORING_STATUS.md created  

---

## ESTIMATED TIME TO COMPLETE

- RegistrationsTable.php cleanup: 45-60 minutes
- MailTemplateSeeder cleanup: 30 minutes
- Test writing (copy/paste provided code): 45 minutes
- Testing and verification: 30-45 minutes

**Total: 2.5-3 hours**

---

## SUPPORT

All test code is provided in the initial implementation plan. Refer to:
- `REFACTORING_STATUS.md` for overall status
- Initial plan document for complete test implementations
- Draw domain is fully functional and ready to use

**You're 68% complete! Just a few more tasks to go!**
