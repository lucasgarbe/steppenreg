<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('event.gender_categories', [
            [
                'key' => 'flinta',
                'color' => '#a855f7',
                'registration_opens_at' => null,
                'translations' => [
                    'en' => ['label' => 'FLINTA*'],
                    'de' => ['label' => 'FLINTA*'],
                ],
                'sort_order' => 1,
            ],
            [
                'key' => 'all_gender',
                'color' => '#3b82f6',
                'registration_opens_at' => null,
                'translations' => [
                    'en' => ['label' => 'All Gender'],
                    'de' => ['label' => 'Alle Geschlechter'],
                ],
                'sort_order' => 2,
            ],
        ]);
    }
};
