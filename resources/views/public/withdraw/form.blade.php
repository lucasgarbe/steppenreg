@extends('layouts.public')

@section('title', __('public.withdrawal.title') . ' - ' . $eventSettings->event_name)

@section('content')
    <x-public.page-header
        :title="__('public.withdrawal.title')"
        :subtitle="__('public.withdrawal.subtitle')"
        :icon="'<svg class=\'h-8 w-8 text-orange-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z\'></path></svg>'"
        icon-background="bg-orange-100"
    />

    <!-- Registration Info -->
    <x-public.card :title="__('admin.registrations.single')" class="mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-600">{{ __('public.withdrawal.participant_info', ['name' => '']) }}</span>
                <span class="text-gray-900">{{ $registration->name }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">{{ __('messages.email') }}:</span>
                <span class="text-gray-900">{{ $registration->email }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">{{ __('public.withdrawal.track_info', ['track' => '']) }}</span>
                <span class="text-gray-900">{{ $registration->track_name }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">{{ __('messages.status') }}:</span>
                <span class="inline-flex px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                    {{ __('messages.drawn') }}
                </span>
            </div>
            @if($registration->starting_number)
                <div>
                    <span class="font-medium text-gray-600">{{ __('public.withdrawal.starting_number_info', ['number' => '']) }}</span>
                    <span class="text-gray-900">{{ $registration->formatted_starting_number }}</span>
                </div>
            @endif
            @if($registration->team)
                <div class="md:col-span-2">
                    <span class="font-medium text-gray-600">{{ __('messages.team') }}:</span>
                    <span class="text-gray-900">{{ $registration->team->name }}</span>
                </div>
            @endif
        </div>
    </x-public.card>

    <!-- Warning -->
    <x-public.alert type="warning" class="mb-8">
        <h3 class="font-medium">{{ __('public.withdrawal.warning') }}</h3>
    </x-public.alert>

    <!-- Withdrawal Form -->
    <x-public.form action="{{ route('withdraw.store', $token) }}" method="POST" id="withdraw-form">
        <!-- Reason (Optional) -->
        <div class="mb-8">
            <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('public.withdrawal.reasons.title') }}
            </label>
            <textarea
                id="reason"
                name="reason"
                rows="3"
                maxlength="2000"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-vertical"
            ></textarea>
            <div class="mt-1 flex justify-between">
                <p class="text-xs text-gray-400">
                    <span id="char-count">0</span>/2000
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-center space-x-4">
            <x-public.button
                type="button"
                variant="secondary"
                onclick="window.history.back()"
            >
                {{ __('public.withdrawal.cancel_button') }}
            </x-public.button>

            <x-public.button
                type="submit"
                variant="danger"
                id="withdraw-btn"
            >
                {{ __('public.withdrawal.withdraw_button') }}
            </x-public.button>
        </div>
    </x-public.form>
@endsection

@push('scripts')
<script>
    // Character counter for withdrawal reason
    document.getElementById('reason').addEventListener('input', function() {
        const charCount = this.value.length;
        const charCountElement = document.getElementById('char-count');
        charCountElement.textContent = charCount;

        // Change color when approaching or exceeding limit
        if (charCount > 1800) {
            charCountElement.className = 'text-red-500 font-medium';
        } else if (charCount > 1500) {
            charCountElement.className = 'text-orange-500';
        } else {
            charCountElement.className = 'text-gray-400';
        }
    });
</script>
@endpush
