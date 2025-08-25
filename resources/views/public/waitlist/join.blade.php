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
    <x-public.card :title="__('admin.registrations.columns.name')" class="mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-600">{{ __('messages.name') }}:</span>
                <span class="text-gray-900">{{ $registration->name }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">{{ __('messages.email') }}:</span>
                <span class="text-gray-900">{{ $registration->email }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">{{ __('messages.track') }}:</span>
                <span class="text-gray-900">
                    {{ $registration->track_name }}
                    @if($registration->track && isset($registration->track['distance']))
                        ({{ $registration->track['distance'] }} km)
                    @endif
                </span>
            </div>
            @if($registration->team)
                <div>
                    <span class="font-medium text-gray-600">{{ __('messages.team') }}:</span>
                    <span class="text-gray-900">{{ $registration->team->name }}</span>
                </div>
            @endif
        </div>
    </x-public.card>

    <!-- Waitlist Information -->
    <x-public.alert type="info" class="mb-8">
        <h3 class="text-sm font-medium mb-2">How the waitlist works:</h3>
        <ul class="text-sm space-y-1">
            <li>• You'll be added to the waitlist in chronological order</li>
            <li>• If someone withdraws, the next person on the waitlist gets their spot</li>
            <li>• You'll be notified immediately via email if a spot becomes available</li>
            <li>• Your waitlist position will be confirmed via email</li>
        </ul>
    </x-public.alert>

    <!-- Join Waitlist Form -->
    <div class="text-center">
        <x-public.form action="{{ route('waitlist.store', $registration->waitlist_token) }}" method="POST">
            <x-public.button 
                type="submit" 
                variant="warning"
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
    document.getElementById('join-waitlist-btn').addEventListener('click', function() {
        this.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>{{ __("public.waitlist.joining") }}';
        this.disabled = true;
    });
</script>
@endpush