@extends('layouts.public')

@section('title', __('public.registration.title'))
@section('description', __('public.registration.subtitle'))

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <x-public.page-header 
        :title="__('public.registration.title')" 
        :subtitle="'Complete the form below to register for ' . $eventSettings->event_name"
    >
        @if($isFlintaOnly)
            <x-public.alert type="info" class="mt-4">
                <h3 class="text-sm font-medium">FLINTA* Registration Open</h3>
                <div class="mt-1 text-sm">
                    <p>Currently only open for FLINTA* participants (women, lesbians, inter, non-binary, trans, and agender people).</p>
                </div>
            </x-public.alert>
        @endif
    </x-public.page-header>

    <x-public.form action="{{ route('registration.store') }}" id="registration-form">
        <!-- Personal Information Section -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">
                {{ __('public.registration.personal_information') }}
            </h2>
            
            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('public.registration.fields.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="{{ __('public.registration.fields.name_placeholder') }}"
                        required
                        value="{{ old('name') }}"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('public.registration.fields.email') }} <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="{{ __('public.registration.fields.email_placeholder') }}"
                        required
                        value="{{ old('email') }}"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Age -->
                <div>
                    <label for="age" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('public.registration.fields.age') }} <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="age" 
                        name="age" 
                        min="16" 
                        max="99"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="{{ __('public.registration.fields.age_placeholder') }}"
                        required
                        value="{{ old('age') }}"
                    >
                    @error('age')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Gender -->
                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('public.registration.fields.gender') }}
                    </label>
                    <select 
                        id="gender" 
                        name="gender" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">{{ __('public.registration.fields.gender_placeholder') }}</option>
                        @foreach(\App\Models\Registration::getGenderOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('gender') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('gender')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Event Information Section -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">
                {{ __('public.registration.event_information') }}
            </h2>
            
            <div class="space-y-6">
                <!-- Track -->
                <div>
                    <label for="track_id" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('public.registration.fields.track') }} <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="track_id" 
                        name="track_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required
                        onchange="handleTrackChange()"
                    >
                        <option value="">{{ __('public.registration.fields.track_placeholder') }}</option>
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

                <!-- Team Options -->
                <div id="team-section" style="display: none;">
                    <div class="space-y-4">
                        <!-- Team Option Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                {{ __('public.registration.fields.team_option') }}
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="team_option" value="individual" class="mr-2" checked onchange="handleTeamOptionChange()">
                                    <span class="text-sm text-gray-700">{{ __('public.registration.team_options.individual') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="team_option" value="join" class="mr-2" onchange="handleTeamOptionChange()">
                                    <span class="text-sm text-gray-700">{{ __('public.registration.team_options.join_team') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="team_option" value="create" class="mr-2" onchange="handleTeamOptionChange()">
                                    <span class="text-sm text-gray-700">{{ __('public.registration.team_options.create_team') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Team Selection/Creation -->
                        <div id="team-input" style="display: none;">
                            <label for="team_name" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('public.registration.fields.team') }}
                            </label>
                            <input 
                                type="text" 
                                id="team_name" 
                                name="team_name" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="{{ __('public.registration.fields.team_placeholder') }}"
                                value="{{ old('team_name') }}"
                            >
                            @error('team_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('public.registration.fields.notes') }}
                    </label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="{{ __('public.registration.fields.notes_placeholder') }}"
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-center">
            <x-public.button 
                type="submit" 
                variant="primary" 
                size="lg"
                id="submit-btn"
            >
                {{ __('public.registration.submit') }}
            </x-public.button>
        </div>
    </x-public.form>
@endsection

@push('scripts')
<script>
    const tracks = @json($tracks);
    
    function handleTrackChange() {
        const trackSelect = document.getElementById('track_id');
        const teamSection = document.getElementById('team-section');
        
        if (trackSelect.value) {
            const selectedTrack = tracks.find(track => track.id == trackSelect.value);
            if (selectedTrack && selectedTrack.allow_teams) {
                teamSection.style.display = 'block';
            } else {
                teamSection.style.display = 'none';
                // Reset team options
                document.querySelector('input[name="team_option"][value="individual"]').checked = true;
                handleTeamOptionChange();
            }
        } else {
            teamSection.style.display = 'none';
        }
    }
    
    function handleTeamOptionChange() {
        const teamInput = document.getElementById('team-input');
        const selectedOption = document.querySelector('input[name="team_option"]:checked');
        
        if (selectedOption && selectedOption.value !== 'individual') {
            teamInput.style.display = 'block';
            const input = document.getElementById('team_name');
            input.required = true;
            
            if (selectedOption.value === 'join') {
                input.placeholder = '{{ __("public.registration.fields.team_placeholder") }}';
            } else if (selectedOption.value === 'create') {
                input.placeholder = '{{ __("public.registration.fields.team_placeholder") }}';
            }
        } else {
            teamInput.style.display = 'none';
            document.getElementById('team_name').required = false;
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        handleTrackChange();
        handleTeamOptionChange();
    });
</script>
@endpush