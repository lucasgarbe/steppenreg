<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the application.
    |
    */
    'features' => [
        /*
         * Starting Numbers
         *
         * When enabled, registrations that are drawn will automatically
         * receive a starting number. Disable this to run events without
         * starting number assignment.
         */
        'starting_numbers' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),
    ],
];
