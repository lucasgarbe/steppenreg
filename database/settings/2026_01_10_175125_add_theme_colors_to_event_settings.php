<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('event.theme_primary_color', '#F9C458');
        $this->migrator->add('event.theme_background_color', '#fffdf8c2');
        $this->migrator->add('event.theme_text_color', '#1a1a1a');
        $this->migrator->add('event.theme_accent_color', '#7a58fc');
    }
};
