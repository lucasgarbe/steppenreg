<?php

return [
    // Navigation
    'navigation' => [
        'registrations' => 'Anmeldungen',
        'teams' => 'Teams',
        'mail_templates' => 'E-Mail-Vorlagen',
        'mail_logs' => 'E-Mail-Protokolle',
        'dashboard' => 'Dashboard',
        'manage_event' => 'Event verwalten',
    ],

    // Registration management
    'registrations' => [
        'title' => 'Anmeldungen',
        'single' => 'Anmeldung',
        'create' => 'Anmeldung erstellen',
        'edit' => 'Anmeldung bearbeiten',
        'view' => 'Anmeldung anzeigen',
        'delete' => 'Anmeldung löschen',

        // Columns
        'columns' => [
            'start_number' => 'Start #',
            'name' => 'Name',
            'email' => 'E-Mail',
            'age' => 'Alter',
            'gender' => 'Gender',
            'track' => 'Strecke',
            'team' => 'Team',
            'draw_status' => 'Auslosungsstatus',
            'finish_time' => 'Zielzeit',
            'status' => 'Status',
            'notes' => 'Notizen',
            'created_at' => 'Angemeldet am',
        ],

        // Actions
        'actions' => [
            'edit' => 'Bearbeiten',
            'promote_from_waitlist' => 'Von Warteliste befördern',
            'add_to_waitlist' => 'Zur Warteliste hinzufügen',
            'manual_withdraw' => 'Zurückziehen',
            'send_withdrawal_link' => 'Rückzugslink senden',
            'send_draw_results' => 'Auslosungsergebnis senden',
            'mark_as_paid' => 'Als bezahlt markieren',
            'mark_as_starting' => 'Als startend markieren',
            'mark_as_drawn' => 'Als gelost markieren',
            'mark_as_waitlist' => 'Als Warteliste markieren',
            'mark_as_not_drawn' => 'Als nicht gelost markieren',
            'assign_starting_numbers' => 'Startnummern zuweisen',
            'generate_waitlist_tokens' => 'Wartelisten-Links erstellen',
            'generate_withdraw_tokens' => 'Rückzugs-Links erstellen',
            'send_draw_notifications' => 'Auslosungsbenachrichtigungen senden',
        ],

        // Confirmations
        'confirmations' => [
            'promote_from_waitlist' => 'Sind Sie sicher, dass Sie :name von der Warteliste zur Auslosung befördern möchten?',
            'add_to_waitlist' => 'Sind Sie sicher, dass Sie :name zur Warteliste hinzufügen möchten?',
            'manual_withdraw' => 'Sind Sie sicher, dass Sie :name manuell vom Event zurückziehen möchten?',
        ],

        // Notifications
        'notifications' => [
            'promotion_completed' => 'Beförderung abgeschlossen',
            'promoted_from_waitlist' => ':name wurde von der Warteliste zur Auslosung befördert',
            'promoted_with_starting_number' => ':name wurde von der Warteliste zur Auslosung befördert (Startnummer #:number)',
            'withdrawal_completed' => 'Rückzug abgeschlossen',
            'withdrew_and_promoted' => ':withdrawn wurde zurückgezogen und :promoted von der Warteliste befördert',
            'withdrew_no_promotion' => ':name wurde zurückgezogen (keine wartenden Teilnehmer:innen zum Befördern)',
            'added_to_waitlist' => 'Zur Warteliste hinzugefügt',
            'added_to_waitlist_body' => ':name wurde zur Warteliste hinzugefügt',
            'withdrawal_link_sent' => 'Rückzugslink gesendet',
            'withdrawal_link_sent_body' => 'Rückzugslink an :email gesendet',
            'draw_results_sent' => 'Auslosungsergebnis gesendet',
            'draw_results_sent_body' => 'Auslosungsergebnis-E-Mail an :email gesendet',
            'starting_numbers_assigned' => 'Startnummern zugewiesen',
            'starting_numbers_result' => 'Zugewiesen: :assigned, Fehlgeschlagen: :failed',
            'waitlist_tokens_generated' => 'Wartelisten-Token erstellt',
            'waitlist_tokens_result' => ':count Wartelisten-Links für berechtigte Anmeldungen erstellt',
            'withdrawal_tokens_generated' => 'Rückzugs-Token erstellt',
            'withdrawal_tokens_result' => ':count Rückzugs-Links für geloste Anmeldungen erstellt',
            'draw_notifications_queued' => 'Auslosungsbenachrichtigungen in Warteschlange',
            'draw_notifications_result' => ':count Auslosungsbenachrichtigungen zur Verarbeitung in die Warteschlange eingereiht',
        ],

        // Filters
        'filters' => [
            'track' => 'Strecke',
            'select_track' => 'Strecke auswählen',
            'all_tracks' => 'Alle Strecken',
            'paid_only' => 'Nur Bezahlte',
            'starting_only' => 'Nur Startende',
            'finished_only' => 'Nur Fertige',
            'team_members_only' => 'Nur Team-Mitglieder',
            'individual_registrations' => 'Einzelanmeldungen',
            'drawn_only' => 'Nur Geloste',
            'not_drawn_only' => 'Nur Nicht-Geloste',
            'status' => 'Status',
            'select_status' => 'Status auswählen',
            'all_statuses' => 'Alle Status',
        ],

        // Status values
        'status' => [
            'registered' => 'Angemeldet',
            'waitlist' => 'Warteliste',
            'drawn' => 'Gelost',
            'paid' => 'Bezahlt',
            'starting' => 'Am Start',
            'finished' => 'Fertig',
        ],

        // Draw status
        'draw_status' => [
            'not_drawn' => 'Nicht gelost',
            'drawn' => 'Gelost',
            'waitlist' => 'Warteliste',
            'withdrawn' => 'Zurückgezogen',
        ],
    ],

    // Teams
    'teams' => [
        'title' => 'Teams',
        'single' => 'Team',
        'create' => 'Team erstellen',
        'edit' => 'Team bearbeiten',
        'view' => 'Team anzeigen',
        'delete' => 'Team löschen',

        'columns' => [
            'name' => 'Team-Name',
            'track' => 'Strecke',
            'members_count' => 'Mitglieder',
            'max_members' => 'Max. Mitglieder',
            'created_at' => 'Erstellt am',
        ],
    ],

    // Dashboard widgets
    'widgets' => [
        'registration_stats' => 'Anmeldungsstatistiken',
        'team_stats' => 'Team-Statistiken',
        'daily_registrations' => 'Tägliche Anmeldungen',
        'registration_timeline_by_gender' => 'Anmeldungsverlauf nach Gender',
        'registration_timeline_by_track' => 'Anmeldungsverlauf nach Strecke',

        'stats' => [
            'total_registrations' => 'Gesamtanmeldungen',
            'drawn_registrations' => 'Geloste Anmeldungen',
            'waitlisted_registrations' => 'Warteliste',
            'paid_registrations' => 'Bezahlte Anmeldungen',
            'starting_registrations' => 'Startende',
            'finished_registrations' => 'Fertige',
            'total_teams' => 'Teams Gesamt',
            'full_teams' => 'Volle Teams',
        ],
    ],

    // General form labels
    'form' => [
        'name' => 'Name',
        'email' => 'E-Mail-Adresse',
        'age' => 'Alter',
        'gender' => 'Gender',
        'track' => 'Strecke',
        'team' => 'Team',
        'notes' => 'Notizen',
        'paid' => 'Bezahlt',
        'starting' => 'Am Start',
        'finish_time' => 'Zielzeit',
        'starting_number' => 'Startnummer',

        'placeholders' => [
            'not_specified' => 'Nicht angegeben',
            'not_finished' => 'Nicht fertig',
            'individual' => 'Einzeln',
            'no_track_selected' => 'Keine Strecke ausgewählt',
            'no_notes' => 'Keine Notizen',
        ],

        'tooltips' => [
            'has_notes' => 'Diese Anmeldung hat Notizen',
        ],
    ],
];
