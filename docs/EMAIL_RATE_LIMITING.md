# Email Rate Limiting - Admin Guide

## Overview

The application implements multi-layered email rate limiting to ensure reliable delivery while respecting email provider limits. This document explains how to monitor bulk sends, troubleshoot issues, and adjust configuration.

## Rate Limits

**Configured Limits:**
- Per-minute: 5 emails maximum
- Per-hour: 30 emails maximum (hard limit)
- Release delay: 60 seconds when rate limited

**Both limits are enforced simultaneously.** The system will never exceed either limit.

## Email Job Types

### 1. Registration Confirmations
- Sent individually when users register
- Retry window: 6 hours
- Expected completion: Minutes to hours

### 2. Draw Notifications
- Sent in bulk after draw completion
- Retry window: 20 hours
- Expected completion for 500 emails: 16-17 hours

### 3. Flexible Emails
- Variable use case (individual or bulk)
- Retry window: 20 hours
- Expected completion: Depends on volume

## Bulk Send Timeline

### Expected Timeline for 500 Emails

| Time Range | Emails Sent | Cumulative | Status |
|------------|-------------|------------|--------|
| Hour 0-1   | 30          | 30         | Initial burst |
| Hour 1-6   | 150         | 180        | Steady sending |
| Hour 6-12  | 180         | 360        | Rate limiting active |
| Hour 12-17 | 140         | 500        | Completion |
| Hour 17-20 | 0           | 500        | Safety buffer |

**Result:** All 500 emails complete within 17 hours.

## Retry Strategy

### Backoff Intervals

Jobs retry with increasing delays:
- Attempt 1: 60 seconds (48-72s with jitter)
- Attempt 2: 5 minutes (4-6min with jitter)
- Attempt 3: 15 minutes (12-18min with jitter)
- Attempt 4: 30 minutes (24-36min with jitter)
- Attempt 5+: 60 minutes (48-72min with jitter)

**Jitter:** Random 20% variation prevents multiple jobs retrying simultaneously.

## Monitoring

### Laravel Pulse Dashboard

Access: `/pulse`

**Key Metrics:**
- Queue depth: Number of pending jobs
- Failed jobs rate: Percentage of failures
- Job duration: Average processing time
- Exception rate: Errors per hour

**Normal Behavior:**
- Queue depth: 0-50 during steady state
- Failed jobs rate: Less than 5%
- Job duration: 1-3 seconds per email
- Exception rate: 0-5 per hour

**During Bulk Send:**
- Queue depth: Spikes to 500, decreases over 17 hours
- Rate-limited jobs: Many jobs in "retry" state (normal)
- Completion rate: ~30 emails per hour

### Mail Logs Admin Panel

Access: `/admin/mail-logs`

**Available Columns:**
- Status: sent, failed, queued, rate limited
- Attempt count: Number of retry attempts
- Rate limit count: Times hit rate limiter
- Last rate limited: Timestamp of last rate limit
- Error message: Failure details (if any)

**Filters:**
- Recent (Last 24h): Default filter
- Recently Rate Limited: Shows delayed emails
- By status: sent, failed, queued
- By template: registration, draw success, etc.

## Common Scenarios

### Scenario 1: Bulk Send in Progress

**Symptoms:**
- 500 jobs in queue
- Many emails showing "Rate Limited" status
- Slow but steady completion rate

**Action:** None required. This is normal behavior.

**Expected:**
- Completion in 16-17 hours
- No failures expected

### Scenario 2: Email Provider Outage

**Symptoms:**
- All emails failing with SMTP errors
- High exception rate in Pulse
- Failed job count increasing

**Action:**
1. Check email provider status
2. Wait for provider recovery
3. Jobs will retry automatically with increasing backoff
4. If outage exceeds retry window (6-20 hours), manual retry needed

### Scenario 3: Configuration Error

**Symptoms:**
- All emails failing immediately
- Error message indicates authentication or configuration issue
- No retries succeeding

**Action:**
1. Check `.env` mail configuration
2. Verify SMTP credentials
3. Test with single email: `php artisan tinker` then dispatch test job
4. Fix configuration
5. Retry failed jobs: `php artisan queue:retry all`

### Scenario 4: Partial Bulk Send Failure

