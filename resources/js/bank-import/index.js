import { parseCsv } from './csv-parser.js';
import { fingerprintRows } from './row-hasher.js';
import { filterAndMatch } from './matcher.js';
import { classifyMatched } from './transfer-status.js';
import { downloadUnresolvedCsv } from './exporter.js';

/**
 * Alpine.js component for the 3-phase bank transfer CSV import workflow.
 *
 * Registered as window.bankImport() so the Blade view can call x-data="bankImport()".
 * All state lives inside the Alpine component — the Livewire re-render never
 * re-initialises it (the view passes no data through x-data, see ADR-0003).
 *
 * The raw bank CSV never leaves the browser: only registration IDs and
 * opaque transfer fingerprints (one per transfer) are sent to the server. The server keeps the
 * confirmation ledger (ADR-0003); no state is persisted locally.
 */
function bankImport() {
    return {
        // ------------------------------------------------------------------
        // State
        // ------------------------------------------------------------------

        /** Registration snapshot pushed from Livewire at init and re-synced after confirms */
        registrations: [],

        /** Active phase tab: 1 | 2 | 3 */
        phase: 1,

        /** Event prefix typed by the admin */
        eventPrefix: '',

        /** CSV file picked by the admin; only read once matching is started */
        selectedFile: null,

        /** True while the selected file is being parsed and matched */
        isProcessing: false,

        /** Rows that passed the filter and were exactly matched to a registration */
        matched: [],

        /** Rows that passed the filter (exactly or as suggestion) with no confirmation yet */
        unmatched: [],

        /** Rows discarded because they contain no plausible registration */
        filtered: [],

        /** Parse error message */
        parseError: null,

        /** Fingerprints of transfers already recorded in the server-side ledger */
        confirmedFingerprints: new Set(),

        /** Registrations that already have a booked transfer in the ledger */
        registrationIdsWithTransfer: new Set(),

        // ------------------------------------------------------------------
        // Computed helpers
        // ------------------------------------------------------------------

        get canStartMatching() {
            return this.selectedFile !== null && this.eventPrefix.trim() !== '' && !this.isProcessing;
        },

        get hasResults() {
            return this.matched.length > 0 || this.unmatched.length > 0 || this.filtered.length > 0;
        },

        /**
         * Phase 2 rows that have not been manually confirmed, including rows whose
         * chosen registration turned out to have a payment already.
         */
        get unresolvedUnmatched() {
            return this.unmatched.filter(item => !item.manualConfirmed);
        },

        /** Auto-matched transfers for a registration that already has a payment */
        get duplicates() {
            return this.matched.filter(item => item.status === 'duplicate');
        },

        /** Everything that needs manual bookkeeping — the Phase 3 export */
        get unresolved() {
            return [...this.duplicates, ...this.unresolvedUnmatched];
        },

        /** Auto-matched transfers that can still be booked */
        get selectableMatched() {
            return this.matched.filter(item => item.status === 'unpaid' || item.status === 'paid_unlinked');
        },

        // ------------------------------------------------------------------
        // Lifecycle
        // ------------------------------------------------------------------

        async init() {
            await this.syncFromServer();
        },

        /**
         * Pull registrations + known ledger fingerprints from Livewire.
         * Handles both synchronous property access and $wire promises.
         */
        async syncFromServer() {
            const resolve = value => {
                if (Array.isArray(value)) {
                    return value;
                }
                return null;
            };

            const registrations = this.$wire.registrations;
            this.registrations = resolve(registrations) ?? await registrations;

            const fingerprints = await this.$wire.confirmedFingerprints;
            this.confirmedFingerprints =
                new Set(Array.isArray(fingerprints) ? fingerprints : Object.values(fingerprints ?? {}));

            const registrationIds = await this.$wire.registrationIdsWithTransfer;
            this.registrationIdsWithTransfer =
                new Set(Array.isArray(registrationIds) ? registrationIds : Object.values(registrationIds ?? {}));
        },

        /**
         * Book transfers on the server; returns the set of fingerprints that were booked.
         *
         * @param {Array<{registration_id: number, fingerprint: string}>} transfers
         * @returns {Promise<Set<string>>}
         */
        async bookTransfers(transfers) {
            const result = await this.$wire.call('confirmPayments', transfers);
            await this.syncFromServer();
            return new Set(result?.booked_fingerprints ?? []);
        },

        /** Re-derive Phase 1 statuses from the ledger and the fresh registration snapshot. */
        reclassifyMatched() {
            this.matched.forEach(item => {
                const fresh = this.registrations.find(r => r.id === item.registration.id);
                if (fresh) {
                    item.registration = fresh;
                }
            });

            const statuses = classifyMatched(this.matched, {
                confirmedFingerprints: this.confirmedFingerprints,
                registrationIdsWithTransfer: this.registrationIdsWithTransfer,
            });

            this.matched.forEach((item, index) => {
                item.status = statuses[index];
                if (item.status !== 'unpaid' && item.status !== 'paid_unlinked') {
                    item.confirmed = false;
                }
            });
        },

        statusLabel(status) {
            switch (status) {
                case 'booked': return 'Booked';
                case 'duplicate': return 'Duplicate?';
                case 'paid_unlinked': return 'Paid · no transfer linked';
                default: return 'Unpaid';
            }
        },

        // ------------------------------------------------------------------
        // Phase 2 helpers
        // ------------------------------------------------------------------

        suggestionReasonLabel(reason) {
            switch (reason) {
                case 'prefix_mismatch': return '(event prefix mismatch)';
                case 'no_separator': return '(no "name - reference" convention)';
                case 'exact_miss': return '(name differs from registration)';
                case 'ambiguous': return '(several possible matches)';
                default: return '';
            }
        },

        // ------------------------------------------------------------------
        // Phase 1: File selection + auto-match
        // ------------------------------------------------------------------

        /**
         * Only remembers the picked file — matching starts with startMatching(),
         * so the prefix can be entered or changed after choosing the file.
         */
        selectFile(event) {
            this.parseError = null;
            this.selectedFile = event.target.files?.[0] ?? null;
        },

        async startMatching() {
            if (!this.canStartMatching) {
                return;
            }

            this.parseError = null;
            this.isProcessing = true;

            try {
                await this.processFile(this.selectedFile);
            } finally {
                this.isProcessing = false;
            }
        },

        async processFile(file) {
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

            const fingerprints = await fingerprintRows(rows);
            const fingerprintOf = new Map(rows.map((row, index) => [row, fingerprints[index]]));

            const { matched, unmatched, filtered } = filterAndMatch(
                rows,
                this.eventPrefix.trim(),
                this.registrations
            );

            // Exact matches: statuses come from the ledger (see classifyMatched).
            // Nothing is pre-selected — the admin ticks what to book.
            this.matched = matched.map(item => ({
                ...item,
                fingerprint: fingerprintOf.get(item.row),
                confirmed: false,
                status: 'unpaid',
            }));
            this.reclassifyMatched();

            // Unmatched rows: transfers already in the ledger were confirmed in an
            // earlier import. Otherwise prefill a loose suggestion if the matcher
            // produced one, but never confirm it — the admin must press Confirm.
            this.unmatched = unmatched.map(item => {
                const fingerprint = fingerprintOf.get(item.row);
                const manualConfirmed = this.confirmedFingerprints.has(fingerprint);
                return {
                    ...item,
                    fingerprint,
                    manualMatch: !manualConfirmed && item.suggestedRegistration ? item.suggestedRegistration : null,
                    manualConfirmed,
                    confirmedRegistration: null,
                    alreadyPaidRegistration: null,
                    searchResults: [],
                };
            });

            this.filtered = filtered;
        },

        bulkSelectAll() {
            this.selectableMatched.forEach(item => {
                item.confirmed = true;
            });
        },

        toggleAllMatched(event) {
            const checked = event.target.checked;
            this.selectableMatched.forEach(item => {
                item.confirmed = checked;
            });
        },

        async confirmSelected() {
            const toConfirm = this.selectableMatched.filter(item => item.confirmed);

            if (toConfirm.length === 0) {
                return;
            }

            // One entry per transfer; the server books at most one transfer per
            // registration and reports which ones it booked (ADR-0003).
            await this.bookTransfers(toConfirm.map(item => ({
                registration_id: item.registration.id,
                fingerprint: item.fingerprint,
            })));

            this.reclassifyMatched();
        },

        // ------------------------------------------------------------------
        // Phase 2: Manual resolution
        // ------------------------------------------------------------------

        async searchForRow(item, query) {
            if (!query || query.trim().length < 2) {
                item.searchResults = [];
                return;
            }

            // Server-side search behind the marking seam (ADR-0003):
            // searching a registration is part of the confirmation decision,
            // so both live behind the same server interface.
            item.searchResults = (await this.$wire.call('searchRegistrations', query)) ?? [];
        },

        selectManualMatch(item, registration) {
            item.manualMatch = registration;
            item.searchResults = [];
        },

        async confirmManualMatch(item) {
            if (!item.manualMatch) {
                return;
            }

            const registration = item.manualMatch;
            const booked = await this.bookTransfers([{
                registration_id: registration.id,
                fingerprint: item.fingerprint,
            }]);

            item.manualMatch = null;

            // The registration already has a payment: this transfer is not it,
            // so it stays unresolved and ends up in the Phase 3 export.
            if (!booked.has(item.fingerprint)) {
                item.alreadyPaidRegistration = registration;
            } else {
                item.manualConfirmed = true;
                item.confirmedRegistration = registration;
                item.alreadyPaidRegistration = null;
            }

            // A manual booking can turn a Phase 1 row for the same registration into a duplicate
            this.reclassifyMatched();
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
