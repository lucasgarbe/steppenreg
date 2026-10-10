import { parseCsv } from './csv-parser.js';
import { hashRow } from './row-hasher.js';
import { filterAndMatch } from './matcher.js';
import { downloadUnresolvedCsv } from './exporter.js';

/**
 * Alpine.js component for the 3-phase bank transfer CSV import workflow.
 *
 * Registered as window.bankImport() so the Blade view can call x-data="bankImport()".
 * All state lives inside the Alpine component — the Livewire re-render never
 * re-initialises it (the view passes no data through x-data, see ADR-0003).
 *
 * The raw bank CSV never leaves the browser: only registration IDs and
 * opaque transfer fingerprints are sent to the server. The server keeps the
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
         * Unresolved = unmatched rows that have not been manually confirmed,
         * including rows whose chosen registration turned out to be paid already.
         */
        get unresolved() {
            return this.unmatched.filter(item => !item.manualConfirmed);
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
        },

        async isAlreadyConfirmed(row) {
            const hash = await hashRow(row);
            return this.confirmedFingerprints.has(hash);
        },

        async recordConfirmed(row) {
            const hash = await hashRow(row);
            this.confirmedFingerprints.add(hash);
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

            const { matched, unmatched, filtered } = filterAndMatch(
                rows,
                this.eventPrefix.trim(),
                this.registrations
            );

            // Exact matches: "already handled" is either in the server ledger
            // or the registration is already paid — pre-check, never re-confirm.
            const annotatedMatched = await Promise.all(
                matched.map(async item => ({
                    ...item,
                    confirmed: false,
                    alreadyConfirmedByHash:
                        item.registration.paid || (await this.isAlreadyConfirmed(item.row)),
                }))
            );

            annotatedMatched.forEach(item => {
                if (item.alreadyConfirmedByHash) {
                    item.confirmed = true;
                }
            });

            // Unmatched rows: transfers already in the ledger were confirmed in an
            // earlier import. Otherwise prefill a loose suggestion if the matcher
            // produced one, but never confirm it — the admin must press Confirm.
            const annotatedUnmatched = await Promise.all(
                unmatched.map(async item => {
                    const manualConfirmed = await this.isAlreadyConfirmed(item.row);
                    return {
                        ...item,
                        manualMatch: !manualConfirmed && item.suggestedRegistration ? item.suggestedRegistration : null,
                        manualConfirmed,
                        confirmedRegistration: null,
                        alreadyPaidRegistration: null,
                        searchResults: item.ambiguousCandidates?.length ? item.ambiguousCandidates : [],
                    };
                })
            );

            this.matched = annotatedMatched;
            this.unmatched = annotatedUnmatched;
            this.filtered = filtered;
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

            // Send registration IDs and fingerprints keyed by registration id;
            // the server records the ledger and marks payments (ADR-0003).
            const confirmations = await Promise.all(
                toConfirm.map(async item => ({
                    id: item.registration.id,
                    fingerprint: await hashRow(item.row),
                }))
            );

            const ids = confirmations.map(confirmation => confirmation.id);
            const fingerprintsByRegistrationId = Object.fromEntries(
                confirmations.map(confirmation => [String(confirmation.id), confirmation.fingerprint])
            );

            const result = await this.$wire.call('confirmPayments', ids, fingerprintsByRegistrationId);
            const alreadyPaidIds = new Set(result?.already_paid_ids ?? []);

            // Reflect the ledger locally so the UI marks these as handled.
            // Rows whose registration was paid in the meantime were not recorded.
            for (const item of toConfirm) {
                if (alreadyPaidIds.has(item.registration.id)) {
                    continue;
                }
                await this.recordConfirmed(item.row);
                item.alreadyConfirmedByHash = true;
            }

            // Pull fresh state — this does NOT reset local UI state because the
            // Alpine component is not re-initialised on Livewire re-renders.
            await this.syncFromServer();

            // Re-annotate matched list with fresh paid state
            this.matched.forEach(item => {
                const fresh = this.registrations.find(r => r.id === item.registration.id);
                if (fresh) item.registration = fresh;
            });
        },

        // ------------------------------------------------------------------
        // Phase 2: Manual resolution
        // ------------------------------------------------------------------

        async searchForRow(item, query) {
            if (!query || query.trim().length < 2) {
                item.searchResults = item.ambiguousCandidates?.length ? item.ambiguousCandidates : [];
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
            const fingerprint = await hashRow(item.row);

            const result = await this.$wire.call('confirmPayments', [registration.id], {
                [String(registration.id)]: fingerprint,
            });

            // The registration was paid already: this transfer is not its payment,
            // so it stays unresolved and ends up in the Phase 3 export.
            if ((result?.already_paid_ids ?? []).includes(registration.id)) {
                item.alreadyPaidRegistration = registration;
                item.manualMatch = null;
                await this.syncFromServer();
                return;
            }

            await this.recordConfirmed(item.row);
            item.manualConfirmed = true;
            item.confirmedRegistration = registration;
            item.alreadyPaidRegistration = null;
            item.manualMatch = null;

            await this.syncFromServer();
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