**Symptoms:**
- Some emails sent successfully
- Others failed after 20 hours
- Batch notification received

**Action:**
1. Review failed jobs: `php artisan queue:failed`
2. Check error messages in mail logs
3. Identify pattern (specific email addresses, template, time range)
4. Fix underlying issue
5. Retry failed jobs: `php artisan queue:retry all`

## Manual Intervention

### View Failed Jobs

```bash
./vendor/bin/sail artisan queue:failed
```

**Output:**
```
+------+----------+-------+----------+
| ID   | Queue    | Class | Failed   |
+------+----------+-------+----------+
| 123  | default  | ...   | 2 hours  |
+------+----------+-------+----------+
```

### View Specific Failure Details

```bash
./vendor/bin/sail artisan queue:failed:show 123
```

**Output:** Full exception trace and job payload.

### Retry Single Failed Job

```bash
./vendor/bin/sail artisan queue:retry 123
```

### Retry All Failed Jobs

```bash
./vendor/bin/sail artisan queue:retry all
```

**Note:** Retried jobs start with fresh 6-20 hour retry window.

### Clear Failed Jobs (After Manual Fix)

```bash
./vendor/bin/sail artisan queue:flush
```

**Warning:** This permanently deletes failed job records.

### Monitor Queue Worker

```bash
./vendor/bin/sail artisan queue:work --verbose
```

**Output:** Live stream of job processing with status and timing.

## Adjusting Rate Limits

### When to Adjust

Adjust rate limits if:
- Email provider allows higher sending rates
- Bulk sends need faster completion
- Current limits cause excessive delays

### How to Adjust

1. Edit `.env` file:

```env
MAIL_RATE_LIMIT_PER_MINUTE=10  # Increase from 5
MAIL_RATE_LIMIT_PER_HOUR=60    # Increase from 30
```

2. Clear configuration cache:

```bash
./vendor/bin/sail artisan config:clear
```

3. Restart queue workers:

```bash
./vendor/bin/sail artisan queue:restart
```

**Important:** Never exceed your email provider's limits. Stay 20-30% below maximum to allow for bursts and retries.

### Provider Limit Examples

**Common Email Provider Limits:**
- Mailgun: 100/hour (free), 10,000/hour (paid)
- SendGrid: 100/day (free), 40,000/day (paid)
- Amazon SES: 14/second (after verification)
- Postmark: 100/month (free), 10,000/month (starter)

