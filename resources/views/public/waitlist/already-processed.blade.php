<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Already Processed - {{ $eventSettings->event_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'])
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <!-- Info Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-6">
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <!-- Header -->
            @if($action === 'waitlist')
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Already on Waitlist</h1>
                <p class="text-lg text-gray-600 mb-8">{{ $registration->name }}, you're already on the waitlist.</p>
            @else
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Already Withdrawn</h1>
                <p class="text-lg text-gray-600 mb-8">{{ $registration->name }}, you've already withdrawn from the event.</p>
            @endif

            <!-- Current Status -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Current Status</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Name:</span>
                        <span class="text-gray-900">{{ $registration->name }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Track:</span>
                        <span class="text-gray-900">{{ $registration->track_name }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Status:</span>
                        @if($registration->is_withdrawn)
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                                Withdrawn
                            </span>
                        @elseif($registration->draw_status === 'waitlist')
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">
                                Waitlist #{{ $registration->getWaitlistPosition() }}
                            </span>
                        @elseif($registration->draw_status === 'drawn')
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                Drawn
                            </span>
                        @else
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                                {{ ucfirst(str_replace('_', ' ', $registration->draw_status)) }}
                            </span>
                        @endif
                    </div>
                    <div>
                        @if($registration->is_withdrawn)
                            <span class="font-medium text-gray-600">Withdrawn:</span>
                            <span class="text-gray-900">{{ $registration->withdrawn_at->format('M j, Y') }}</span>
                        @elseif($registration->waitlist_registered_at)
                            <span class="font-medium text-gray-600">Waitlist Since:</span>
                            <span class="text-gray-900">{{ $registration->waitlist_registered_at->format('M j, Y') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action-specific message -->
            @if($action === 'waitlist' && $registration->draw_status === 'waitlist')
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold text-blue-800 mb-2">You're in good standing!</h3>
                    <p class="text-blue-700">
                        You're currently #{{ $registration->getWaitlistPosition() }} on the waitlist for {{ $registration->track_name }}. 
                        We'll email you if a spot becomes available.
                    </p>
                </div>
            @elseif($action === 'withdraw' && $registration->is_withdrawn)
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Withdrawal Complete</h3>
                    <p class="text-gray-700">
                        Your withdrawal was processed on {{ $registration->withdrawn_at->format('M j, Y') }}. 
                        Your spot has been released to help others participate.
                    </p>
                </div>
            @endif

            <!-- Actions -->
            <div class="flex justify-center">
                <a href="{{ url('/') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>