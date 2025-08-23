<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('event.event_name', 'Steppenreg');
        $this->migrator->add('event.site_active', true);
    }
};
