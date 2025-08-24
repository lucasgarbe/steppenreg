<?php

namespace App\Providers;

use App\Models\Registration;
use App\Observers\RegistrationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\MailVariableResolver::class);
        $this->app->singleton(\App\Services\MailTemplateService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Registration::observe(RegistrationObserver::class);
    }
}
