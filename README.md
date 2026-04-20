# Steppenreg

Event registration and participant management system built with Laravel 12 and Filament 4. Designed for sporting events with limited capacity — handles public registration, team management, lottery-based participant selection, and starting number assignment.

> [!WARNING]
> Currently in active development, but already powering live events.

## Features

- **Multi-phase registration** — Priority periods for specific groups, automatic state transitions based on configured schedules
- **Team registration** — Teams are treated as atomic units throughout the system, including the lottery draw
- **Lottery draw system** — Randomized selection when registrations exceed available spots, with full audit trail
- **Starting number assignment** — Per-track number ranges with reserved ranges for waitlisted participants
- **Template-based email system** — Admin-configurable mail templates with variable placeholders, queued delivery with rate limiting and retry logic
- **Custom registration questions** — Admins can define arbitrary questions (text, number, select, checkbox, etc.) per event
- **Filament 4 admin panel** — Dashboard with registration statistics, state transition monitoring, and full CRUD for all entities
- **Multi-language support** — German and English built in, extensible for additional locales

## Tech Stack

| | |
| --- | --- |
| **Backend** | Laravel 12, PHP 8.4 |
| **Admin Panel** | Filament 4, Spatie Laravel Settings |
| **Database** | PostgreSQL |
| **Frontend** | Blade, Vite |
| **Infrastructure** | Docker (Laravel Sail) |
| **Testing** | PHPUnit |

## Quick Start

```bash
git clone https://github.com/lucasgarbe/steppenreg.git
cd steppenreg
cp .env.example .env

# Install dependencies
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs

# Start and initialize
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
./vendor/bin/sail artisan make:filament-user
```

- **Public site**: http://localhost
- **Admin panel**: http://localhost/admin

## Architecture

The application uses a domain-driven structure for its most complex features. The `app/Domain/` directory contains bounded contexts — `Draw/` and `StartingNumber/` — each with their own models, services, events, and exceptions. This isolates business logic from the rest of the application and makes these modules independently testable.

Key design decisions:

- **Event-driven draw system** — The lottery draw dispatches events (`DrawExecuted`, `RegistrationDrawn`, `RegistrationNotDrawn`) that trigger notifications and side effects, keeping the core draw logic focused on selection
- **Settings-based configuration** — Tracks, gender categories, and custom questions are stored in the database via Spatie Laravel Settings, so the system adapts to each event without code changes
- **Observer-based team synchronization** — A `RegistrationObserver` keeps draw status in sync across all team members when one member's status changes, with a re-entrancy guard to prevent infinite loops

## Documentation

- [Configuration](CONFIGURATION.md) — Environment variables, mail setup, port conflicts
- [Deployment](docs/DEPLOYMENT.md) — Production setup, reverse proxy, scheduler, backups
- [Localization](docs/LOCALIZATION.md) — Translation setup and adding new languages
- [Email Rate Limiting](docs/EMAIL_RATE_LIMITING.md) — Queue configuration and retry strategy

## License

MIT
