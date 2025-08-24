<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waitlist Joined Successfully</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'])
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <!-- Header -->
            <h1 class="text-3xl font-bold text-gray-900 mb-4">You're on the Waitlist!</h1>
            <p class="text-lg text-gray-600 mb-8">{{ $registration->name }}, you've successfully joined the waitlist.</p>

            <!-- Position Info -->
            <div class="bg-blue-50 rounded-lg p-6 mb-8">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600 mb-2">#{{ $position }}</div>
                    <div class="text-sm text-blue-700">Your position on the waitlist for {{ $registration->track_name }}</div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8 text-left">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">What happens next?</h2>
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-2 w-2 bg-blue-500 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <p><strong>We'll monitor withdrawals:</strong> If someone withdraws from your track, spots will be offered to waitlist participants in order.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-2 w-2 bg-blue-500 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <p><strong>You'll get an email:</strong> If a spot opens up for you, we'll send you an email with instructions on how to claim it.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <div class="h-2 w-2 bg-blue-500 rounded-full"></div>
                        </div>
                        <div class="ml-3">
                            <p><strong>Time limit:</strong> You'll have 24-48 hours to accept a spot when offered.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Details -->
            <div class="border border-gray-200 rounded-lg p-4 mb-8">
                <h3 class="text-sm font-medium text-gray-800 mb-3">Your Registration Details</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Email:</span>
                        <div class="font-medium">{{ $registration->email }}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Track:</span>
                        <div class="font-medium">{{ $registration->track_name }}</div>
                    </div>
                    <div>
                        <span class="text-gray-500">Status:</span>
                        <div>
                            <span class="inline-flex px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">
                                Waitlist #{{ $position }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-500">Joined:</span>
                        <div class="font-medium">{{ $registration->waitlist_registered_at->format('M j, Y \a\t g:i A') }}</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <p class="text-sm text-gray-500">
                    Save this page or bookmark it to check your waitlist status anytime.
                </p>
                <div class="flex justify-center">
                    <a href="{{ url('/') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>