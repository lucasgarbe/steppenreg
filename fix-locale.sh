#!/bin/bash
echo "Fixing locale settings..."

# Unset any system environment variables that might override .env
unset APP_LOCALE
unset APP_FALLBACK_LOCALE  
unset APP_FAKER_LOCALE

echo "Clearing Laravel caches..."
php artisan optimize:clear

echo "Testing locale settings..."
php artisan tinker --execute "
echo 'Current locale: ' . app()->getLocale() . PHP_EOL;
echo 'German test: ' . __('messages.welcome') . PHP_EOL;
echo 'Admin test: ' . __('admin.navigation.registrations') . PHP_EOL;
"

echo "Locale fix completed!"
echo ""
echo "If you're still seeing English, restart your development server:"
echo "  php artisan serve"