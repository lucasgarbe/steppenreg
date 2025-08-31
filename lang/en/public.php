<?php

return [
    // Registration form
    'registration' => [
        'title' => 'Registration',
        'subtitle' => 'You can register here',
        'form_title' => 'Registration Form',
        'personal_information' => 'Personal Information',
        'event_information' => 'Event Information',
        'submit' => 'Submit Registration',
        'submitting' => 'Submitting...',

        'fields' => [
            'name' => 'Full Name',
            'name_placeholder' => 'Your full name',
            'email' => 'Email Address',
            'email_placeholder' => 'your@email.com',
            'age' => 'Age',
            'age_placeholder' => 'Your age in years',
            'participation_count' => 'Did you ride BAB in the past?',
            'participation_count_placeholder' => 'Did you ride BAB in the past?',
            'participation_count_help' => 'Just out of curiosity. How many times have you participated in this event before?',
            'participation_count_options' => [
                'first_time' => 'Not yet',
                'second_time' => '1',
                'third_time' => '2',
                'fourth_time' => '3',
            ],
            'gender' => 'Gender',
            'gender_placeholder' => 'Select gender',
            'track' => 'Track',
            'track_placeholder' => 'Select track',
            'team_option' => 'Team Option',
            'team' => 'Team (Optional)',
            'team_placeholder' => 'Enter team name (leave empty to register individually)',
            'team_help' => 'Enter a team name to join an existing team or create a new one. Your teammates should enter the exact same team name.',
            'notes' => 'Notes',
            'notes_placeholder' => 'Additional information or notes (optional)',
        ],

        'flinta_notice' => [
            'title' => 'FLINTA* Registration Open',
            'message' => 'Currently only open for FLINTA* participants.',
        ],

        'team_options' => [
            'individual' => 'Individual registration',
            'join_team' => 'Join existing team',
            'create_team' => 'Create new team',
        ],

        'gender_options' => [
            'flinta' => 'FLINTA*',
            'all_gender' => 'All Gender',
        ],

        'success' => [
            'title' => 'Registration Successful!',
            'message' => 'Your registration has been successfully submitted.',
            'confirmation_sent' => 'You will receive a confirmation email shortly.',
            'what_next' => 'What\'s next?',
            'wait_for_draw' => 'Wait for the draw - we\'ll notify you by email about the results.',
            'back_to_home' => 'Back to Home',
        ],

        'errors' => [
            'validation_failed' => 'Please check your input.',
            'submission_failed' => 'Registration failed. Please try again.',
            'track_required' => 'Please select a track.',
            'name_required' => 'Name is required.',
            'email_required' => 'Email address is required.',
            'email_invalid' => 'Please enter a valid email address.',
            'age_required' => 'Age is required.',
            'age_min' => 'You must be at least :min years old.',
            'team_full' => 'The selected team is already full.',
        ],
    ],

    // Waitlist
    'waitlist' => [
        'join_title' => 'Join Waitlist',
        'join_subtitle' => 'Join the waitlist to get a chance for a spot',
        'join_button' => 'Join Waitlist',
        'joining' => 'Joining...',

        'success' => [
            'title' => 'Joined Waitlist!',
            'message' => 'You have successfully joined the waitlist.',
            'position_info' => 'Your waitlist position will be communicated via email.',
            'notification_info' => 'You will be notified if a spot becomes available.',
        ],

        'already_processed' => [
            'title' => 'Already Processed',
            'message' => 'This waitlist link has already been used or expired.',
            'contact_info' => 'If you have questions, please contact us.',
        ],

        'invalid_token' => [
            'title' => 'Invalid Link',
            'message' => 'This waitlist link is invalid or expired.',
            'contact_info' => 'If you need help, please contact us.',
        ],

        'closed' => [
            'title' => 'Waitlist Closed',
            'message' => 'The waitlist for this event is currently closed.',
            'info' => 'Registration is no longer possible.',
        ],
    ],

    // Withdrawal
    'withdrawal' => [
        'title' => 'Withdraw from Event',
        'subtitle' => 'Withdraw your registration from the event',
        'confirmation_required' => 'Confirmation Required',
        'warning' => 'Warning: This action cannot be undone.',
        'participant_info' => 'Participant: :name',
        'track_info' => 'Track: :track',
        'starting_number_info' => 'Starting Number: :number',
        'withdraw_button' => 'Withdraw Registration',
        'withdrawing' => 'Withdrawing...',
        'cancel_button' => 'Cancel',

        'reasons' => [
            'title' => 'Reason for withdrawal (optional)',
            'injury' => 'Injury',
            'illness' => 'Illness',
            'personal' => 'Personal reasons',
            'schedule_conflict' => 'Schedule conflict',
            'other' => 'Other',
        ],

        'success' => [
            'title' => 'Withdrawal Successful',
            'message' => 'You have successfully withdrawn from the event.',
            'next_participant_info' => 'The next participant on the waitlist has been notified.',
            'refund_info' => 'You will receive refund information via email.',
        ],

        'already_processed' => [
            'title' => 'Already Processed',
            'message' => 'This withdrawal link has already been used or expired.',
            'contact_info' => 'If you have questions, please contact us.',
        ],

        'invalid_token' => [
            'title' => 'Invalid Link',
            'message' => 'This withdrawal link is invalid or expired.',
            'contact_info' => 'If you need help, please contact us.',
        ],
    ],

    // General messages
    'event_closed' => [
        'title' => 'Registration Closed',
        'message' => 'Registration for this event is currently closed.',
        'info' => 'Registrations are currently not possible.',
        'help' => 'Need Help?',
        'contact' => 'Contact us:',
        'click_to_reveal_email' => 'Click to reveal email address',
        'click_to_send_email' => 'Click to send email',
        'email_subject' => 'Question about :event',
        'not_available' => 'Registration is not currently available.',
    ],

    'waitlist_closed' => [
        'message' => 'Registration for :event is currently closed.',
        'info' => 'Waitlist management is handled through personalized email notifications to registered participants.',
        'email_instruction' => 'If you have received a waitlist invitation email, please use the link provided in that message.',
    ],

    'maintenance' => [
        'title' => 'Maintenance Mode',
        'message' => 'The registration system is currently under maintenance.',
        'info' => 'Please try again later.',
    ],

    // Navigation
    'navigation' => [
        'home' => 'Home',
        'register' => 'Register',
        'about' => 'About',
        'contact' => 'Contact',
        'language' => 'Language',
    ],

    // Footer
    'footer' => [
        'contact' => 'Contact',
        'privacy' => 'Privacy',
        'terms' => 'Terms of Service',
        'imprint' => 'Imprint',
    ],
];
