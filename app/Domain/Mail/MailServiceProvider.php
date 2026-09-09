<?php

namespace App\Domain\Mail;

use App\Domain\Mail\Services\MailTemplateService;
use App\Domain\Mail\Services\VariableResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VariableResolver::class);
        $this->app->singleton(MailTemplateService::class);
    }

    public function boot(): void
    {
        if (! config('steppenreg.features.mail', true)) {
            return;
        }

        RateLimiter::for('emails', function ($job) {
            return [
                Limit::perMinute((int) config('mail.rate_limit_per_minute', 5))
                    ->by('minute:emails'),
                Limit::perHour((int) config('mail.rate_limit_per_hour', 30))
                    ->by('hour:emails'),
            ];
        });
    }
}
