<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // DateTime fields for automatic state transitions
        $this->migrator->add('event.flinta_registration_opens_at', null);
        $this->migrator->add('event.everyone_registration_opens_at', null);
        $this->migrator->add('event.registration_closes_at', null);
        $this->migrator->add('event.waitlist_only_starts_at', null);
        $this->migrator->add('event.event_starts_at', null);
        $this->migrator->add('event.event_ends_at', null);
        
        // Control flags
        $this->migrator->add('event.automatic_state_transitions', false);
        $this->migrator->add('event.manual_override_active', false);
        $this->migrator->add('event.manual_override_state', null);
    }
};
