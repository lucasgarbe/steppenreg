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
            <ul class="text-sm space-y-1 list-disc text-left">
                <li>{{ __('public.registration.success.wait_for_draw') }}</li>
                <li>{{ __('public.registration.success.mail') }}
                    <a href="#" id="contact-email" class="text-blue-600 hover:text-blue-800 underline" data-email="{{ base64_encode('bab@steppenwolf-berlin.de') }}">
                        Loading...
                    </a>
                </li>
            </ul>
        </x-public.card>

        <a href="https://steppenwolf-berlin.de/bab" class="inline-flex items-center justify-center font-medium rounded-md border focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 bg-white hover:bg-gray-50 text-gray-900 border-gray-300 focus:ring-blue-500 px-4 py-2 text-sm">
            {{ __('public.registration.success.back_to_home') }}
        </a>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const emailLink = document.getElementById('contact-email');

        // Decode base64 encoded email
        const encodedEmail = emailLink.getAttribute('data-email');
        const decodedEmail = atob(encodedEmail);

        // Update link text to show decoded email
        emailLink.textContent = decodedEmail;

        // Create proper mailto link with subject
        const subject = encodeURIComponent('Question about {{ $eventSettings->event_name }}');
        emailLink.href = `mailto:${decodedEmail}?subject=${subject}`;
    });
</script>
@endpush
