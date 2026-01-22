<?php

use App\Jobs\SendBatchedMailFailureNotifications;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic state transitions to run every minute
Schedule::command('event:update-state')->everyMinute();

// Send batched mail failure notifications every 15 minutes
Schedule::job(new SendBatchedMailFailureNotifications)->everyFifteenMinutes();
