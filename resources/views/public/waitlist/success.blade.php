@extends('layouts.public')

@section('title', __('public.waitlist.success.title'))

@section('content')
    <div class="text-center">
        <x-public.page-header
            :title="__('public.waitlist.success.title')"
            :icon="'<svg class=\'h-8 w-8 text-green-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'></path></svg>'"
            icon-background="bg-green-100"
        />

        <p class="text-gray-600 mb-8">
            {{ __('public.waitlist.success.message') }}
        </p>

        <!-- Information -->
        <x-public.card type="success" :title="__('public.registration.success.what_next')" class="mb-6">
            <ul class="text-sm text-left space-y-1">
                <li>• {{ __('public.waitlist.success.position_info') }}</li>
                <li>• {{ __('public.waitlist.success.notification_info') }}</li>
                <li>• Keep training and stay prepared!</li>
                <li>• No further action needed from you</li>
            </ul>
        </x-public.card>

        <!-- Action Button -->
        <x-public.button
            type="button"
            variant="secondary"
            onclick="window.location.href='/'"
        >
            {{ __('public.registration.success.back_to_home') }}
        </x-public.button>
    </div>
@endsection
