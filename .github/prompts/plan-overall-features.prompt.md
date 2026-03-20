## Plan: FAQ + Complaint Enforcement Workflow

## Separate Feature Plans

Use the standalone plans under `.github/prompts/features` to implement each requirement independently and reduce ambiguity:

1. `.github/prompts/features/plan-feature-01-faq-management.prompt.md`
2. `.github/prompts/features/plan-feature-02-tenant-faq-view.prompt.md`
3. `.github/prompts/features/plan-feature-03-complaint-system-core.prompt.md`
4. `.github/prompts/features/plan-feature-04-general-complaint-flow.prompt.md`
5. `.github/prompts/features/plan-feature-05-specific-complaint-flow.prompt.md`
6. `.github/prompts/features/plan-feature-06-owner-complaint-dashboard.prompt.md`
7. `.github/prompts/features/plan-feature-07-demerit-system-logic.prompt.md`
8. `.github/prompts/features/plan-feature-08-warning-enforcement-flow.prompt.md`
9. `.github/prompts/features/plan-feature-09-notification-system.prompt.md`
10. `.github/prompts/features/plan-feature-10-smart-automation.prompt.md`
11. `.github/prompts/features/plan-feature-11-onboarding-tour-first-login.prompt.md`

Execution guide: `.github/prompts/features/README.md`

Implement all 11 requested features by extending existing SRPM structures (especially requests, notifications, owner/tenant Livewire pages, and scheduled commands), while adding only essential tables/columns for FAQ, feedback, demerit tracking, automation logging, and onboarding progress. Recommended approach: keep the existing request pipeline as the complaint backbone, add typed complaint metadata (general/specific), attach demerit and enforcement logic to owner approval decisions, reuse the existing in-app notification system plus scheduler, and add a role-aware first-login onboarding tour with persistent progress.

**Steps**
1. Phase 1 - Data Model Alignment (*blocks all later phases*)
2. Add FAQ schema scoped per property: create FAQ categories table and FAQs table with property ownership guardrails; include visibility/ordering and basic content validation constraints.
3. Add FAQ feedback persistence: one row per tenant+FAQ vote to support helpful/unhelpful tracking.
4. Extend existing requests schema (instead of new complaints table): add complaint subtype fields for general/specific flow, predefined general-topic field, specific-target fields (reported tenant/unit), priority, and owner-decision fields used to trigger demerits.
5. Add demerit/enforcement persistence: create complaint-demerit ledger table (idempotent via unique request constraint), add tenant demerit counter and enforcement status fields, and optionally an enforcement-events log table for auditability. (*parallel with step 2/3 after migration ordering is set*)
6. Add automation log table to record scheduler-triggered actions (rent reminders, warning threshold events, termination triggers). (*parallel with step 5*)
7. Phase 2 - FAQ Management and Tenant FAQ UX (*depends on Phase 1*)
8. Owner FAQ management: add owner FAQ page/section by extending current owner navigation and conventions; implement create/edit/delete FAQ and category CRUD with owner active-property scoping and clean validation.
9. Tenant FAQ viewing: add tenant FAQ page/section with property-scoped read-only FAQs, search input, category filter, and simple fast-reading layout (collapsible list/cards reusing existing design system).
10. FAQ feedback action: add helpful/unhelpful toggles on tenant FAQ items, prevent duplicate votes from same tenant on same FAQ (update-on-repeat behavior), and expose aggregate counts for owner context.
11. Phase 3 - Complaint System Core and Dual Flows (*depends on Phase 1*)
12. Extend tenant request submission flow to enforce complaint type selection when request type is complaint, then branch validation:
13. General complaint: require predefined topic list (noise/littering/etc.), minimal detail input, auto-mark standard priority.
14. Specific complaint: require target tenant, target unit, and detailed reason; auto-mark high priority and clear labeling for owner review.
15. Keep non-complaint request types intact (maintenance/others) to avoid regressions.
16. Phase 4 - Owner Complaint Dashboard + Demerit Lifecycle (*depends on Phase 3, uses Phase 1 tables*)
17. Extend existing owner requests page as complaint dashboard: add filters for complaint subtype and owner-decision status, highlight priority, and include tenant history panel (past approved complaints + demerit count).
18. Add explicit owner decision actions for complaints (approve/reject): on approve, create demerit ledger row and increment tenant demerit count by exactly 1 up to cap 5; enforce idempotency so re-approval never double-counts.
19. Threshold enforcement:
20. At demerit 3, set tenant enforcement status to warned and create warning notification.
21. Near limit (e.g., 4), create final warning notification.
22. At demerit 5, set tenant enforcement status to terminated, terminate active lease(s) through existing lease termination logic, and mark restriction flags used by tenant flows.
23. Restrict tenant actions after termination by gating critical tenant actions (new complaint/payment/request creation and other required actions) while keeping read visibility for status transparency.
24. Phase 5 - Notifications and Smart Automation (*depends on Phases 3-4*)
25. Expand notification event types using existing notifications table and component (read/unread already present): rent reminders, demerit warning, final warning, termination notice, complaint decision updates.
26. Integrate complaint/deemerit notifications at decision time and threshold transitions.
27. Add/extend scheduled command(s) to run daily checks for:
28. Upcoming rent due reminders ahead of deadlines.
29. Demerit threshold reconciliation (safety net to ensure warning/termination states are consistent).
30. Action logging into automation logs for traceability.
31. Keep existing lease payment checks but align schedule cadence to daily where requirement demands it (or keep hourly with once-per-day guard logic if business wants near-real-time).
32. Phase 6 - Hardening, Policy Guards, and Regression Safety (*depends on all phases*)
33. Add policy/authorization guards so owners only manage FAQs/complaints within active property and tenants only see FAQ/complaints tied to their active lease property.
34. Add validation and UX safeguards: clean text validation, length limits, duplicate prevention, and clear owner/tenant status messaging.
35. Add/extend feature tests for complaint branching, demerit cap behavior, threshold notifications, FAQ visibility scoping, feedback uniqueness, and restricted-tenant behavior.
36. Run targeted test suite + migration checks and verify no regressions in existing request/payment workflows.

