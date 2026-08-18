# ADR-0001: Deepen the Draw mutation seam

## Status
Accepted

## Context
Draw status mutations were scattered across 4 independent code paths (DrawService, table actions, bulk actions, form select), but only DrawService fired domain events. Manual marks bypassed starting number assignment. Team synchronization logic lived in RegistrationObserver, outside the Draw domain. The Draw model tracked execution records with a unique constraint preventing multiple lottery runs per track.

## Decision
Consolidate all draw status mutations through DrawService with `markDrawn()` and `markNotDrawn()` methods. Remove the Draw model and `draws` table entirely. Create a separate `draw_statuses` table (one row per registration, current state only) to keep draw concerns modular — events without lotteries don't use this table. Use spatie/laravel-activitylog for audit trail instead of Draw records.

## Key design choices

**Single interface in DrawService**
All mutation paths (lottery, table actions, form) delegate to DrawService. The `draw_status` field is removed from Registration's `$fillable`, making bypass structurally impossible rather than conventionally discouraged.

**Separate `draw_statuses` table**
Draw state lives in the Draw domain, not tangled into Registration. This allows events without lotteries to exist without draw columns. One row per registration tracks current state; activity log captures history.

**Team synchronization moves into DrawService**
The observer's team-sync logic (when one member's draw status changes, sync all others) is core draw domain logic. It moves from RegistrationObserver into DrawService, executed synchronously within the same transaction. Events fire per member to ensure starting numbers auto-assign.

**Multiple lottery executions supported**
Without the Draw model's unique constraint, the same track can run the lottery multiple times (draw 100, then 20 more). Execution guard becomes "are there any registrations without a draw_status in this track?"

**Activity log for audit trail**
spatie/laravel-activitylog captures who did what when. Structured logging (Laravel Log + Monolog) handles observability separately. These serve different audiences: audit log for business questions, application log for operational debugging.

**Remove draw_status field from RegistrationForm**
Admins change draw status only through dedicated table actions, not via a form dropdown. This eliminates a footgun (the dropdown implied you could freely pick any status, but draw status has rules).

## Consequences
- **Locality**: draw bugs concentrate in one module instead of scattering across observers, table actions, and forms
- **Leverage**: one interface serves lottery execution, manual marks, and bulk operations
- **Testability**: one module to test all draw status transitions through, replacing zero coverage on the core algorithm
- **Modularity**: draw concerns decoupled from Registration — events without lotteries don't need draw columns
- **Audit trail preserved**: activity log captures execution history without the complexity of Draw records
- **Runtime bug fixed**: manual marks now trigger starting number assignment via domain events

## Considered alternatives

**Keep Draw model for audit trail**
Rejected: Draw records added complexity (unique constraint preventing multiple executions, bidirectional relationships) without proportional value. Activity log is more appropriate for "who did what when" and doesn't tangle into the domain model.

**Methods on Registration model instead of DrawService**
Rejected: DrawService already owns the draw domain and has event infrastructure. Putting methods on Registration would scatter draw logic back into the Registration domain.

**Keep `$fillable` and rely on convention**
Rejected: Structural enforcement (removing from `$fillable`) is stronger than convention. Makes bypass impossible rather than discouraged.

**Synchronous recursive calls for team sync**
Rejected: One bulk update is faster and atomic. Firing events per member preserves starting number assignment without N separate transactions.
