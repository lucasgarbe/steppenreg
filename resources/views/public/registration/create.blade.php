@extends('layouts.public')

@section('title', __('public.registration.title'))
@section('description', __('public.registration.subtitle'))

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <x-public.page-header
        :title="$eventSettings->event_name .' '. __('public.registration.title')"
        :subtitle="__('public.registration.subtitle')"
    >
        @if($isPriorityPeriod && !empty($availableCategories))
            <!-- Show custom messages for categories that have them -->
            @foreach($categoriesWithMessages as $category)
                @php
                    $locale = app()->getLocale();
                    $message = $category['message'][$locale] ?? null;
                    $messageStyle = $category['message_style'] ?? 'info';
                    $categoryLabel = $category['translations'][$locale]['label'] ?? $category['key'];
                @endphp
                
                @if($message)
                    <x-public.alert 
                        type="{{ $messageStyle }}" 
                        :icon="false" 
                        class="mt-4"
                    >
                        <h3 class="text-sm font-medium mb-2">{{ $categoryLabel }}</h3>
                        <div class="text-sm prose prose-sm max-w-none">
                            {!! $message !!}
                        </div>
                    </x-public.alert>
                @endif
            @endforeach
            
            <!-- Default priority message if no custom messages -->
            @if(empty($categoriesWithMessages))
                <x-public.alert type="info" :icon="false" class="mt-4">
                    <h3 class="text-sm font-medium">{{ __('public.registration.priority_notice.title') }}</h3>
                    <div class="mt-1 text-sm">
                        <p>{{ __('public.registration.priority_notice.message') }}</p>
                        <p class="mt-2">
                            <strong>{{ __('public.registration.priority_notice.available_for') }}:</strong>
                            {{ collect($availableCategories)->pluck('translations.'.app()->getLocale().'.label')->join(', ') }}
                        </p>
                    </div>
                </x-public.alert>
            @endif
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
                        required
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

                <!-- Team -->
                <div>
                    <label for="team_name" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('public.registration.fields.team') }}
                    </label>
                    <input
                        type="text"
                        id="team_name"
                        name="team_name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
                        placeholder="{{ __('public.registration.fields.team_placeholder') }}"
                        value="{{ old('team_name') }}"
                    >
                    <p class="mt-1 text-xs text-gray-500">
                        {{ __('public.registration.fields.team_help') }}
                    </p>
                    @error('team_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-spw-yellow focus:border-spw-yellow"
                        placeholder="{{ __('public.registration.fields.notes_placeholder') }}"
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <!-- Custom Questions Section -->
        @php
            $customQuestions = $eventSettings->custom_questions ?? [];
        @endphp
        @if(count($customQuestions) > 0)
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">
                {{ __('public.registration.additional_information') }}
            </h2>

            <div class="space-y-6">
                @foreach($customQuestions as $question)
                    @include('public.registration.partials.custom-question', ['question' => $question])
                @endforeach
            </div>
        </div>
        @endif

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
    document.addEventListener('DOMContentLoaded', function() {
        // Team name normalization
        const teamNameInput = document.getElementById('team_name');

        teamNameInput.addEventListener('blur', function() {
            // Trim whitespace
            this.value = this.value.trim();
        });
    });
</script>
@endpush
