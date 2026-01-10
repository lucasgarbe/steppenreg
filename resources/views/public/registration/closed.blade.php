@extends('layouts.public')

@section('title', __('public.event_closed.title') . ' - ' . $eventSettings->event_name)

@section('content')
    <div class="text-center">
        <x-public.page-header
            :title="__('public.event_closed.title')"
            :icon="'<svg class=\'h-8 w-8 text-red-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z\'></path></svg>'"
            icon-background="bg-red-100"
        />

        <div class="text-gray-600 space-y-4 mb-8">
            @if($state === 'closed')
                <p class="text-lg">{{ __('public.event_closed.message') }}</p>
                <p>{{ __('public.event_closed.info') }}</p>
            @elseif($state === 'closed_waitlist')
                <p class="text-lg">{{ __('public.waitlist_closed.message', ['event' => $eventSettings->event_name]) }}</p>
                <p>{{ __('public.waitlist_closed.info') }}</p>
                <p class="text-sm text-gray-500">{{ __('public.waitlist_closed.email_instruction') }}</p>
            @else
                <p class="text-lg">{{ __('public.event_closed.not_available') }}</p>
            @endif
        </div>

        <!-- Contact Information -->
        <x-public.card type="info" class="mb-6">
            <div class="text-center">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">{{__('public.event_closed.help')}}</h2>
                <p class="text-sm text-gray-600">
                    {{ __('public.event_closed.contact') }}
                    <a href="#" id="contact-email" class="text-blue-600 hover:text-blue-800 underline" data-email="{{ base64_encode(app(\App\Settings\EventSettings::class)->contact_email) }}">
                        Loading...
                    </a>
                </p>
            </div>
        </x-public.card>
    </div>
@endsection
