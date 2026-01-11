<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from session, URL parameter, or use German as default
        $locale = $request->get('locale')
            ?? Session::get('locale')
            ?? 'de'; // Default to German

        // Validate locale
        $supportedLocales = ['de', 'en'];
        if (! in_array($locale, $supportedLocales)) {
            $locale = 'de'; // Default to German
        }

        // Set the application locale
        App::setLocale($locale);

        // Store in session for future requests
        Session::put('locale', $locale);

        return $next($request);
    }
}
