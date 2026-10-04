# ADR-0003: Bank import — client-side CSV handling, server-side payment confirmation ledger

## Status
Accepted

## Context
The bank transfer import (`feat/payment-import` branch, `app/Domain/BankImport/`) parses the club's raw bank statement CSV in the browser and confirms payments over Livewire. The raw CSV contains sensitive customer data (names, IBANs, account movements). Payment confirmation dedup lived in browser localStorage (per-browser, per-machine), and the transfer-reference naming convention varied per event (observed: `Steppenreg - <name>` with a ` - ` separator, and `Teilnahmebeitrag SPX26 <name>` with no separator — the latter matched nothing under the hard-coded separator logic).

## Decision
The raw bank CSV is never uploaded, stored, or processed server-side. The browser keeps only what it physically must do: parse the CSV, filter by event prefix/convention, extract names, propose matches, and compute an opaque SHA-256 fingerprint per transfer row (`Buchungstag|Betrag|Verwendungszweck`).

Everything that is state or decision-of-record lives on the server, behind one seam: `recordConfirmations([{ registrationId, fingerprint }])`.
- A `bank_transfer_confirmations` table (`fingerprint` unique, `registration_id`, audit columns) replaces localStorage as the dedup ledger. Known fingerprints are preloaded to the client so already-confirmed rows pre-check.
- `ConfirmPaymentsAction` remains the single point for marking registrations paid: it validates registration IDs (throws on stale/invalid ones) and is idempotent about already-paid rows.
- Manual resolution searches go through the server (`searchRegistrations()` via Livewire); the earlier idea of deleting the server-side search is reversed by this decision, since searching is part of the marking decision and shares its seam.
- Phase 1 auto-matching still needs a registration snapshot in the browser (`id`, `name`, `payed`) — deliberately narrowed, and justified because the server cannot match without the CSV.
- The transfer-reference convention (event prefix, separator) is a per-event setting, not hard-coded logic.

## Key design choices

**Privacy drives the split.** Sensitivity of the raw statement data was the deciding constraint: no byte of the CSV crosses the seam. Only fingerprints and registration IDs are transmitted.

**Fingerprint is opaque on the server.** The server stores the hash without interpreting it; matching still happens client-side. This keeps a single parsing/matching implementation (JS) instead of duplicating bank-format knowledge in PHP.

**Robustness over parallelism.** The team accepted single-admin, non-concurrent usage as the norm; the system is still made robust through server-side validation and the idempotent, immutable confirmation ledger — not through optimistic locking or real-time conflict handling.

**Feature toggle at the provider seam.** The feature ships behind `steppenreg.features.bank_import`, following the Mail (ADR-0002, "when the mail feature is disabled, the rate limiter should go with it") and StartingNumber provider-guard convention.

## Consequences
- Dedup is shared across browsers, machines, and admins for free.
- No bank PII is persisted server-side; the ledger contains only fingerprints, links, and who/when.
- The matching rules live in JS; contributions in PHP that want to reuse them must reimplement or expose them (accepted trade-off).
- Changing the marking paid flow must consider the ledger: confirmations are recorded atomically with the paid flip.

## Considered alternatives

**Full server-side import (upload the CSV to Livewire).** Rejected: uploads/stores sensitive banking data on the server; violates the data-handling constraint the team set explicitly.

**Status quo (localStorage dedup, client-only state).** Rejected: dedup does not survive another browser; "already handled" knowledge is not shared and is fragile.

**Rename `payed` to `paid` together with this feature.** Decision: rename is done as a separate, app-wide change on `v2` prior to merging this feature — the column, scopes, filters, translations, UI labels, and export paths are all included, not just the DB column.
