# ADR-0002: Collapse the Mail sending paths

## Status

Accepted

## Context

The mail subsystem has two parallel sending paths: template-based (via `MailTemplateService`) and flexible (via `SendFlexibleMail` which bypasses the service). Variable replacement exists in 4 locations: `MailTemplateService`, `TemplateBasedEmail::envelope()`, `template-based.blade.php`, and `SendFlexibleMail::processVariables()`. Blade views contain CommonMark instantiation and regex conditionals. All 3 mail jobs duplicate boilerplate (mail log tracking, attempt counting, failure batch recording, retry logic). Zero test coverage across the entire mail subsystem.

## Decision

Consolidate all mail sending through `MailTemplateService` with two public methods: `sendEmail()` for template-based mail and `sendFlexible()` for flexible mail. Move all rendering (variable replacement, CommonMark processing) into `MailTemplateService` — mailable classes become thin presenters. Extract an abstract `MailJob` base class to consolidate job boilerplate. Extract the entire mail subsystem into `app/Domain/Mail/` following the established domain pattern.

## Key design choices

**Single entry point in MailTemplateService**
All mail sending goes through `MailTemplateService`. The service resolves variables, renders content (including CommonMark processing), sends via Laravel's Mail facade, and logs to `MailLog`. Template-based and flexible mail become internal methods, not separate paths.

**Rendering moves out of Blade views**
`template-based.blade.php` becomes a thin presenter that outputs pre-rendered HTML. All variable replacement and markdown processing happens in `MailTemplateService` before the view is rendered. This deletes rendering logic from Blade views and mailable classes.

**Abstract MailJob base class**
Each mail job keeps its own class identity (important for queue monitoring and filtering) but inherits from `MailJob` which provides: mail log lookup, attempt tracking, `markAsSent()`/`markAsFailed()`/`MailFailureBatch::recordFailure()`, `retryUntil()`, `backoff()`, and `middleware()`. Jobs become thin dispatchers that call the service.

**Manual draw notification dispatch preserved**
Draw notifications remain manually dispatched from Filament table/bulk actions. The domain constraint is that admins must confirm and evaluate the draw before informing participants. Automatic event-driven dispatch would violate this workflow.

**Full domain extraction**
Mail code moves to `app/Domain/Mail/` with the established structure: Models, Services, Jobs, Events, Listeners, Mail (mailables), Notifications. This matches the Draw and StartingNumber domains and makes mail concerns discoverable.

**MailVariableResolver stays separate**
Variable resolution is a distinct responsibility from mail sending. `MailVariableResolver` moves into the Mail domain as `app/Domain/Mail/Services/VariableResolver.php` and continues to be injected into `MailTemplateService` via DI.

**MailTemplate model methods stay on model**
Static methods like `getAvailableVariables()` and `getAvailableVariablesGrouped()` remain on the `MailTemplate` model. They're query helpers for Filament forms tied to the model's data, not variable resolution logic.

**Rate limiting moves to MailServiceProvider**
The email rate limiter configuration moves from `AppServiceProvider` to `MailServiceProvider` inside the Mail domain. When the mail feature is disabled, the rate limiter should go with it.

**Incremental migration with tests**
Phase 1: Extract domain (move files, update namespaces) + add test coverage for existing behavior. Phase 2: Collapse sending paths (extend `MailTemplateService`, add `sendFlexible()`, extract `MailJob` base class) + add more tests. This gives us a safety net before refactoring.

## Consequences

- **Locality**: variable replacement in one place instead of 4
- **Leverage**: one interface serves template-based and flexible mail
- **Testability**: one module to test mail end-to-end, replacing zero coverage
- **Maintainability**: job boilerplate consolidated, rendering logic exits Blade views
- **Discoverability**: mail concerns concentrated in `app/Domain/Mail/`
- **Workflow preserved**: manual draw notification dispatch respects domain constraint

## Considered alternatives

**Keep SendFlexibleMail as separate path**
Rejected: perpetuates duplication of MailLog management and variable replacement. Two paths mean two things to test, two things to maintain.

**Move rendering to shared MailRenderer class**
Rejected: adds another class without proportional benefit. `MailTemplateService` already owns the sending pipeline; rendering is part of that pipeline.

**Consolidate into single generic SendMailJob**
Rejected: collapses distinct job types that have different retry windows, rate limiting, and monitoring needs. Queue monitoring and `queue:retry` filtering benefit from separate job classes.

**Event-driven draw notifications**
Rejected: violates domain constraint that admins must confirm draw before notifying participants. Manual dispatch is intentional workflow, not accidental omission.

**Keep mail code in app/Services/ and app/Models/**
Rejected: mail has its own models, services, jobs, and business logic. It's a bounded context, not just infrastructure. Scattered mail code is harder to discover and maintain.

## Migration plan

**Phase 1: Extract domain + add tests**
- Create `app/Domain/Mail/` directory structure
- Move mail models, services, jobs, mailables, notifications into domain
- Update all imports and namespaces
- Add `MailServiceProvider` with rate limiter config
- Write tests for existing behavior (template-based sending, flexible sending, variable resolution, mail log tracking)

**Phase 2: Collapse paths + add tests**
- Add `sendFlexible()` method to `MailTemplateService`
- Move rendering logic from Blade views and mailables into `MailTemplateService`
- Extract abstract `MailJob` base class
- Refactor 3 mail jobs to extend base class
- Update `template-based.blade.php` to be a thin presenter
- Write tests for collapsed paths

## Related documentation

- Architecture review: `/private/var/folders/p3/hyvqj86s5_v9n99ywjg_xqjr0000gn/T/architecture-review-1787077190.html`
- Email rate limiting guide: `docs/EMAIL_RATE_LIMITING.md`
