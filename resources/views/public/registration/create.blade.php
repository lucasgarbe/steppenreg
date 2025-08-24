<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('public.registration.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ __('public.registration.title') }}</h1>
                
                <!-- Language Switcher -->
                <div class="mb-4">
                    <x-language-switcher />
                </div>
                
                @if($isFlintaOnly)
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-purple-800">FLINTA* Registration Open</h3>
                                <div class="mt-1 text-sm text-purple-700">
                                    <p>Currently only open for FLINTA* participants (women, lesbians, inter, non-binary, trans, and agender people).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                
                <p class="text-gray-600">Complete the form below to register for {{ $eventSettings->event_name }}</p>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('registration.store') }}" id="registration-form">
                @csrf

                <!-- Personal Information Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">
                        Personal Information
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter your full name">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Age -->
                        <div>
                            <label for="age" class="block text-sm font-medium text-gray-700 mb-2">
                                Age <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   id="age"
                                   name="age"
                                   value="{{ old('age') }}"
                                   min="1"
                                   max="120"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter your age">
                            @error('age')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Gender -->
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                                Gender Category <span class="text-red-500">*</span>
                            </label>
                            <select id="gender"
                                    name="gender"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select gender category...</option>
                                <option value="flinta" {{ old('gender') == 'flinta' ? 'selected' : '' }}>FLINTA*</option>
                                @if(!$isFlintaOnly)
                                    <option value="all_gender" {{ old('gender') == 'all_gender' ? 'selected' : '' }}>All Gender</option>
                                @endif
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                FLINTA* includes women, lesbians, inter, non-binary, trans, and agender people
                                @if($isFlintaOnly)
                                    <br><strong class="text-purple-600">Currently only FLINTA* registration is available.</strong>
                                @endif
                            </p>
                            @error('gender')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="md:col-span-2">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="your.email@example.com">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Track Selection Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">
                        Track Selection
                    </h2>

                    <div>
                        <label for="track_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Choose Your Track <span class="text-red-500">*</span>
                        </label>
                        <select id="track_id"
                                name="track_id"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select a track...</option>
                            @foreach($tracks as $track)
                                <option value="{{ $track['id'] }}" {{ old('track_id') == $track['id'] ? 'selected' : '' }}>
                                    {{ $track['name'] }}
                                    @if(isset($track['distance']))
                                        ({{ $track['distance'] }} km)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('track_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Team Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">
                        Team (Optional)
                    </h2>

                    <div>
                        <label for="team_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Team Name
                        </label>
                        <input type="text"
                               id="team_name"
                               name="team_name"
                               value="{{ old('team_name') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter team name (leave empty to register individually)">
                        <p class="mt-1 text-xs text-gray-500">
                            Enter a team name to join an existing team or create a new one.
                            Your teammates should enter the exact same team name.
                        </p>
                        @error('team_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-center">
                    <button type="submit"
                            class="bg-blue-600 text-white px-8 py-3 rounded-md font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                        Complete Registration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Simple form enhancement - could add team name suggestions in the future if needed
            const teamNameInput = document.getElementById('team_name');

            // Optional: Add team name normalization
            teamNameInput.addEventListener('blur', function() {
                // Trim whitespace and normalize case
                this.value = this.value.trim();
            });
        });
    </script>
</body>
</html>
