<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Confirmed</title>
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
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Withdrawal Confirmed</h1>
            <p class="text-lg text-gray-600 mb-8">{{ $registration->name }}, your withdrawal has been processed successfully.</p>

            <!-- What happened -->
            <div class="bg-blue-50 rounded-lg p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">What we've done:</h2>
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex items-center justify-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-2">
                            <p><strong>Released your spot</strong> from {{ $registration->track_name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-2">
                            <p><strong>Notified the next person</strong> on the waitlist (if available)</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-2">
                            <p><strong>Sent you a confirmation email</strong> with this information</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Final Details -->
            <div class="border border-gray-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Withdrawal Summary</h3>
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
                        <span class="font-medium text-gray-600">Withdrawn:</span>
                        <span class="text-gray-900">{{ $registration->withdrawn_at->format('M j, Y \a\t g:i A') }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Status:</span>
                        <span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                            Withdrawn
                        </span>
                    </div>
                    @if($registration->withdrawal_reason)
                        <div class="md:col-span-2 mt-4">
                            <span class="font-medium text-gray-600">Reason provided:</span>
                            <div class="mt-1 text-gray-900 italic bg-gray-50 p-3 rounded">
                                "{{ $registration->withdrawal_reason }}"
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Thank you message -->
            <div class="text-center mb-8">
                <p class="text-gray-600">
                    Thank you for letting us know promptly. Your consideration helps us better serve the cycling community and gives others the opportunity to participate.
                </p>
            </div>

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