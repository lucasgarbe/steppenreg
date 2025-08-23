<?php

use Spatie\LaravelSettings\Settings;

class EventSettings extends Settings
{
    public string $event_name;

    public bool $site_active;

    public static function group(): string
    {
        return 'event';
    }
}
