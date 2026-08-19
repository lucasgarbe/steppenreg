<?php

namespace App\Providers;

use App\Models\Registration;
use App\Models\User;
use App\Observers\RegistrationObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Registration::observe(RegistrationObserver::class);

        config([
            'app.supported_locales' => [
                'de' => ['name' => 'Deutsch', 'flag' => 'DE'],
                'en' => ['name' => 'English', 'flag' => 'EN'],
            ],
        ]);

        Gate::define('viewPulse', function (User $user) {
            if ($user === null) {
                return false;
            }

            return true;
        });
    }
}
