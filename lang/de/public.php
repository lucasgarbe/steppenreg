<?php

return [
    // Registration form
    'registration' => [
        'title' => 'Anmeldung',
        'subtitle' => 'Hier kannst du dich anmelden',
        'form_title' => 'Anmeldeformular',
        'personal_information' => 'Persönliche Informationen',
        'event_information' => 'Event-Informationen',
        'submit' => 'Anmeldung abschicken',
        'submitting' => 'Wird gesendet...',

        'fields' => [
            'name' => 'Vollständiger Name',
            'name_placeholder' => 'Dein vollständiger Name',
            'email' => 'E-Mail-Adresse',
            'email_placeholder' => 'deine@email.de',
            'age' => 'Alter',
            'age_placeholder' => 'Dein Alter in Jahren',
            'participation_count' => 'Bist du schonmal beim :event mitgefahren?',
            'participation_count_placeholder' => 'Bist du schonmal beim :event mitgefahren?',
            'participation_count_help' => 'Nur aus Interesse. Wie oft hast du schon an diesem Event teilgenommen?',
            'participation_count_options' => [
                'first_time' => 'Noch nie',
                'second_time' => '1',
                'third_time' => '2',
                'fourth_time' => '3',
            ],
            'gender' => 'Gender',
            'gender_placeholder' => 'Gender auswählen',
            'track' => 'Strecke',
            'track_placeholder' => 'Strecke auswählen',
            'team_option' => 'Rudel-Option',
            'team' => 'Rudel/Team (Optional)',
            'team_placeholder' => 'Rudelnamen eingeben (leer lassen für Einzelanmeldung)',
            'team_help' => 'Gib einen Rudelnamen ein, um einem bestehenden Rudel beizutreten oder ein neues zu erstellen. Achtet bitte darauf, dass alle im Rudel den gleichen Name eingeben',
            'notes' => 'Anmerkungen',
            'notes_placeholder' => 'Zusätzliche Informationen oder Anmerkungen (optional)',
        ],

        'flinta_notice' => [
            'title' => 'FLINTA* Anmeldung geöffnet',
            'message' => 'Derzeit nur für FLINTA* Teilnehmer*innen geöffnet.',
        ],

        'team_options' => [
            'individual' => 'Einzelanmeldung',
            'join_team' => 'Bestehendem Team beitreten',
            'create_team' => 'Neues Team erstellen',
        ],

        'gender_options' => [
            'flinta' => 'FLINTA*',
            'all_gender' => 'All Gender',
        ],

        'success' => [
            'title' => 'Anmeldung erfolgreich!',
            'message' => 'Jawollo! Deine Anmeldung ist bei uns eingegangen.',
            'confirmation_sent' => 'Du erhältst in Kürze eine Bestätigung per Mail.',
            'what_next' => 'Wie geht es weiter?',
            'wait_for_draw' => 'Warte auf die Auslosung - wir informieren dich per E-Mail über das Ergebnis.',
            'mail' => 'Bei Fragen kannst du uns schreiben',
            'back_to_home' => 'Zur Hauptseite',
        ],

        'errors' => [
            'validation_failed' => 'Bitte überprüfe deine Eingaben.',
            'submission_failed' => 'Anmeldung fehlgeschlagen. Bitte versuche es erneut.',
            'track_required' => 'Bitte wähle eine Strecke aus.',
            'name_required' => 'Name ist erforderlich.',
            'email_required' => 'E-Mail-Adresse ist erforderlich.',
            'email_invalid' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'age_required' => 'Alter ist erforderlich.',
            'age_min' => 'Du musst mindestens :min Jahre alt sein.',
            'team_full' => 'Das gewählte Team ist bereits voll.',
        ],
    ],

    // Waitlist
    'waitlist' => [
        'join_title' => 'Der Warteliste beitreten',
        'join_subtitle' => 'Tritt der Warteliste bei, um eine Chance auf einen Platz zu erhalten',
        'join_button' => 'Warteliste beitreten',
        'joining' => 'Trete bei...',

        'success' => [
            'title' => 'Warteliste beigetreten!',
            'message' => 'Du bist der Warteliste erfolgreich beigetreten.',
            'position_info' => 'Deine Position auf der Warteliste wird dir per E-Mail mitgeteilt.',
            'notification_info' => 'Du wirst benachrichtigt, falls ein Platz frei wird.',
        ],

        'already_processed' => [
            'title' => 'Bereits verarbeitet',
            'message' => 'Dieser Wartelisten-Link wurde bereits verwendet oder ist abgelaufen.',
            'contact_info' => 'Falls du Fragen hast, kontaktiere uns bitte.',
        ],

        'invalid_token' => [
            'title' => 'Ungültiger Link',
            'message' => 'Dieser Wartelisten-Link ist ungültig oder abgelaufen.',
            'contact_info' => 'Falls du Hilfe benötigst, kontaktiere uns bitte.',
        ],

        'closed' => [
            'title' => 'Warteliste geschlossen',
            'message' => 'Die Warteliste für dieses Event ist derzeit geschlossen.',
            'info' => 'Anmeldungen sind nicht mehr möglich.',
        ],
    ],

    // Withdrawal
    'withdrawal' => [
        'title' => 'Deinen Platz weitergeben',
        'subtitle' => 'Gebe deinen Platz frei, damit eine andere Person teilnehmen kann',
        'confirmation_required' => 'Bestätigung erforderlich',
        'warning' => 'Warnung: Diese Aktion kann nicht rückgängig gemacht werden.',
        'participant_info' => 'Teilnehmer*in: :name',
        'track_info' => 'Strecke: :track',
        'starting_number_info' => 'Startnummer: :number',
        'withdraw_button' => 'Platz weitergeben',
        'withdrawing' => 'Wird verarbeitet...',
        'cancel_button' => 'Abbrechen',

        'reasons' => [
            'title' => 'Grund für die Abgabe deines Platzes (optional)',
            'injury' => 'Verletzung',
            'illness' => 'Krankheit',
            'personal' => 'Persönliche Gründe',
            'schedule_conflict' => 'Terminkonflikt',
            'other' => 'Andere',
        ],

        'success' => [
            'title' => 'Dein Platz wurde erfolgreich abgegeben',
            'message' => 'Du hast deinen Platz erfolgreich weitergegeben.',
            'next_participant_info' => 'Dein Platz wird an eine neue Person verlost',
            'refund_info' => 'Informationen zur Rückerstattung erhältst du per E-Mail.',
        ],

        'already_processed' => [
            'title' => 'Bereits verarbeitet',
            'message' => 'Dieser Link wurde bereits verwendet oder ist abgelaufen.',
            'contact_info' => 'Falls du Fragen hast, kontaktiere uns bitte.',
        ],

        'invalid_token' => [
            'title' => 'Ungültiger Link',
            'message' => 'Dieser Link ist ungültig oder abgelaufen.',
            'contact_info' => 'Falls du Hilfe benötigst, kontaktiere uns bitte.',
        ],
    ],

    // General messages
    'event_closed' => [
        'title' => 'Anmeldung geschlossen',
        'message' => 'Die Anmeldung für dieses Event ist derzeit geschlossen.',
        'info' => 'Anmeldungen sind aktuell nicht möglich.',
        'help' => 'Brauchst du Hilfe?',
        'contact' => 'Kontaktiere uns:',
        'click_to_reveal_email' => 'Klicken um E-Mail-Adresse anzuzeigen',
        'click_to_send_email' => 'Klicken um E-Mail zu senden',
        'email_subject' => 'Frage zu :event',
        'not_available' => 'Anmeldung ist derzeit nicht verfügbar.',
    ],

    'waitlist_closed' => [
        'message' => 'Die Anmeldung für :event ist derzeit geschlossen.',
        'info' => 'Die Wartelisten-Verwaltung erfolgt über personalisierte E-Mail-Benachrichtigungen an registrierte Teilnehmer*innen.',
        'email_instruction' => 'Falls du eine Wartelisten-Einladung per E-Mail erhalten hast, verwende bitte den Link in dieser Nachricht.',
    ],

    'maintenance' => [
        'title' => 'Wartungsmodus',
        'message' => 'Das Anmeldesystem wird gerade gewartet.',
        'info' => 'Bitte versuche es später erneut.',
    ],

    // Navigation
    'navigation' => [
        'home' => 'Startseite',
        'register' => 'Anmelden',
        'about' => 'Über uns',
        'contact' => 'Kontakt',
        'language' => 'Sprache',
    ],

    // Footer
    'footer' => [
        'contact' => 'Kontakt',
        'privacy' => 'Datenschutz',
        'terms' => 'Nutzungsbedingungen',
        'imprint' => 'Impressum',
    ],
];
