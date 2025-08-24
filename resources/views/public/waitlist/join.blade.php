<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Waitlist - {{ $eventSettings->event_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Join Waitlist</h1>
                <p class="text-gray-600">You weren't selected in the draw, but you can join the waitlist!</p>
            </div>

            <!-- Registration Info -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Your Registration Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Name:</span>
                        <span class="text-gray-900">{{ $registration->name }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Email:</span>
                        <span class="text-gray-900">{{ $registration->email }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Track:</span>
                        <span class="text-gray-900">{{ $registration->track_name }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Status:</span>
                        <span class="inline-flex px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                            Not Drawn
                        </span>
                    </div>
                </div>
            </div>

            <!-- Waitlist Information -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">How the Waitlist Works</h3>
                        <div class="mt-1 text-sm text-blue-700 space-y-2">
                            <p>• You'll be added to the waitlist for your selected track</p>
                            <p>• If someone withdraws, spots become available in order of waitlist registration</p>
                            <p>• You'll receive an email if a spot opens up for you</p>
                            <p>• You can check your waitlist position anytime using this link</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Join Form -->
            <form method="POST" action="{{ route('waitlist.store', $token) }}">
                @csrf
                
                <div class="mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="confirm" name="confirm" value="1" required
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="confirm" class="ml-2 block text-sm text-gray-900">
                            I want to join the waitlist for {{ $eventSettings->event_name }} and understand that I will be notified if a spot becomes available.
                        </label>
                    </div>
                    @error('confirm')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @error('general')
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        {{ $message }}
                    </div>
                @enderror

                <div class="flex justify-center space-x-4">
                    <a href="{{ url('/') }}" 
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Not Now
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Join Waitlist
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>