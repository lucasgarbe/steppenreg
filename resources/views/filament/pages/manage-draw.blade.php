<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Main Form -->
        {{ $this->form }}

        <x-filament::button
            wire:click="submitDraw"
            size="lg"
            color="danger"
            icon="heroicon-o-arrow-path-rounded-square"
            class="flex-1 justify-center">
            <span class="font-semibold">Execute Draw</span>
        </x-filament::button>

    </div>
</x-filament-panels::page>
