<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Registration;
use App\Observers\RegistrationObserver;
use Illuminate\Support\Facades\Gate;
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
        $this->app->singleton(\App\Services\StartingNumberService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Registration::observe(RegistrationObserver::class);

        // Configure supported locales
        config([
            'app.supported_locales' => [
                'de' => ['name' => 'Deutsch', 'flag' => 'DE'],
                'en' => ['name' => 'English', 'flag' => 'EN'],
            ]
        ]);

        Gate::define('viewPulse', function (User $user) {
            if ($user === null) {
                return false;
            }
            return true;
        });
    }
}
