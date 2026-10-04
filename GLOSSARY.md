# Steppenreg

Registration and participation management for club events (application, draw with waitlist, starting numbers, payments, communication by mail).

## Language

**Registration**:
A person's application to participate in an event. Owns who applies and their answers.
_Avoid_: application entry, sign-up row

## State domains

**Draw status**:
The outcome state of a registration after a track's lottery or manual mark. Tracked in the Draw domain, mutated only through its single interface. Togglable per installation; an event without a lottery uses no draw rows.
_Avoid_: drawn flag, state machine

**Paid**:
The payment state of a registration: the fee is confirmed as received. Tracked in the Payment domain. Togglable per installation; an event tracking no payments uses no payment rows.
_Avoid_: payed (misspelling), user-facing translations of "bezahlt" are fine in German

**Starting number**:
A number assigned to a drawn and paid registration for the event. Togglable per installation.
_Avoid_: race number

## Payment and import

**Payment domain**:
Owns the paid state and its mutation paths. Marking paid (manually, via table action, or via import) always goes through the domain's single interface.
_Avoid_: payed column, payment service (that's the implementation)

**Bank Transfer**:
An incoming payment on the club account, taken from an exported bank statement CSV. Exists only in the browser during an import session; never uploaded.
_Avoid_: payment (ambiguous with the paid state), transaction

**Import module**:
An optional confirmation channel next to the Payment domain: it matches bank transfers to registrations and confirms them as paid. Togglable separately from the Payment domain.
_Avoid_: bank sync, payment importer

**Transfer fingerprint**:
An opaque SHA-256 token identifying an exact bank transfer row, computed from its date, amount, and purpose. Stable across statements.
_Avoid_: hash (unqualified), id

**Confirmation ledger**:
The server-side record of which transfers were linked to which registrations and when. The single source of truth for "already handled".
_Avoid_: localStorage, marked payments list

## Communication

**Mail Template**:
A reusable, localized email template with variables, sent to selected registrations.
_Avoid_: email (for the template concept)
