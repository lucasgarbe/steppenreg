@extends('layouts.public')

@section('title', __('public.registration.success.title'))

@section('content')
    <div class="text-center">
        <x-public.page-header
            :title="__('public.registration.success.title')"
            :icon="'<svg class=\'h-8 w-8 text-green-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'></path></svg>'"
            icon-background="bg-green-100"
        />

        <p class="text-gray-600 mb-8">
            {{ __('public.registration.success.message') }}
            {{ __('public.registration.success.confirmation_sent') }}
        </p>

        <!-- Next Steps -->
        <x-public.card type="info" :title="__('public.registration.success.what_next')" class="mb-6">
                <p>{{ __('public.registration.success.mail') }}
                    <a href="#" id="contact-email" class="text-blue-600 hover:text-blue-800 underline" data-email="{{ base64_encode(app(\App\Settings\EventSettings::class)->contact_email) }}" data-event-name="{{ app(\App\Settings\EventSettings::class)->event_name }}" data-email-subject="{{ __('public.event_closed.email_subject', ['event' => app(\App\Settings\EventSettings::class)->event_name]) }}">
                        Loading...
                    </a>
                </p>
        </x-public.card>

        <a href="{{ app(\App\Settings\EventSettings::class)->event_website_url ?: app(\App\Settings\EventSettings::class)->organization_website }}" class="inline-flex items-center justify-center font-medium rounded-md border focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 bg-white hover:bg-gray-50 text-gray-900 border-gray-300 focus:ring-blue-500 px-4 py-2 text-sm">
            {{ __('public.registration.success.back_to_home') }}
        </a>
    </div>
@endsection
