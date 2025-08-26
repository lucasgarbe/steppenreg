<?php

return [
    // Registration form
    'registration' => [
        'title' => 'Event-Anmeldung',
        'subtitle' => 'Melde dich für unser Event an',
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
            'gender' => 'Geschlecht',
            'gender_placeholder' => 'Geschlecht auswählen',
            'track' => 'Strecke',
            'track_placeholder' => 'Strecke auswählen',
            'team_option' => 'Team-Option',
            'team' => 'Team (Optional)',
            'team_placeholder' => 'Teamnamen eingeben (leer lassen für Einzelanmeldung)',
            'team_help' => 'Geben Sie einen Teamnamen ein, um einem bestehenden Team beizutreten oder ein neues zu erstellen. Ihre Teammitglieder sollten exakt denselben Teamnamen eingeben.',
            'notes' => 'Anmerkungen',
            'notes_placeholder' => 'Zusätzliche Informationen oder Anmerkungen (optional)',
        ],
        
        'team_options' => [
            'individual' => 'Einzelanmeldung',
            'join_team' => 'Bestehendem Team beitreten',
            'create_team' => 'Neues Team erstellen',
        ],
        
        'gender_options' => [
            'flinta' => 'FLINTA*',
            'all_gender' => 'Alle Geschlechter',
        ],
        
        'success' => [
            'title' => 'Anmeldung erfolgreich!',
            'message' => 'Deine Anmeldung wurde erfolgreich übermittelt.',
            'confirmation_sent' => 'Du erhältst in Kürze eine Bestätigungs-E-Mail.',
            'what_next' => 'Wie geht es weiter?',
            'wait_for_draw' => 'Warte auf die Auslosung - wir informieren dich per E-Mail über das Ergebnis.',
            'back_to_home' => 'Zur Startseite',
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
        'title' => 'Vom Event zurückziehen',
        'subtitle' => 'Ziehe deine Anmeldung für das Event zurück',
        'confirmation_required' => 'Bestätigung erforderlich',
        'warning' => 'Warnung: Diese Aktion kann nicht rückgängig gemacht werden.',
        'participant_info' => 'Teilnehmer:in: :name',
        'track_info' => 'Strecke: :track',
        'starting_number_info' => 'Startnummer: :number',
        'withdraw_button' => 'Anmeldung zurückziehen',
        'withdrawing' => 'Wird zurückgezogen...',
        'cancel_button' => 'Abbrechen',
        
        'reasons' => [
            'title' => 'Grund für den Rückzug (optional)',
            'injury' => 'Verletzung',
            'illness' => 'Krankheit',
            'personal' => 'Persönliche Gründe',
            'schedule_conflict' => 'Terminkonflikt',
            'other' => 'Andere',
        ],
        
        'success' => [
            'title' => 'Rückzug erfolgreich',
            'message' => 'Du hast dich erfolgreich vom Event zurückgezogen.',
            'next_participant_info' => 'Der nächste Teilnehmer auf der Warteliste wurde benachrichtigt.',
            'refund_info' => 'Informationen zur Rückerstattung erhältst du per E-Mail.',
        ],
        
        'already_processed' => [
            'title' => 'Bereits verarbeitet',
            'message' => 'Dieser Rückzugs-Link wurde bereits verwendet oder ist abgelaufen.',
            'contact_info' => 'Falls du Fragen hast, kontaktiere uns bitte.',
        ],
        
        'invalid_token' => [
            'title' => 'Ungültiger Link',
            'message' => 'Dieser Rückzugs-Link ist ungültig oder abgelaufen.',
            'contact_info' => 'Falls du Hilfe benötigst, kontaktiere uns bitte.',
        ],
    ],
    
    // General messages
    'event_closed' => [
        'title' => 'Anmeldung geschlossen',
        'message' => 'Die Anmeldung für dieses Event ist derzeit geschlossen.',
        'info' => 'Neue Anmeldungen sind nicht mehr möglich.',
        'contact' => 'Bei Fragen wende dich an die Veranstalter.',
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