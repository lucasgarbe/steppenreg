@extends('layouts.public')

@section('title', __('public.withdrawal.success.title'))
@section('body-class', 'py-8 flex items-center justify-center min-h-screen')
@section('main-class', 'max-w-md w-full mx-4')

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

        <!-- Next Steps -->
        <x-public.card type="info" :title="__('public.registration.success.what_next')" class="mb-6">
            <ul class="text-sm text-left space-y-1">
                <li>• {{ __('public.withdrawal.success.next_participant_info') }}</li>
                <li>• {{ __('public.withdrawal.success.refund_info') }}</li>
                <li>• You will receive a confirmation email shortly</li>
                <li>• Thank you for notifying us promptly</li>
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