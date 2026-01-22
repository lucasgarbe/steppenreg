<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Starting Numbers Feature
    |--------------------------------------------------------------------------
    |
    | Enable or disable the automatic starting number assignment feature.
    | When enabled, registrations that are drawn will automatically receive
    | a starting number based on the configured ranges.
    |
    */
    'enabled' => env('STEPPENREG_STARTING_NUMBERS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Track Number Ranges
    |--------------------------------------------------------------------------
    |
    | Define starting number ranges for each track. The key should be the
    | track ID, and the value should be an array with 'start' and 'end'.
    |
    | This can be configured via the Settings Page UI or manually here.
    |
    | Example:
    | 'tracks' => [
    |     1 => [
    |         'name' => '50km Track',
    |         'start' => 1,
    |         'end' => 500,
    |     ],
    |     2 => [
    |         'name' => '100km Track',
    |         'start' => 501,
    |         'end' => 1000,
    |     ],
    | ],
    |
    */
    'tracks' => [],

    /*
    |--------------------------------------------------------------------------
    | Global Overflow Bucket
    |--------------------------------------------------------------------------
    |
    | When a track's number range is exhausted, numbers will be assigned from
    | this global overflow bucket. This ensures all drawn participants receive
    | a starting number even if their track range is full.
    |
    */
    'overflow' => [
        'enabled' => true,
        'start' => 9001,
        'end' => 9999,
    ],

    /*
    |--------------------------------------------------------------------------
    | Number Formatting
    |--------------------------------------------------------------------------
    |
    | Configure how starting numbers are formatted when displayed or assigned.
    |
    | - padding: Number of digits (with leading zeros)
    | - prefix: Text before the number (e.g., 'BIB-')
    | - suffix: Text after the number (e.g., '-A')
    |
    | Examples:
    | padding: 4 → 0001, 0042, 1234
    | prefix: 'BIB-', padding: 4 → BIB-0001
    | padding: 3, suffix: '-A' → 001-A
    |
    */
    'format' => [
        'padding' => 4,
        'prefix' => '',
        'suffix' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Assignment Strategy
    |--------------------------------------------------------------------------
    |
    | Determine how numbers are assigned from the available range:
    |
    | - sequential: Assign numbers in order (1, 2, 3, ...)
    | - random: Assign random numbers from the available range
    |
    */
    'strategy' => 'sequential',

    /*
    |--------------------------------------------------------------------------
    | Reserved Numbers
    |--------------------------------------------------------------------------
    |
    | Numbers that should never be assigned automatically.
    | Useful for excluding unlucky numbers or reserving specific numbers.
    |
    | Example: [1, 13, 666, 999]
    |
    */
    'reserved' => [],

    /*
    |--------------------------------------------------------------------------
    | Auto-Assignment
    |--------------------------------------------------------------------------
    |
    | Automatically assign numbers when registrations are drawn.
    | If disabled, numbers must be assigned manually.
    |
    */
    'auto_assign' => true,

    /*
    |--------------------------------------------------------------------------
    | Allow Manual Override
    |--------------------------------------------------------------------------
    |
    | Allow administrators to manually override assigned numbers.
    | Useful for special cases or VIP participants.
    |
    */
    'allow_manual_override' => true,
];
