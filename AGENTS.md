# Agent Guidelines for Steppenreg

## Build/Test Commands
- Run all tests: `./vendor/bin/sail artisan test` or `composer test`
- Run single test: `./vendor/bin/sail artisan test --filter=TestName`
- Run specific test file: `./vendor/bin/sail artisan test tests/Feature/ExampleTest.php`
- Lint PHP: `./vendor/bin/sail composer pint` (Laravel Pint)
- Build assets: `./vendor/bin/sail npm run build`
- Dev server: `composer dev` (starts server, queue, logs, vite concurrently)

## Code Style
- **Framework**: Laravel 12 with Filament 4 admin panel
- **Indentation**: 4 spaces (not tabs), LF line endings
- **Imports**: Group by vendor, framework, app (PSR-4: `App\` namespace)
- **Types**: Use strict types (`declare(strict_types=1)` not required but preferred), type hints on all params/returns
- **Naming**: camelCase methods, StudlyCase classes, snake_case DB columns, descriptive names (e.g., `generateWaitlistToken()`)
- **Error handling**: Try-catch for external services, return `?Type` for nullable, use exceptions for exceptional cases
- **Models**: Use Eloquent relationships, scopes, accessors; define `$fillable`, `$casts`
- **Services**: Constructor property promotion with DI (e.g., `__construct(private MailVariableResolver $resolver)`)
- **Comments**: PHPDoc for public methods, inline comments for complex logic only
- **No emojis**: Professional codebase, avoid emojis in code/docs/commits
