<?php

use App\Settings\EventSettings;

if (! function_exists('track_label')) {
    /**
     * Get custom track label or fall back to translation
     *
     * @param  bool  $plural  Whether to return plural form
     */
    function track_label(bool $plural = false): string
    {
        return app(EventSettings::class)->getTrackLabel($plural);
    }
}
