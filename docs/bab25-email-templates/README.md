# BAB 2025 Email Templates

Restored from database backup: `bab25_backup-2026_08_30-03_00_01.dump`

## Templates

| Key | Subject | File |
|-----|---------|------|
| `registration_confirmation` | `{{event_name}} - Deine Anmeldung ist erfasst` | [registration_confirmation.md](registration_confirmation.md) |
| `draw_success` | `{{event_name}} - Dein Los` | [draw_success.md](draw_success.md) |
| `draw_waitlist` | `{{event_name}} - Du wurdest über die Warteliste gezogen` | [draw_waitlist.md](draw_waitlist.md) |
| `draw_rejection` | `{{event_name}} - Dein Los` | [draw_rejection.md](draw_rejection.md) |
| `waitlist_registration_success` | `{{event_name}} - Du bist auf der Warteliste` | [waitlist_registration_success.md](waitlist_registration_success.md) |
| `withdrawal_confirmation` | `{{event_name}} - Du hast deinen Platz abgegeben` | [withdrawal_confirmation.md](withdrawal_confirmation.md) |

## Template Variables

| Variable | Description |
|----------|-------------|
| `{{name}}` | Participant name |
| `{{email}}` | Participant email |
| `{{track_name}}` | Track name (e.g., 300km, 200km, 150km) |
| `{{event_name}}` | Event name |
| `{{withdraw_url}}` | URL for withdrawing from the event |
| `{{waitlist_url}}` | URL for joining the waitlist |
| `{{#team_name}}...{{/team_name}}` | Conditional block for team name |

## Notes

- All templates are bilingual (German / English)
- Body format is Markdown
- Bank details for BAB 2025:
  ```
  Radsportverein Steppenwolf Berlin
  DE07 8306 5408 0005 4675 19
  Betreff: BAB 2025 - {{name}}
  ```
