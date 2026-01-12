<?php

return [
    // Navigation
    'navigation' => [
        'registrations' => 'Registrations',
        'teams' => 'Teams',
        'mail_templates' => 'Mail Templates',
        'mail_logs' => 'Mail Logs',
        'dashboard' => 'Dashboard',
        'manage_event' => 'Manage Event',
    ],

    // Registration management
    'registrations' => [
        'title' => 'Registrations',
        'single' => 'Registration',
        'create' => 'Create Registration',
        'edit' => 'Edit Registration',
        'view' => 'View Registration',
        'delete' => 'Delete Registration',

        // Columns
        'columns' => [
            'start_number' => 'Start #',
            'name' => 'Name',
            'email' => 'Email',
            'age' => 'Age',
            'gender' => 'Gender',
            'track' => 'Track',
            'team' => 'Team',
            'draw_status' => 'Draw Status',
            'finish_time' => 'Finish Time',
            'status' => 'Status',
            'notes' => 'Notes',
            'created_at' => 'Registered At',
        ],

        // Actions
        'actions' => [
            'edit' => 'Edit',
            'promote_from_waitlist' => 'Promote from Waitlist',
            'add_to_waitlist' => 'Add to Waitlist',
            'manual_withdraw' => 'Withdraw',
            'send_withdrawal_link' => 'Send Withdrawal Link',
            'send_draw_results' => 'Send Draw Results',
            'mark_as_paid' => 'Mark as Paid',
            'mark_as_starting' => 'Mark as Starting',
            'mark_as_drawn' => 'Mark as Drawn',
            'mark_as_waitlist' => 'Mark as Waitlist',
            'mark_as_not_drawn' => 'Mark as Not Drawn',
            'assign_starting_numbers' => 'Assign Starting Numbers',
            'generate_waitlist_tokens' => 'Generate Waitlist Links',
            'generate_withdraw_tokens' => 'Generate Withdraw Links',
            'send_draw_notifications' => 'Send Draw Result Emails',
        ],

        // Confirmations
        'confirmations' => [
            'promote_from_waitlist' => 'Are you sure you want to promote :name from waitlist to drawn status?',
            'add_to_waitlist' => 'Are you sure you want to add :name to the waitlist?',
            'manual_withdraw' => 'Are you sure you want to manually withdraw :name from the event?',
        ],

        // Notifications
        'notifications' => [
            'promotion_completed' => 'Promotion Completed',
            'promoted_from_waitlist' => 'Promoted :name from waitlist to drawn status',
            'promoted_with_starting_number' => 'Promoted :name from waitlist to drawn status (Starting #:number)',
            'withdrawal_completed' => 'Withdrawal Completed',
            'withdrew_and_promoted' => 'Withdrew :withdrawn and promoted :promoted from waitlist',
            'withdrew_no_promotion' => 'Withdrew :name (no waitlisted participants to promote)',
            'added_to_waitlist' => 'Added to Waitlist',
            'added_to_waitlist_body' => 'Added :name to the waitlist',
            'withdrawal_link_sent' => 'Withdrawal Link Sent',
            'withdrawal_link_sent_body' => 'Sent withdrawal link to :email',
            'draw_results_sent' => 'Draw Results Sent',
            'draw_results_sent_body' => 'Sent draw results email to :email',
            'starting_numbers_assigned' => 'Starting numbers assigned',
            'starting_numbers_result' => 'Assigned: :assigned, Failed: :failed',
            'waitlist_tokens_generated' => 'Waitlist tokens generated',
            'waitlist_tokens_result' => 'Generated :count waitlist links for eligible registrations',
            'withdrawal_tokens_generated' => 'Withdrawal tokens generated',
            'withdrawal_tokens_result' => 'Generated :count withdrawal links for drawn registrations',
            'draw_notifications_queued' => 'Draw notification emails queued',
            'draw_notifications_result' => 'Sent :count draw result emails to queue for processing',
        ],

        // Filters
        'filters' => [
            'track' => 'Track',
            'select_track' => 'Select Track',
            'all_tracks' => 'All tracks',
            'paid_only' => 'Paid Only',
            'starting_only' => 'Starting Only',
            'finished_only' => 'Finished Only',
            'team_members_only' => 'Team Members Only',
            'individual_registrations' => 'Individual Registrations',
            'drawn_only' => 'Drawn Only',
            'not_drawn_only' => 'Not Drawn Only',
            'status' => 'Status',
            'select_status' => 'Select Status',
            'all_statuses' => 'All statuses',
        ],

        // Status values
        'status' => [
            'registered' => 'Registered',
            'waitlist' => 'Waitlist',
            'drawn' => 'Drawn',
            'paid' => 'Paid',
            'starting' => 'Starting',
            'finished' => 'Finished',
        ],

        // Draw status
        'draw_status' => [
            'not_drawn' => 'Not Drawn',
            'drawn' => 'Drawn',
            'waitlist' => 'Waitlist',
            'withdrawn' => 'Withdrawn',
        ],
    ],

    // Teams
    'teams' => [
        'title' => 'Teams',
        'single' => 'Team',
        'create' => 'Create Team',
        'edit' => 'Edit Team',
        'view' => 'View Team',
        'delete' => 'Delete Team',

        'columns' => [
            'name' => 'Team Name',
            'track' => 'Track',
            'members_count' => 'Members',
            'max_members' => 'Max Members',
            'created_at' => 'Created At',
        ],
    ],

    // Dashboard widgets
    'widgets' => [
        'registration_stats' => 'Registration Statistics',
        'team_stats' => 'Team Statistics',
        'daily_registrations' => 'Daily Registrations',
        'registration_timeline_by_gender' => 'Registration Timeline by Gender',
        'registration_timeline_by_track' => 'Registration Timeline by Track',

        'stats' => [
            'total_registrations' => 'Total Registrations',
            'drawn_registrations' => 'Drawn Registrations',
            'waitlisted_registrations' => 'Waitlisted',
            'paid_registrations' => 'Paid Registrations',
            'starting_registrations' => 'Starting',
            'finished_registrations' => 'Finished',
            'total_teams' => 'Total Teams',
            'full_teams' => 'Full Teams',
        ],
    ],

    // General form labels
    'form' => [
        'name' => 'Name',
        'email' => 'Email Address',
        'age' => 'Age',
        'gender' => 'Gender',
        'track' => 'Track',
        'team' => 'Team',
        'notes' => 'Notes',
        'paid' => 'Paid',
        'starting' => 'Starting',
        'finish_time' => 'Finish Time',
        'starting_number' => 'Starting Number',

        'placeholders' => [
            'not_specified' => 'Not specified',
            'not_finished' => 'Not finished',
            'individual' => 'Individual',
            'no_track_selected' => 'No track selected',
            'no_notes' => 'No notes',
        ],

        'tooltips' => [
            'has_notes' => 'This registration has notes',
        ],
    ],
];
