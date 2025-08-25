<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Event - {{ $eventSettings->event_name }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('steppenwolf-logo_small-transparent-black.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('steppenwolf-logo_small-transparent-black.png') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'])
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <!-- Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
            </div>

            <!-- Header -->
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Event is Live!</h1>
            
            <!-- Message -->
            <div class="text-gray-600 space-y-4">
                <p class="text-lg">{{ $eventSettings->event_name }} is currently taking place.</p>
                <p>Registration is closed as the event is now live. For any questions or assistance, please contact the event staff on-site.</p>
            </div>

            <!-- Event Status -->
            <div class="mt-8 bg-green-50 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Event Status</h2>
                <div class="text-left space-y-3">
                    <div>
                        <span class="font-medium text-gray-600">Event:</span>
                        <span class="text-gray-900">{{ $eventSettings->event_name }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Status:</span>
                        <span class="inline-flex px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                            {{ $eventSettings->getApplicationStateLabel() }}
                        </span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Mode:</span>
                        <span class="text-gray-900">Event Management System Active</span>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="mt-8 text-sm text-gray-500">
                <p>Need help? Contact the event organizers on-site.</p>
            </div>
        </div>
    </div>
</body>
</html>