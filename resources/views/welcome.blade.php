@extends('layouts.public')

@section('title', app(\App\Settings\EventSettings::class)->event_name)
@section('description', 'Register for our upcoming event. Complete the form to secure your spot.')

@section('content')
    <x-public.page-header 
        :title="app(\App\Settings\EventSettings::class)->event_name" 
        subtitle="Event Registration System"
    />

    <div class="space-y-6">
        <x-public.card>
            <p class="text-gray-600 mb-4">
                Register for our upcoming event. Complete the form to secure your spot.
            </p>
            
            <div class="space-y-4 mb-6">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 mt-1">
                        <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                    </div>
                    <div>
                        <span>Fill out the </span>
                        <a href="{{ route('registration.create') }}" class="text-blue-600 hover:text-blue-800 underline font-medium">
                            Registration Form
                        </a>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 mt-1">
                        <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                    </div>
                    <div>
                        <span>Access the event </span>
                        <a href="/admin" class="text-blue-600 hover:text-blue-800 underline font-medium">
                            Admin Panel
                        </a>
                    </div>
                </div>
            </div>
        </x-public.card>

        <div class="text-center space-y-4">
            <x-public.button 
                type="button" 
                variant="primary"
                size="lg"
                onclick="window.location.href='{{ route('registration.create') }}'"
            >
                Register Now
            </x-public.button>
            
            @if (Route::has('login'))
                <div class="space-x-4">
                    @auth
                        <x-public.button 
                            type="button" 
                            variant="secondary"
                            onclick="window.location.href='/admin'"
                        >
                            Dashboard
                        </x-public.button>
                    @else
                        <x-public.button 
                            type="button" 
                            variant="secondary"
                            onclick="window.location.href='{{ route('login') }}'"
                        >
                            Log in
                        </x-public.button>
                    @endauth
                </div>
            @endif
        </div>
    </div>
@endsection
