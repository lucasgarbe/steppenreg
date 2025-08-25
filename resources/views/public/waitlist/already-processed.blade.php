@extends('layouts.public')

@section('title', __('public.waitlist.already_processed.title'))
@section('body-class', 'py-8 flex items-center justify-center min-h-screen')
@section('main-class', 'max-w-md w-full mx-4')

@section('content')
    <div class="text-center">
        <x-public.page-header 
            :title="__('public.waitlist.already_processed.title')"
            :icon="'<svg class=\'h-8 w-8 text-yellow-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z\'></path></svg>'"
            icon-background="bg-yellow-100"
        />

        <p class="text-gray-600 mb-8">
            {{ __('public.waitlist.already_processed.message') }}
        </p>

        <!-- Contact Information -->
        <x-public.card type="info" class="mb-6">
            <div class="text-center">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Need Help?</h2>
                <p class="text-sm text-gray-600">{{ __('public.waitlist.already_processed.contact_info') }}</p>
            </div>
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