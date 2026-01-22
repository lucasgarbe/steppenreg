<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('event.track_label_singular_en', '');
        $this->migrator->add('event.track_label_singular_de', '');
        $this->migrator->add('event.track_label_plural_en', '');
        $this->migrator->add('event.track_label_plural_de', '');
    }
};
