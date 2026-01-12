<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Main Form -->
        {{ $this->form }}

        <!-- Action Buttons -->
        <div class="flex gap-4">
            <x-filament::button
                wire:click="submitDraw"
                size="lg"
                color="danger"
                icon="heroicon-o-arrow-path-rounded-square"
                class="flex-1 justify-center">
                <span class="font-semibold">Execute Draw</span>
            </x-filament::button>

            <x-filament::button
                wire:click="sendAllDrawNotifications"
                wire:confirm="Are you sure you want to send draw result emails to ALL participants? This will notify everyone based on their current draw status."
                size="lg"
                color="primary"
                icon="heroicon-o-envelope"
                class="flex-1 justify-center">
                <span class="font-semibold">Send All Draw Results</span>
            </x-filament::button>
        </div>

        <!-- Info Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">About Draw Notifications</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>The "Send All Draw Results" button will notify participants about their draw status:</p>
                        <ul class="mt-1 list-disc pl-5 space-y-1">
                            <li><strong>Drawn participants:</strong> Get congratulations and event details</li>
                            <li><strong>Not drawn participants:</strong> Get notification about the results</li>
                            <li>Emails are queued and sent automatically</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
