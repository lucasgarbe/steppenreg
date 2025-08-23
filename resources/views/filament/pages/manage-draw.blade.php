<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Action Buttons -->
        <div class="bg-white shadow-sm ring-1 ring-gray-950/5 rounded-xl p-6">
            <div class="flex sm:flex-row gap-4 justify-end">
                <x-filament::button
                    wire:click="submitDraw"
                    color="success"
                    size="lg"
                    icon="heroicon-o-star"
                    class="flex-1 justify-center">
                    <span class="font-semibold">Execute Draw</span>
                </x-filament::button>

                <x-filament::button
                    wire:click="getTrackStats"
                    color="info"
                    outlined
                    size="lg"
                    icon="heroicon-o-chart-bar"
                    class="flex-1 justify-center">
                    <span class="font-semibold">View Statistics</span>
                </x-filament::button>
            </div>
        </div>

        <!-- Main Form -->
        {{ $this->form }}

    </div>
</x-filament-panels::page>
