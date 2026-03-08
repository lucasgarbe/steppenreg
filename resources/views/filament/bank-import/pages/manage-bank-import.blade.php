<x-filament-panels::page>
    <div
        x-data="bankImport(@js($registrations))"
        x-init="init()"
        class="space-y-6"
    >
        {{-- Phase tabs --}}
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
            <button
                @click="phase = 1"
                :class="phase === 1 ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-4 py-2 text-sm transition-colors"
            >
                Phase 1 &mdash; Auto-match
                <span
                    x-show="matched.length > 0"
                    class="ml-1 inline-flex items-center rounded-full bg-primary-100 dark:bg-primary-900 px-2 py-0.5 text-xs font-medium text-primary-700 dark:text-primary-300"
                    x-text="matched.length"
                ></span>
            </button>
            <button
                @click="phase = 2"
                :class="phase === 2 ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-4 py-2 text-sm transition-colors"
            >
                Phase 2 &mdash; Manual match
                <span
                    x-show="unmatched.length > 0"
                    class="ml-1 inline-flex items-center rounded-full bg-warning-100 dark:bg-warning-900 px-2 py-0.5 text-xs font-medium text-warning-700 dark:text-warning-300"
                    x-text="unmatched.length"
                ></span>
            </button>
            <button
                @click="phase = 3"
                :class="phase === 3 ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-4 py-2 text-sm transition-colors"
            >
                Phase 3 &mdash; Export unresolved
                <span
                    x-show="unresolved.length > 0"
                    class="ml-1 inline-flex items-center rounded-full bg-danger-100 dark:bg-danger-900 px-2 py-0.5 text-xs font-medium text-danger-700 dark:text-danger-300"
                    x-text="unresolved.length"
                ></span>
            </button>
        </div>

        {{-- ===== PHASE 1: Upload + Auto-match ===== --}}
        <div x-show="phase === 1" class="space-y-4">

            {{-- Upload form --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm space-y-4">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Upload bank statement CSV</h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Event name prefix
                        </label>
                        <input
                            x-model="eventPrefix"
                            type="text"
                            placeholder="e.g. Steppenreg 2025"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Only rows where <em>Verwendungszweck</em> starts with this prefix will be processed.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            CSV file (stays in your browser)
                        </label>
                        <input
                            type="file"
                            accept=".csv"
                            @change="handleFileUpload($event)"
                            class="block w-full text-sm text-gray-700 dark:text-gray-300 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 dark:file:bg-primary-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-700 dark:file:text-primary-300 hover:file:bg-primary-100"
                        />
                    </div>
                </div>

                <div x-show="parseError" class="rounded-lg bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 p-3 text-sm text-danger-700 dark:text-danger-300" x-text="parseError"></div>
            </div>

            {{-- Results summary --}}
            <div x-show="hasResults" class="grid grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 text-center shadow-sm">
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400" x-text="matched.length"></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Auto-matched</p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 text-center shadow-sm">
                    <p class="text-2xl font-bold text-warning-600 dark:text-warning-400" x-text="unmatched.length"></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Unmatched</p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 text-center shadow-sm">
                    <p class="text-2xl font-bold text-gray-500 dark:text-gray-400" x-text="filtered.length"></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Filtered out</p>
                </div>
            </div>

            {{-- Matched rows table --}}
            <div x-show="matched.length > 0" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Matched rows
                        <span class="ml-1 text-sm text-gray-500 dark:text-gray-400" x-text="'(' + matched.filter(r => r.confirmed).length + ' of ' + matched.length + ' confirmed)'"></span>
                    </h3>
                    <div class="flex gap-2">
                        <button
                            @click="bulkSelectAll()"
                            class="text-xs px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            Select all
                        </button>
                        <button
                            @click="confirmSelected()"
                            :disabled="matched.filter(r => r.confirmed).length === 0"
                            class="text-xs px-3 py-1.5 rounded-lg bg-primary-600 text-white font-semibold hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Confirm selected
                        </button>
                    </div>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left w-8">
                                <input type="checkbox" @change="toggleAllMatched($event)" class="rounded border-gray-300 dark:border-gray-600" />
                            </th>
                            <th class="px-4 py-3 text-left">Reference</th>
                            <th class="px-4 py-3 text-left">Extracted name</th>
                            <th class="px-4 py-3 text-left">Registration</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="(item, idx) in matched" :key="idx">
                            <tr :class="item.confirmed ? 'bg-success-50 dark:bg-success-900/10' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50'">
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        x-model="item.confirmed"
                                        :disabled="item.alreadyConfirmedByHash"
                                        class="rounded border-gray-300 dark:border-gray-600"
                                    />
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate" x-text="item.row['Verwendungszweck']"></td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white" x-text="item.extractedName"></td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300" x-text="item.registration.name"></td>
                                <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-white" x-text="item.row['Betrag'] + ' ' + item.row['Waehrung']"></td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400" x-text="item.row['Buchungstag']"></td>
                                <td class="px-4 py-3">
                                    <template x-if="item.registration.payed || item.alreadyConfirmedByHash">
                                        <span class="inline-flex items-center rounded-full bg-success-100 dark:bg-success-900 px-2 py-0.5 text-xs font-medium text-success-700 dark:text-success-300">Paid</span>
                                    </template>
                                    <template x-if="!item.registration.payed && !item.alreadyConfirmedByHash">
                                        <span class="inline-flex items-center rounded-full bg-warning-100 dark:bg-warning-900 px-2 py-0.5 text-xs font-medium text-warning-700 dark:text-warning-300">Unpaid</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== PHASE 2: Manual match ===== --}}
        <div x-show="phase === 2" class="space-y-4">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Manually match unresolved rows</h2>

                <div x-show="unmatched.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>No unmatched rows. Either no CSV has been uploaded, or all rows were matched in Phase 1.</p>
                </div>

                <div x-show="unmatched.length > 0" class="space-y-6">
                    <template x-for="(item, idx) in unmatched" :key="idx">
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                            {{-- Row info --}}
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="item.row['Verwendungszweck']"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        <span x-text="item.row['Buchungstag']"></span>
                                        &bull;
                                        <span class="font-mono" x-text="item.row['Betrag'] + ' ' + item.row['Waehrung']"></span>
                                        &bull;
                                        Extracted: <em x-text="item.extractedName || '(none)'"></em>
                                    </p>
                                </div>
                                <template x-if="item.manualMatch">
                                    <button
                                        @click="confirmManualMatch(item)"
                                        class="shrink-0 text-xs px-3 py-1.5 rounded-lg bg-primary-600 text-white font-semibold hover:bg-primary-700"
                                    >
                                        Confirm
                                    </button>
                                </template>
                            </div>

                            {{-- Search input --}}
                            <div class="relative">
                                <input
                                    type="text"
                                    :placeholder="'Search registrations...'"
                                    @input.debounce.300ms="searchForRow(item, $event.target.value)"
                                    class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                />

                                {{-- Search results --}}
                                <div
                                    x-show="item.searchResults && item.searchResults.length > 0"
                                    class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg divide-y divide-gray-100 dark:divide-gray-800"
                                >
                                    <template x-for="(reg, rIdx) in item.searchResults" :key="rIdx">
                                        <button
                                            type="button"
                                            @click="selectManualMatch(item, reg)"
                                            class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="reg.name"></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="reg.track_name + ' \u00b7 ' + reg.draw_status"></p>
                                                </div>
                                                <template x-if="reg.payed">
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-success-100 dark:bg-success-900 text-success-700 dark:text-success-300">Paid</span>
                                                </template>
                                                <template x-if="!reg.payed">
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-warning-100 dark:bg-warning-900 text-warning-700 dark:text-warning-300">Unpaid</span>
                                                </template>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Selected match preview --}}
                            <template x-if="item.manualMatch">
                                <div class="flex items-center gap-3 rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 px-3 py-2">
                                    <span class="text-xs text-primary-700 dark:text-primary-300">Selected:</span>
                                    <span class="text-sm font-medium text-primary-900 dark:text-primary-100" x-text="item.manualMatch.name"></span>
                                    <span class="text-xs text-primary-600 dark:text-primary-400" x-text="item.manualMatch.track_name"></span>
                                    <button @click="item.manualMatch = null" class="ml-auto text-xs text-primary-500 hover:text-primary-700">Clear</button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ===== PHASE 3: Export unresolved ===== --}}
        <div x-show="phase === 3" class="space-y-4">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Unresolved rows</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            These rows passed the event prefix filter but could not be matched to a registration.
                            Export them for manual bookkeeping.
                        </p>
                    </div>
                    <button
                        @click="exportUnresolved()"
                        :disabled="unresolved.length === 0"
                        class="px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-semibold hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        Export CSV
                    </button>
                </div>

                <div x-show="unresolved.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>No unresolved rows. Either no CSV has been uploaded or all rows have been matched.</p>
                </div>

                <div x-show="unresolved.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">Sender</th>
                                <th class="px-4 py-3 text-left">Reference</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <template x-for="(item, idx) in unresolved" :key="idx">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400" x-text="item.row['Buchungstag']"></td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300" x-text="item.row['Name Zahlungsbeteiligter']"></td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300" x-text="item.row['Verwendungszweck']"></td>
                                    <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-white" x-text="item.row['Betrag'] + ' ' + item.row['Waehrung']"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
