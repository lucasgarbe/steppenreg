<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Event Registration</h1>
                <p class="text-gray-600">Complete the form below to register for the event</p>
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
                                <option value="all_gender" {{ old('gender') == 'all_gender' ? 'selected' : '' }}>All Gender</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">FLINTA* includes women, lesbians, inter, non-binary, trans, and agender people</p>
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
