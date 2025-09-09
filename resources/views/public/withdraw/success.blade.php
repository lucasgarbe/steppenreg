@extends('layouts.public')

@section('title', __('public.withdrawal.success.title'))

@section('content')
    <div class="text-center">
        <x-public.page-header
            :title="__('public.withdrawal.success.title')"
            :icon="'<svg class=\'h-8 w-8 text-green-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'></path></svg>'"
            icon-background="bg-green-100"
        />

        <p class="text-gray-600 mb-8">
            {{ __('public.withdrawal.success.message') }}
        </p>

        <!-- Contact Information -->
        <x-public.card type="info" class="mb-6">
            <div class="text-center">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">{{__('public.event_closed.help')}}</h2>
                <p class="text-sm text-gray-600">
                    {{ __('public.event_closed.contact') }}
                    <a href="#" id="contact-email" class="text-blue-600 hover:text-blue-800 underline" data-email="{{ base64_encode('contact@example.org') }}">
                        Loading...
                    </a>
                </p>
            </div>
        </x-public.card>

        <a href="https://your-event.org/event" class="inline-flex items-center justify-center font-medium rounded-md border focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 bg-white hover:bg-gray-50 text-gray-900 border-gray-300 focus:ring-blue-500 px-4 py-2 text-sm">
            {{ __('public.registration.success.back_to_home') }}
        </a>
    </div>
@endsection
