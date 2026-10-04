# Steppenreg

Event registration and participant management for sporting events with limited capacity. Handles public registration, team formation, lottery-based selection, and starting number assignment.

## Language

### Event Structure

**Track**:
A distinct race or course within an event, with its own capacity, starting number range, and draw.
_Avoid_: Route, discipline, category

**Gender Category**:
A classification used to control registration eligibility and priority access windows. Not merely demographic — it gates when someone can register.
_Avoid_: Division, class, group

**Custom Question**:
An admin-defined question attached to the registration form, with configurable type (text, select, checkbox, etc.) and per-event scope.
_Avoid_: Field, attribute, metadata

### Participants

**Registration**:
A person's entry into an event — captures identity, track choice, payment, draw outcome, and starting number. The central entity of the system.
_Avoid_: Entry, participant, sign-up

**Team**:
A group of registrations that compete together. Treated as an atomic unit in the draw — all members are selected or none are. Bound to a single track. When a member withdraws post-draw, the team shrinks; the freed spot is not reassigned.
_Avoid_: Group, squad

### Selection

**Draw**:
The lottery-based selection process that determines which registrations receive a spot when demand exceeds capacity. Can be executed multiple times per track to incrementally fill spots.
_Avoid_: Lottery, selection, raffle

**Draw Status**:
The current state of a registration in the draw process: `drawn` (selected) or `not_drawn` (went through lottery but not selected). Stored in a separate `draw_statuses` table to keep draw concerns modular — events without lotteries don't use this table.
_Avoid_: Draw result, selection status

**Starting Number**:
A unique numeric identifier assigned to a drawn registration, allocated from a track-specific range. Includes reserved sub-ranges for waitlisted participants.
_Avoid_: Bib number, race number

**Waitlist**:
Registrations that were not initially drawn but may still receive a starting number if spots open. Has its own number range (30% of main capacity) and an overflow mechanism.
_Avoid_: Reserve list, standby

### Lifecycle

**Registration Lifecycle**:
The progression of a registration through distinct stages: registered -> drawn -> paid -> starting -> finished. Each stage is independent — a registration can be drawn but not paid, or paid but not starting (no-show).

**Drawn**:
A registration that won the lottery and has been allocated a spot. Does not imply payment or participation.
_Avoid_: Selected, accepted

**Paid**:
A registration that has completed payment. In practice, payment is expected before starting, but not strictly enforced — accommodates real-world scenarios (late payment, on-site payment, etc.). Note: the codebase uses the misspelling `payed` in the database column and scopes.
_Avoid_: Payed (except in existing code)

**Starting**:
A registration that is actively participating in the event on event day. Distinct from drawn — someone can be drawn but not starting (no-show).
_Avoid_: Participating, active

**Application State**:
The current phase of the event lifecycle: `closed`, `priority_period`, `general_open`, `live_event`. Can be driven automatically by datetime or manually overridden.
_Avoid_: Status, phase, mode
