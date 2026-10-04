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

        /*
         * Payment tracking
         *
         * When enabled, the fee payment state of registrations is tracked
         * in the Payment domain. Disable this for events without any payment.
         */
        'payment' => env('STEPPENREG_PAYMENT_ENABLED', true),

        /*
         * Bank transfer import
         *
         * When enabled, the admin panel offers a page to import bank statement
         * CSVs and confirm payments. Requires the payment feature.
         */
        'bank_import' => env('STEPPENREG_BANK_IMPORT_ENABLED', true),

        'mail' => env('STEPPENREG_MAIL_ENABLED', true),
    ],
];
