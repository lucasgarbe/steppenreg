# ADR-0004: Payment state as its own togglable domain

## Status
Accepted

## Context
The `payed` boolean (soon renamed `paid`) lives directly on `registrations` and is consumed by table filters, stats, exports, mail variables ("unpaid but drawn"), and the bank import. Following ADR-0001 (draw state moved to its own `draw_statuses` table) and the feature-toggle convention in `config/steppenreg.php` (`starting_numbers`, `mail`), the question was raised: keep payment state on the registration, or give it its own domain?

## Decision
Payment state moves out of `Registration` into its own domain, following the Draw precedent:

- **`payment_statuses` table** — one row per registration, current state only (`registration_id` unique, `paid`, `paid_via`, `paid_at`). A registration without a row is unpaid; an event tracking no payments is an event with no rows — absence is the natural off position.
- **`PaymentService` as the single interface** — `markPaid()` / `markUnpaid()`. Filament table actions, bulk actions, and the bank import all delegate through it. `paid` is removed from `Registration`'s `$fillable` so bypasses are structurally impossible (same enforcement as `draw_status` in ADR-0001).
- **The domain is togglable** — `steppenreg.features.payment` in `config/steppenreg.php` (env-based, like the existing toggles), because events can exist without any payment at all. Rationale accepted per team decision: same as events without lotteries.
- **The Bank Import module remains separately togglable** (`steppenreg.features.bank_import`), as it is only a *confirmation channel* feeding `PaymentService`, not the payment domain itself.

## Consumers
When the toggle is off, payment affordances must read as absent rather than false: no "paid" column/filters in the registrations table, no "unpaid but drawn" stats/mail variables. RegistrationsTable filters, RegistrationStats, RegistrationExporter, and mail variable resolution are the main surface to check.

## Consequences
- Table filter/export/stats use relationship access instead of a plain column; one-time migration cost (~the mapped `payed` occurrences).
- Registration becomes "person + application"; Draw and Payment are symmetric state domains beside it.
- Bank Import's confirmation ledger (`bank_transfer_confirmations`, ADR-0003) lives beside the payment domain: a transfer confirmation records a fingerprint and drives `PaymentService::markPaid()`.
- The `paid` rename (ADR-0003) happens as part of this move: consumers migrate to `PaymentService`/relationship access once instead of renaming twice.

## Considered alternatives

**Keep `paid` on registrations and toggle only the bank import.** Rejected: payment is not intrinsic to every event; the coupling would make the toggle misleading — the column and its UI affordances would still exist for events without payments.

**Derive paid state from the confirmation ledger on the fly.** Rejected for now: the ledger only knows bank-import confirmations, while payments can also be marked manually; deriving state from one channel only would misstate the other.
