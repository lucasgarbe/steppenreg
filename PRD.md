# Product Requirements Document: Multi-Layered Email Rate Limiting

## Overview
Implement comprehensive email rate limiting with dual-layer throttling (per-minute and per-hour), intelligent retry strategies, enhanced monitoring, and batch notifications for failed emails.

## Objectives
- Ensure maximum 30 emails sent per hour (hard limit)
- Distribute load with 5 emails per minute limit
- Support bulk sends up to 500 emails within 20-hour window
- Provide full visibility into rate limiting behavior
- Minimize manual intervention through intelligent retry logic

---

## Requirements

### 1. Update Backoff Strategy to Extended Timings

**Description:** Replace current backoff timings with extended intervals that align with hourly rate limits.

**Acceptance Criteria:**
- [ ] SendFlexibleMail uses backoff: [60, 300, 900, 1800, 3600] seconds
- [ ] SendDrawNotification uses backoff: [60, 300, 900, 1800, 3600] seconds
- [ ] SendRegistrationConfirmation uses backoff: [60, 300, 900, 1800, 3600] seconds
- [ ] Backoff includes 20% jitter to prevent thundering herd
- [ ] Final backoff interval is 60 minutes (3600 seconds)
- [ ] Code passes Laravel Pint style checks

**Files Modified:**
- app/Jobs/Mail/SendFlexibleMail.php
- app/Jobs/Mail/SendDrawNotification.php
- app/Jobs/Mail/SendRegistrationConfirmation.php

---

### 2. Update Retry Window for Bulk Send Jobs

**Description:** Extend retry window for bulk send jobs to accommodate 500 emails at 30/hour rate.

**Acceptance Criteria:**
- [ ] SendFlexibleMail retryUntil set to 20 hours
- [ ] SendDrawNotification retryUntil set to 20 hours
- [ ] SendRegistrationConfirmation retryUntil remains 6 hours
- [ ] DateTime import added to all modified job files
- [ ] retryUntil returns proper DateTime object

**Files Modified:**
- app/Jobs/Mail/SendFlexibleMail.php
- app/Jobs/Mail/SendDrawNotification.php
- app/Jobs/Mail/SendRegistrationConfirmation.php (verify only)

---

### 3. Implement Batch Notification System for Failed Emails

**Description:** Replace per-email failure notifications with batched notifications to prevent notification spam during bulk send failures.

**Acceptance Criteria:**
- [ ] Create MailFailureBatch model with fields: count, template_keys, started_at, completed_at
- [ ] Create database migration for mail_failure_batches table
- [ ] Create BatchMailFailureNotification class
- [ ] Update job failed() methods to accumulate failures instead of immediate notification
- [ ] Implement job to send batched notifications every 15 minutes
- [ ] Batch notification includes: total count, breakdown by template, time range, link to admin panel
- [ ] Individual notifications removed from job failed() methods
- [ ] Batched notifications sent to all admin users

**Files Created:**
- app/Models/MailFailureBatch.php
- database/migrations/YYYY_MM_DD_HHMMSS_create_mail_failure_batches_table.php
- app/Notifications/BatchMailFailureNotification.php
- app/Jobs/SendBatchedMailFailureNotifications.php

**Files Modified:**
- app/Jobs/Mail/SendFlexibleMail.php
- app/Jobs/Mail/SendRegistrationConfirmation.php
- app/Jobs/Mail/SendDrawNotification.php
- app/Console/Kernel.php (add scheduled task)

---

### 4. Create Admin Documentation

**Description:** Create concise documentation for admins explaining monitoring, troubleshooting, and manual intervention procedures.

**Acceptance Criteria:**
- [ ] Documentation file created in docs/ directory
- [ ] Covers: bulk send monitoring, manual retry procedures, rate limit adjustment
- [ ] Includes: expected timings for 500-email bulk send
- [ ] Lists: common failure scenarios and resolutions
- [ ] Provides: Laravel Pulse monitoring instructions
- [ ] Contains: queue worker commands reference
- [ ] Uses clear, concise language without emojis
- [ ] Includes example commands with expected outputs

**Files Created:**
- docs/EMAIL_RATE_LIMITING.md

---

### 5. Testing and Validation

**Description:** Verify all components work correctly through automated and manual testing.

**Acceptance Criteria:**
- [ ] All database migrations run successfully
- [ ] Laravel Pint passes without errors
- [ ] Single email dispatch works correctly
- [ ] 10-email batch completes within expected timeframe
- [ ] Rate limiting triggers and logs correctly in MailLog
- [ ] Backoff timing verified through queue worker logs
- [ ] Batch notifications accumulate failures correctly
- [ ] Admin panel displays all tracking fields properly
- [ ] No PHP syntax errors in any modified files
- [ ] Configuration values load correctly from .env

---

## Technical Specifications

### Backoff Timing
- Attempt 1: 60 seconds (48-72s with jitter)
- Attempt 2: 300 seconds (240-360s with jitter)
- Attempt 3: 900 seconds (720-1080s with jitter)
- Attempt 4: 1800 seconds (1440-2160s with jitter)
- Attempt 5+: 3600 seconds (2880-4320s with jitter)

### Retry Windows
- Bulk jobs (FlexibleMail, DrawNotification): 20 hours
- Individual jobs (RegistrationConfirmation): 6 hours

### Rate Limits
- Per minute: 5 emails
- Per hour: 30 emails
- Release delay: 60 seconds

### Batch Notification Schedule
- Frequency: Every 15 minutes
- Minimum failures to trigger: 1
- Notification channels: email, database

---

## Success Metrics

- 500-email bulk send completes within 20 hours
- Zero emails fail due to retry window expiration in normal operation
- Rate limit hit count visible in admin panel
- Admin receives single batched notification instead of 500 individual notifications
- Queue worker maintains steady throughput without thrashing
- Manual retry procedures documented and accessible

---

## Dependencies

- Laravel 12.x framework
- Filament 4.x admin panel
- PostgreSQL database
- Laravel Pulse for monitoring
- Existing MailLog infrastructure

---

## Rollback Plan

If issues arise:
1. Revert migration: `php artisan migrate:rollback`
2. Restore previous job files from git
3. Clear configuration cache: `php artisan config:clear`
4. Restart queue workers: `php artisan queue:restart`
5. Monitor failed jobs: `php artisan queue:failed`

---

## Implementation Timeline

1. Update backoff strategy (15 min)
2. Update retry windows (10 min)
3. Implement batch notification system (45 min)
4. Create admin documentation (30 min)
5. Testing and validation (20 min)

**Total estimated time:** 2 hours
