<?php

namespace App\Providers;

use App\Domain\Mail\Jobs\MailJob;
use App\Models\Registration;
use App\Models\User;
use App\Observers\RegistrationObserver;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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

        Event::listen(function (JobProcessing $event) {
            if ($event->job->payload()['data']['command'] ?? null) {
                $job = @unserialize($event->job->payload()['data']['command']);
                if ($job instanceof MailJob) {
                    Log::info('[Mail Trail] Queue job processing started', [
                        'job_class' => get_class($job),
                        'job_id' => $event->job->getJobId(),
                        'registration_id' => $job->registration->id ?? null,
                        'attempt' => $event->job->attempts(),
                    ]);
                }
            }
        });

        Event::listen(function (JobProcessed $event) {
            if ($event->job->payload()['data']['command'] ?? null) {
                $job = @unserialize($event->job->payload()['data']['command']);
                if ($job instanceof MailJob) {
                    Log::info('[Mail Trail] Queue job processed successfully', [
                        'job_class' => get_class($job),
                        'job_id' => $event->job->getJobId(),
                        'registration_id' => $job->registration->id ?? null,
                    ]);
                }
            }
        });

        Event::listen(function (JobFailed $event) {
            if ($event->job->payload()['data']['command'] ?? null) {
                $job = @unserialize($event->job->payload()['data']['command']);
                if ($job instanceof MailJob) {
                    Log::error('[Mail Trail] Queue job failed', [
                        'job_class' => get_class($job),
                        'job_id' => $event->job->getJobId(),
                        'registration_id' => $job->registration->id ?? null,
                        'exception' => $event->exception->getMessage(),
                    ]);
                }
            }
        });
    }
}
