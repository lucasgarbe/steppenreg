<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white shadow-sm ring-1 ring-gray-950/5 p-6 rounded-xl">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Automatic Draw Management</h2>
            <p class="text-gray-600 mb-6">
                Use this interface to execute automatic draws for specific tracks. The system will randomly select participants, 
                ensuring that team members are always drawn together as a complete unit.
            </p>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="font-medium text-blue-900 mb-2">How the Draw Works:</h3>
                <ul class="text-blue-800 text-sm space-y-1">
                    <li>• <strong>Individual registrations</strong> are treated as single drawing units</li>
                    <li>• <strong>Team registrations</strong> are treated as complete team units (all members drawn together)</li>
                    <li>• The system randomly selects units until reaching your target participant count</li>
                    <li>• Only registrations with "Not Drawn" status are included in the draw pool</li>
                </ul>
            </div>

            {{ $this->form }}
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Important Note</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>This action cannot be undone automatically. Make sure to:</p>
                        <ul class="mt-1 space-y-1">
                            <li>• Review the track statistics before executing</li>
                            <li>• Use "Preview Draw" to understand what will be selected</li>
                            <li>• Consider manually selected participants (they won't be affected)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
