<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Registration - {{ $eventSettings->event_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 mb-4">
                    <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Withdraw from Event</h1>
                <p class="text-gray-600">We're sorry to see you go, but we understand plans change.</p>
            </div>

            <!-- Registration Info -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Your Current Registration</h2>
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
                        <span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                            Drawn
                        </span>
                    </div>
                    @if($registration->team)
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-600">Team:</span>
                            <span class="text-gray-900">{{ $registration->team->name }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Impact Warning -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">What happens when you withdraw?</h3>
                        <div class="mt-1 text-sm text-yellow-700 space-y-1">
                            <p>• Your spot will be released and offered to the next person on the waitlist</p>
                            <p>• You will receive a confirmation email</p>
                            <p>• This action cannot be undone - you cannot rejoin the event</p>
                            @if($registration->team)
                                <p>• If you're the last member of your team, the team will be dissolved</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Withdrawal Form -->
            <form method="POST" action="{{ route('withdraw.store', $token) }}">
                @csrf
                
                <div class="mb-6">
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Reason for withdrawal (optional)
                    </label>
                    <textarea id="reason" name="reason" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Let us know why you're withdrawing (this helps us improve future events)">{{ old('reason') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">This information is optional and will only be used to improve our events.</p>
                    @error('reason')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <div class="flex items-start">
                        <input type="checkbox" id="confirm" name="confirm" value="1" required
                               class="h-4 w-4 text-red-600 border-gray-300 rounded focus:ring-red-500 mt-1">
                        <label for="confirm" class="ml-2 block text-sm text-gray-900">
                            <strong>I understand that withdrawing is permanent.</strong> I confirm that I want to withdraw from {{ $eventSettings->event_name }} and release my spot to someone on the waitlist.
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
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-red-600 text-white rounded-md font-semibold hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Withdraw from Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>