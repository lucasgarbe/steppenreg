@extends('layouts.public')

@section('title', __('public.waitlist.join_title') . ' - ' . $eventSettings->event_name)

@section('content')
    <x-public.page-header
        :title="__('public.waitlist.join_title')"
        :subtitle="__('public.waitlist.join_subtitle')"
        :icon="'<svg class=\'h-8 w-8 text-yellow-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z\'></path></svg>'"
        icon-background="bg-yellow-100"
    />

    <!-- Registration Info -->
    <x-public.card title="Info" class="mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="font-medium text-gray-600">{{ __('messages.name') }}:</p>
                <p class="text-gray-900">{{ $registration->name }}</p>
            </div>
            <div>
                <p class="font-medium text-gray-600">{{ __('messages.email') }}:</p>
                <p class="text-gray-900">{{ $registration->email }}</p>
            </div>
            <div>
                <p class="font-medium text-gray-600">{{ __('messages.track') }}:</spap>
                <p class="text-gray-900">
                    {{ $registration->track_name }}
                    @if($registration->track && isset($registration->track['distance']))
                        ({{ $registration->track['distance'] }} km)
                    @endif
                </p>
            </div>
            @if($registration->team)
                <div>
                    <p class="font-medium text-gray-600">{{ __('messages.team') }}:</p>
                    <p class="text-gray-900">{{ $registration->team->name }}</p>
                </div>
            @endif
        </div>
    </x-public.card>

    <!-- Join Waitlist Form -->
    <div class="text-center">
        <x-public.form action="{{ route('waitlist.store', $token) }}" method="POST">
            <x-public.button
                type="submit"
                size="lg"
                id="join-waitlist-btn"
            >
                {{ __('public.waitlist.join_button') }}
            </x-public.button>
        </x-public.form>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('join-waitlist-btn');
        submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>{{ __("public.waitlist.joining") }}';
        submitBtn.disabled = true;
    });
</script>
@endpush