**Relevant files**
- routes/web.php — add owner/tenant FAQ routes and any complaint-specific route aliases if needed.
- database/migrations — add new migration files for FAQ, FAQ categories, FAQ feedback, request extensions, demerit/enforcement, and automation logs.
- app/Models/Request.php — add fillable/casts/relations for complaint subtype, target entities, priority, and owner decision metadata.
- app/Livewire/Forms/Tenant/RequestForm.php — add complaint branching validation (general vs specific) and clean-content validation rules.
- app/Livewire/Tenant/Pages/Requests.php — update complaint submission UX and guard behavior for terminated tenants.
- resources/views/livewire/tenant/pages/requests.blade.php — add complaint type selector, topic list, specific-complaint fields, and restriction messaging.
- app/Livewire/Owner/Pages/Requests.php — add owner decision actions, complaint filters, tenant-history context, and demerit trigger integration.
- resources/views/livewire/owner/pages/requests.blade.php — add complaint dashboard UI for subtype/status filters, priority labels, and decision controls.
- app/Models/Tenant.php — add demerit and enforcement status fields/relations.
- app/Models/Lease.php — reuse terminate() for threshold-based contract termination.
- app/Models/Notification.php — extend type usage conventions for new event categories.
- app/Livewire/Common/Notifications.php — ensure new notification types display correctly and remain read/unread manageable.
- routes/console.php — schedule daily automation checks for reminders and demerit reconciliation.
- app/Console/Commands/CheckLeasePayments.php — adjust/extend reminder timing and logging hooks.
- app/Console/Commands (new command likely needed) — demerit threshold reconciliation + automation logging.
- app/Livewire/Owner/Pages/Dashboard.php — expose complaint/enforcement context if required.
- app/Livewire/Tenant/Pages/Dashboard.php — show demerit/enforcement status clearly to tenant.
- resources/views/components/owner/sidebar-content.blade.php — add FAQ navigation entry.
- resources/views/components/tenant/sidebar-content.blade.php — add FAQ navigation entry.

**Verification**
1. Migration validation: run migrate on a clean/local copy and confirm new/altered tables support required foreign keys and indexes.
2. FAQ scope checks: owner A cannot manage owner B FAQs; tenant only sees FAQs from active lease property.
3. FAQ UX checks: owner CRUD, category assignment, tenant search/filter, helpful/unhelpful voting and idempotent updates.
4. Complaint flow checks: complaint type required; general path accepts predefined topic + minimal detail; specific path requires tenant/unit/reason and sets high priority.
5. Owner dashboard checks: filters by complaint subtype/status work; approval/rejection updates correctly; tenant history panel reflects previous approvals/demerits.
6. Demerit logic checks: each approved complaint increments by 1 only once; cap at 5; no over-increment after cap; warning at 3; final warning near cap; termination at 5.
7. Restriction checks: terminated tenants are blocked from restricted actions and shown clear state in tenant UI/dashboard.
8. Notification checks: new events are created in-app, appear in notification center, and read/unread actions still function.
9. Automation checks: scheduled daily run sends rent reminders and reconciles demerit thresholds; automation logs capture each action.
10. Regression checks: existing maintenance/other request flows, payment checks, and lease/payment dashboards still operate as before.

**Decisions**
- Demerit source is approved complaint requests only (not payment penalties).
- FAQ visibility is property-scoped; tenant sees FAQs for their active lease property.
- Complaint implementation extends existing requests table/components (no separate complaints module).
- New files are added only where required (migrations/models/components for genuinely missing domains like FAQs and demerit ledger).

**Further Considerations**
1. Predefined general complaint topics should be configurable per property in later phase; initial implementation can ship with global defaults (noise, littering, parking, vandalism, pets, others).
2. “Final warning near limit” is recommended at demerit 4 to make threshold behavior explicit and testable.
3. If a tenant has multiple active leases (edge case), termination policy should explicitly target all active leases or only the active-property lease.
