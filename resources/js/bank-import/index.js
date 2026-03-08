import { parseCsv } from './csv-parser.js';
import { hashRow } from './row-hasher.js';
import { filterAndMatch } from './matcher.js';
import { downloadUnresolvedCsv } from './exporter.js';

const STORAGE_KEY = 'bankimport_confirmed_hashes';

/**
 * Alpine.js component for the 3-phase bank transfer CSV import workflow.
 *
 * Registered as window.bankImport() so the Blade view can call x-data="bankImport(...)".
 *
 * @param {Array<{id: number, name: string, payed: boolean, draw_status: string, track_name: string}>} registrations
 */
function bankImport(registrations) {
    return {
        // ------------------------------------------------------------------
        // State
        // ------------------------------------------------------------------

        /** Active phase tab: 1 | 2 | 3 */
        phase: 1,

        /** Event prefix typed by the admin */
        eventPrefix: '',

        /** Rows that passed the filter and were exactly matched to a registration */
        matched: [],

        /** Rows that passed the filter but had no exact match */
        unmatched: [],

        /** Rows discarded because they did not contain the event prefix */
        filtered: [],

        /** Parse error message */
        parseError: null,

        /** Set of SHA-256 hashes for rows confirmed in previous sessions */
        confirmedHashes: new Set(),

        // ------------------------------------------------------------------
        // Computed helpers
        // ------------------------------------------------------------------

        get hasResults() {
            return this.matched.length > 0 || this.unmatched.length > 0 || this.filtered.length > 0;
        },

        /**
         * Unresolved = unmatched rows that have not been manually matched/confirmed.
         */
        get unresolved() {
            return this.unmatched.filter(item => !item.manualConfirmed);
        },

        // ------------------------------------------------------------------
        // Lifecycle
        // ------------------------------------------------------------------

        init() {
            this.loadConfirmedHashes();
        },

        // ------------------------------------------------------------------
        // localStorage helpers
        // ------------------------------------------------------------------

        loadConfirmedHashes() {
            try {
                const stored = localStorage.getItem(STORAGE_KEY);
                if (stored) {
                    this.confirmedHashes = new Set(JSON.parse(stored));
                }
            } catch {
                this.confirmedHashes = new Set();
            }
        },

        persistConfirmedHashes() {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(Array.from(this.confirmedHashes))
            );
        },

        async addConfirmedHash(row) {
            const hash = await hashRow(row);
            this.confirmedHashes.add(hash);
            this.persistConfirmedHashes();
            return hash;
        },

        async isAlreadyConfirmed(row) {
            const hash = await hashRow(row);
            return this.confirmedHashes.has(hash);
        },

        // ------------------------------------------------------------------
        // Phase 1: File upload + auto-match
        // ------------------------------------------------------------------

        async handleFileUpload(event) {
            this.parseError = null;
            const file = event.target.files?.[0];

            if (!file) {
                return;
            }

            if (!this.eventPrefix.trim()) {
                this.parseError = 'Please enter an event name prefix before uploading a file.';
                event.target.value = '';
                return;
            }

            const content = await file.text();
            let rows;

            try {
                rows = parseCsv(content);
            } catch (err) {
                this.parseError = 'Failed to parse CSV: ' + err.message;
                return;
            }

            if (rows.length === 0) {
                this.parseError = 'The file appears to be empty or contains only headers.';
                return;
            }

            const { matched, unmatched, filtered } = filterAndMatch(
                rows,
                this.eventPrefix.trim(),
                registrations
            );

            // Annotate matched rows with confirmed state
            const annotatedMatched = await Promise.all(
                matched.map(async item => ({
                    ...item,
                    confirmed: false,
                    alreadyConfirmedByHash: await this.isAlreadyConfirmed(item.row),
                }))
            );

            // Pre-check rows already confirmed in a previous session
            annotatedMatched.forEach(item => {
                if (item.alreadyConfirmedByHash) {
                    item.confirmed = true;
                }
            });

            // Annotate unmatched rows
            const annotatedUnmatched = unmatched.map(item => ({
                ...item,
                manualMatch: null,
                manualConfirmed: false,
                searchResults: [],
            }));

            this.matched = annotatedMatched;
            this.unmatched = annotatedUnmatched;
            this.filtered = filtered.map(row => ({ row }));
        },

        bulkSelectAll() {
            this.matched.forEach(item => {
                if (!item.alreadyConfirmedByHash) {
                    item.confirmed = true;
                }
            });
        },

        toggleAllMatched(event) {
            const checked = event.target.checked;
            this.matched.forEach(item => {
                if (!item.alreadyConfirmedByHash) {
                    item.confirmed = checked;
                }
            });
        },

        async confirmSelected() {
            const toConfirm = this.matched.filter(
                item => item.confirmed && !item.alreadyConfirmedByHash
            );

            if (toConfirm.length === 0) {
                return;
            }

            const ids = toConfirm.map(item => item.registration.id);

            // Dispatch to Livewire
            await this.$wire.call('confirmPayments', ids);

            // Persist hashes locally so re-upload detects them
            for (const item of toConfirm) {
                await this.addConfirmedHash(item.row);
                item.alreadyConfirmedByHash = true;
            }

            // Refresh registrations from updated Livewire state
            const updated = this.$wire.get('registrations');
            if (updated) {
                updated.forEach(reg => {
                    const idx = registrations.findIndex(r => r.id === reg.id);
                    if (idx !== -1) {
                        registrations[idx] = reg;
                    }
                });
                // Re-annotate matched list with fresh payed state
                this.matched.forEach(item => {
                    const fresh = registrations.find(r => r.id === item.registration.id);
                    if (fresh) item.registration = fresh;
                });
            }
        },

        // ------------------------------------------------------------------
        // Phase 2: Manual resolution
        // ------------------------------------------------------------------

        async searchForRow(item, query) {
            if (!query || query.trim().length < 2) {
                item.searchResults = [];
                return;
            }

            // Search client-side against the registrations array
            const q = query.toLowerCase().trim();
            item.searchResults = registrations
                .filter(r => r.name.toLowerCase().includes(q))
                .slice(0, 10);
        },

        selectManualMatch(item, registration) {
            item.manualMatch = registration;
            item.searchResults = [];
        },

        async confirmManualMatch(item) {
            if (!item.manualMatch) {
                return;
            }

            await this.$wire.call('confirmPayments', [item.manualMatch.id]);
            await this.addConfirmedHash(item.row);
            item.manualConfirmed = true;
            item.manualMatch = null;
        },

        // ------------------------------------------------------------------
        // Phase 3: Export unresolved
        // ------------------------------------------------------------------

        exportUnresolved() {
            if (this.unresolved.length === 0) {
                return;
            }
            downloadUnresolvedCsv(this.unresolved);
        },
    };
}

// Register the Alpine component.
// Filament bundles its own Alpine internally via Livewire's wire:snapshot mechanism.
// The 'alpine:init' event is dispatched before Alpine initialises components,
// so this is the correct and only registration hook needed.
document.addEventListener('alpine:init', () => {
    Alpine.data('bankImport', bankImport);
});
