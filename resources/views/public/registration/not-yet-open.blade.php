@extends('layouts.public')

@section('title', __('public.registration.not_yet_open'))

@section('content')
    <x-public.page-header
        :title="$eventSettings->event_name"
        :subtitle="__('public.registration.not_yet_open')"
    />

    <div class="max-w-2xl mx-auto">
        <x-public.alert type="info" :icon="true">
            <h3 class="text-lg font-medium mb-2">{{ __('public.registration.not_yet_open_title') }}</h3>
            <div class="text-sm space-y-2">
                <p>{{ __('public.registration.not_yet_open_message') }}</p>
                
                @if(isset($nextOpening))
                    <div class="mt-4 p-4 bg-white bg-opacity-50 rounded-md">
                        <p class="font-semibold">{{ __('public.registration.next_opening') }}</p>
                        <p class="text-base mt-1">
                            <strong>{{ $nextOpening['label'] }}</strong> registration opens on 
                            <strong>{{ $nextOpening['datetime']->format('F j, Y') }}</strong> at 
                            <strong>{{ $nextOpening['datetime']->format('g:i A') }}</strong>
                        </p>
                        <p class="text-sm mt-1 text-gray-600">
                            ({{ $nextOpening['datetime']->diffForHumans() }})
                        </p>
                    </div>
                @endif
                
                @if(isset($event))
                    @php
                        $categories = $event->getGenderCategories();
                    @endphp
                    
                    @if(count($categories) > 1)
                        <div class="mt-4">
                            <p class="font-semibold mb-2">{{ __('public.registration.upcoming_openings') }}</p>
                            <ul class="space-y-2">
                                @foreach($categories as $gender => $settings)
                                    @if($settings['enabled'] && isset($settings['registration_opens_at']))
                                        @php
                                            $opensAt = \Carbon\Carbon::parse($settings['registration_opens_at']);
                                        @endphp
                                        @if($opensAt->isFuture())
                                            <li class="flex justify-between items-center">
                                                <span>{{ $settings['label'] }}</span>
                                                <span class="text-gray-600">{{ $opensAt->format('M j, Y g:i A') }}</span>
                                            </li>
                                        @endif
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif
            </div>
        </x-public.alert>
        
        <div class="mt-6 text-center">
            <a href="{{ url('/') }}" class="text-spw-yellow hover:text-yellow-600 font-medium">
                {{ __('public.registration.return_home') }}
            </a>
        </div>
    </div>
@endsection
