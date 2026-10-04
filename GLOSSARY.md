# Steppenreg

Registration and participation management for club events (application, draw with waitlist, starting numbers, payments, communication by mail).

## Language

### Participation and draw

**Registration**:
A person's application to participate in an event. Owns payment state.
_Avoid_: application entry, sign-up row

**Draw status**:
The outcome state of a registration after a track's lottery or manual mark — and the audit trail around it. Mutated only through the Draw domain interface.
_Avoid_: drawn flag, state machine

**Starting number**:
A number assigned to a drawn and paid registration for the event.

### Payment

**Paid**:
The payment state of a registration: the fee is confirmed as received. The canonical English word; `_Avoid_: payed (misspelling), bezahlt (only in user-facing translations)`.

**Bank Transfer**:
An incoming payment on the club account, taken from an exported bank statement CSV. Exists only in the browser during an import session; never uploaded.
_Avoid_: payment (ambiguous with registration state), transaction

**Transfer Fingerprint**:
An opaque SHA-256 token identifying an exact bank transfer row, computed from its date, amount, and purpose. Stable across statements.
_Avoid_: hash (unqualified), id

**Confirmation Ledger**:
The server-side record of which transfers were linked to which registrations and when. The single source of truth for "already handled".
_Avoid_: localStorage, marked payments list

### Communication

**Mail Template**:
A reusable, localized email template with variables, sent to selected registrations.
_Avoid_: email (for the template concept)
