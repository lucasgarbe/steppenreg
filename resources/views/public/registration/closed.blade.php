<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Closed - {{ $eventSettings->event_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'])
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <!-- Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>

            <!-- Header -->
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Registration Closed</h1>
            
            <!-- Message based on state -->
            <div class="text-gray-600 space-y-4">
                @if($state === 'closed')
                    <p class="text-lg">Registration for {{ $eventSettings->event_name }} is currently closed.</p>
                    <p>Please check back later or contact the event organizers for more information.</p>
                @elseif($state === 'closed_waitlist')
                    <p class="text-lg">Registration for {{ $eventSettings->event_name }} is currently closed.</p>
                    <p>Waitlist management is handled through personalized email notifications to registered participants.</p>
                    <p class="text-sm text-gray-500 mt-4">If you have received a waitlist invitation email, please use the link provided in that message.</p>
                @endif
            </div>

            <!-- Event Details -->
            <div class="mt-8 bg-gray-50 rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Event Information</h2>
                <div class="text-left space-y-3">
                    <div>
                        <span class="font-medium text-gray-600">Event:</span>
                        <span class="text-gray-900">{{ $eventSettings->event_name }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Status:</span>
                        <span class="inline-flex px-3 py-1 rounded-full text-sm bg-red-100 text-red-800">
                            {{ $eventSettings->getApplicationStateLabel() }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Back to home -->
            <div class="mt-8">
                <a href="{{ url('/') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>