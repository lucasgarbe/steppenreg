<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EventSettings extends Settings
{
    public string $event_name;

    public bool $site_active;

    public array $tracks;

    public static function group(): string
    {
        return 'event';
    }
}
