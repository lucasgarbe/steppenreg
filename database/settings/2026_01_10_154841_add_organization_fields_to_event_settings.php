<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('event.organization_name', 'Your Organization');
        $this->migrator->add('event.organization_website', 'https://example.com');
        $this->migrator->add('event.contact_email', 'contact@example.com');
        $this->migrator->add('event.organization_logo_path', 'logo.png');
        $this->migrator->add('event.event_website_url', 'https://example.com/event');
    }
};
