<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('event.default_team_max_members', null);
    }

    public function down(): void
    {
        $this->migrator->delete('event.default_team_max_members');
    }
};
