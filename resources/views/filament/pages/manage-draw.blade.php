<x-filament-panels::page>
    <div class="space-y-8">

        <!-- Draw Section -->
        <div class="space-y-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Draw Management</h2>
            
            <!-- Draw Form -->
            {{ $this->form }}

            <!-- Draw Action Button -->
            <div class="flex justify-end">
                <x-filament::button
                    wire:click="submitDraw"
                    size="lg"
                    color="danger"
                    icon="heroicon-o-arrow-path-rounded-square"
                    class="w-full sm:w-auto">
                    <span class="font-semibold">Execute Draw</span>
                </x-filament::button>
            </div>

            <!-- Draw Info Section -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">About Draw Execution</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>The draw process will:</p>
                            <ul class="mt-1 list-disc pl-5 space-y-1">
                                <li>Randomly select the specified number of participants</li>
                                <li>Keep team members together</li>
                                <li>Update participant statuses to "drawn"</li>
                                <li>This action cannot be undone</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="bg-white dark:bg-gray-900 px-3 text-gray-500">Email Notifications</span>
            </div>
        </div>

        <!-- Email Section -->
        <div class="space-y-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Send Draw Results</h2>
            
            <!-- Email Form -->
            {{ $this->emailForm }}

            <!-- Email Action Button -->
            <div class="flex justify-end">
                <x-filament::button
                    wire:click="submitSendEmails"
                    wire:confirm="Are you sure you want to send draw result emails? This will notify all participants in the selected track(s) based on their current draw status."
                    size="lg"
                    color="primary"
                    icon="heroicon-o-envelope"
                    class="w-full sm:w-auto">
                    <span class="font-semibold">Send Draw Results</span>
                </x-filament::button>
            </div>

            <!-- Email Info Section -->
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
                            <p>The "Send Draw Results" button will send emails to participants in the selected track:</p>
                            <ul class="mt-1 list-disc pl-5 space-y-1">
                                <li><strong>Drawn participants:</strong> Get withdrawal links and congratulations</li>
                                <li><strong>Waitlist participants:</strong> Get status confirmation</li>  
                                <li><strong>Not drawn participants:</strong> Get waitlist registration links</li>
                                <li><strong>All necessary tokens</strong> are generated automatically before sending</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>