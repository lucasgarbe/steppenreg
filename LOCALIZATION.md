# Localization Guide

This application supports multiple languages with German as the primary language and English as fallback.

## Current Setup

- **Primary Language**: German (de)
- **Fallback Language**: English (en)
- **Timezone**: Europe/Berlin
- **Supported Locales**: Configured in `AppServiceProvider`

## File Structure

```
lang/
├── de/
│   ├── messages.php     # General application strings
│   ├── admin.php        # Admin interface strings  
│   └── public.php       # Public-facing strings
└── en/
    ├── messages.php     # General application strings
    ├── admin.php        # Admin interface strings
    └── public.php       # Public-facing strings
```

## Adding a New Language

### 1. Create Language Files

Create a new directory for your language code (e.g., `fr` for French):

```bash
mkdir lang/fr
```

Copy the English files as a template:

```bash
cp lang/en/*.php lang/fr/
```

### 2. Translate the Content

Edit the files in `lang/fr/` and translate all the strings:

```php
// lang/fr/messages.php
return [
    'welcome' => 'Bienvenue',
    'register' => 'S\'inscrire',
    // ... etc
];
```

### 3. Add Language to Configuration

Update `app/Providers/AppServiceProvider.php`:

```php
config([
    'app.supported_locales' => [
        'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪'],
        'en' => ['name' => 'English', 'flag' => '🇺🇸'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷'], // Add this line
    ]
]);
```

### 4. Update Middleware

Update `app/Http/Middleware/SetLocale.php`:

```php
$supportedLocales = ['de', 'en', 'fr']; // Add 'fr'
```

## Usage in Code

### In Blade Templates

```blade
{{ __('messages.welcome') }}
{{ __('admin.registrations.title') }}
{{ __('public.registration.form_title') }}
```

### In PHP Classes

```php
__('messages.success')
__('admin.notifications.saved')
```

### With Parameters

```php
__('admin.registrations.notifications.promoted_from_waitlist', ['name' => $record->name])
```

## Language Switching

The application includes a language switcher component that:

1. Shows available languages with flags
2. Maintains current URL with `?locale=de` parameter
3. Stores preference in session
4. Works on both public and admin pages

### Using the Language Switcher

```blade
<x-language-switcher />
```

## Configuration Files

### Laravel Config

- `config/app.php` - Main locale settings
- `bootstrap/app.php` - Middleware registration

### Environment Variables

You can override default settings in `.env`:

```env
APP_LOCALE=de
APP_FALLBACK_LOCALE=en
APP_TIMEZONE="Europe/Berlin"
APP_FAKER_LOCALE=de_DE
```

## Key Features

1. **Automatic Detection**: Locale from URL parameter, session, or default
2. **Fallback Support**: Falls back to English if translation missing
3. **Admin Integration**: Filament admin interface uses translations
4. **Model Integration**: Models return localized strings
5. **Validation**: Only allows configured locales
6. **Session Persistence**: Remembers user's language choice

## File Categories

### messages.php
General application strings used across the app

### admin.php
- Navigation labels
- Column headers
- Action labels
- Form fields
- Notifications
- Confirmations

### public.php
- Registration forms
- Success/error messages
- Waitlist pages
- Withdrawal forms
- Navigation elements

## Best Practices

1. **Consistent Keys**: Use descriptive, hierarchical keys
2. **Context**: Group related translations
3. **Parameters**: Use named parameters for dynamic content
4. **Fallbacks**: Always provide English translations
5. **Testing**: Test all languages thoroughly

## Future Extensions

To add more languages or features:

1. **RTL Support**: Add CSS for right-to-left languages
2. **Date Localization**: Use Carbon for localized dates
3. **Number Formatting**: Localize number/currency display
4. **Pluralization**: Use Laravel's pluralization features
5. **Database Content**: Consider translatable models for dynamic content

## Troubleshooting

### Translations Not Showing
1. Check if locale is set correctly: `app()->getLocale()`
2. Verify file exists: `lang/{locale}/{file}.php`
3. Clear config cache: `php artisan config:clear`
4. Check translation key exists and is correct

### Language Switcher Not Working
1. Verify middleware is registered
2. Check supported locales configuration
3. Ensure session is working properly

### Admin Interface Still in English
1. Filament uses system locale - verify middleware is applied
2. Check if admin translations exist in `lang/{locale}/admin.php`