**Recommended Configuration:**
- Per-minute: 50% of provider's per-minute limit
- Per-hour: 50% of provider's per-hour limit
- Release delay: 60 seconds (don't change)

## Batch Failure Notifications

### How They Work

Failed emails are batched and sent every 15 minutes instead of individual notifications per failure.

**Notification Contains:**
- Total failure count
- Breakdown by template type
- Time period of failures
- Link to mail logs admin panel

**Example Notification:**

```
Subject: Batch Email Failures: 25 emails failed

Multiple emails failed to send after exhausting retry attempts.

Total Failures: 25
Time Period: 2 hours ago to 1 minute ago

Breakdown by Template:
- draw_success: 15 failures
- registration_confirmation: 10 failures

[View Mail Logs Button]
```

### Notification Schedule

- Frequency: Every 15 minutes
- Trigger: At least 1 failed email in batch
- Recipients: All admin users
- Channels: Email and database notification

### Managing Notifications

**View in Admin Panel:**
Navigate to notifications bell icon (top right)

**Mark as Read:**
Click notification to view details and mark as read

**Disable Notifications:**
Not recommended. Notifications indicate system issues requiring attention.

## Troubleshooting Guide

### Problem: Queue Not Processing

**Check queue worker status:**
```bash
./vendor/bin/sail artisan queue:monitor
```

**Start queue worker:**
```bash
./vendor/bin/sail artisan queue:work
```

**Expected:** Worker processes jobs continuously.

### Problem: All Emails Stuck in "Queued"

**Possible Causes:**
- Queue worker not running
- Rate limits too restrictive
- Database queue table locked

**Solution:**
1. Restart queue worker: `php artisan queue:restart`
2. Check queue depth: `php artisan queue:monitor`
3. Review recent exceptions in Pulse

### Problem: High Failure Rate

**Check Pulse metrics:**
- Exception rate > 10/hour: Indicates systemic issue
- Failed jobs rate > 10%: Configuration or provider problem

**Common Causes:**
- Invalid SMTP credentials
- Email provider rate limiting
- Network connectivity issues
- Malformed email addresses

**Solution:**
1. Test single email manually
2. Review exception messages
3. Verify email provider status
4. Check application logs: `tail -f storage/logs/laravel.log`

### Problem: Emails Taking Longer Than Expected

**Expected Times:**
- 500 emails: 16-17 hours
- 200 emails: 6-7 hours
- 50 emails: 1.7 hours
- 10 emails: 20 minutes

**If slower than expected:**
1. Check if rate limits are configured correctly
2. Verify queue worker is running
3. Review retry count in mail logs (high count indicates issues)
4. Check for system resource constraints

## Advanced Operations

### Manual Bulk Retry with Filtering

Retry only specific template types:

```bash
./vendor/bin/sail artisan tinker
>>> DB::table('failed_jobs')
      ->where('payload', 'like', '%SendDrawNotification%')
      ->get()
      ->each(fn($job) => Artisan::call('queue:retry', ['id' => $job->id]));
```

### Monitor Specific Template

```bash
./vendor/bin/sail artisan tinker
>>> App\Models\MailLog::where('template_key', 'draw_success')
      ->where('created_at', '>=', now()->subHours(24))
      ->get(['status', DB::raw('count(*) as count')])
      ->groupBy('status');
```

**Output:** Count by status for last 24 hours.

### Clear Rate Limiter Cache

If rate limiter appears stuck:

```bash
./vendor/bin/sail artisan cache:clear
```

**Note:** This resets all rate limit counters. Use cautiously.

## Support and Escalation

### When to Escalate

Contact development team if:
- Failure rate exceeds 20% for more than 1 hour
- Queue depth remains at maximum for more than 24 hours
- Batch notifications indicate sustained systemic failures
- Email provider reports abuse or policy violations

### Information to Provide

When escalating issues, include:
1. Pulse dashboard screenshot
2. Recent failed job IDs and error messages
3. Mail logs export (filtered to relevant timeframe)
4. Email provider error responses (if any)
5. Recent changes to configuration or environment

## Maintenance

### Regular Tasks

**Daily:**
- Review Pulse dashboard for anomalies
- Check failed jobs count

**Weekly:**
- Review batch failure notifications
- Analyze long-term sending trends
- Verify queue worker uptime

**Monthly:**
- Review email provider usage statistics
- Adjust rate limits if needed
- Clean up old mail logs (optional)

### Health Checks

Run health check command (if available):
```bash
./vendor/bin/sail artisan queue:monitor
```

**Healthy System Indicators:**
- Queue depth near zero most of the time
- Failed jobs less than 1% of total
- Average job duration under 3 seconds
- No sustained exceptions

## Configuration Reference

### Environment Variables

```env
# Email Rate Limiting
MAIL_RATE_LIMIT_PER_MINUTE=5
MAIL_RATE_LIMIT_PER_HOUR=30
MAIL_RATE_LIMIT_RELEASE_DELAY=60

# Queue Configuration
QUEUE_CONNECTION=database

# SMTP Settings
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=registration@yourevent.com
MAIL_FROM_NAME="${APP_NAME}"
```

### File Locations

- Configuration: `config/mail.php`
- Mail jobs: `app/Jobs/Mail/`
- Mail logs model: `app/Models/MailLog.php`
- Failure batch model: `app/Models/MailFailureBatch.php`
- Notifications: `app/Notifications/`
- Scheduled tasks: `routes/console.php`

## Appendix

### Glossary

- **Rate Limiting:** Restricting number of emails sent per time period
- **Backoff:** Increasing delay between retry attempts
- **Jitter:** Random variation to prevent synchronized retries
- **Queue Depth:** Number of jobs waiting to be processed
- **Retry Window:** Maximum time allowed for job retries
- **Batch Notification:** Grouped notification for multiple failures

### Related Documentation

- Laravel Queue Documentation: https://laravel.com/docs/queues
- Laravel Rate Limiting: https://laravel.com/docs/routing#rate-limiting
- Laravel Pulse: https://pulse.laravel.com

### Changelog

- 2026-01-22: Initial documentation for multi-layered rate limiting system
