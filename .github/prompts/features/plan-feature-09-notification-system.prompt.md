## Feature 09 Plan: Notification System

### Objective
Trigger and deliver in-app notifications for rent reminders, demerit warning at 3, final warning near limit, and termination notice, with read/unread management.

### Existing Foundation To Reuse
- app/Models/Notification.php
- app/Livewire/Common/Notifications.php
- app/Console/Commands/CheckLeasePayments.php
- app/Livewire/Tenant/Pages/Dashboard.php
- app/Livewire/Owner/Pages/Dashboard.php

### Prerequisite
- Features 07 and 08 (demerit + enforcement transitions).

### Files To Modify
- app/Models/Notification.php
- app/Livewire/Common/Notifications.php
- app/Support/Services/DemeritService.php
- app/Console/Commands/CheckLeasePayments.php
- app/Livewire/Tenant/Pages/Dashboard.php
- app/Livewire/Owner/Pages/Dashboard.php

### Implementation Steps
1. Standardize notification type keys:
- rent_due_reminder
- demerit_warning
- demerit_final_warning
- termination_notice
- complaint_decision (optional but recommended)
2. Trigger notifications at event points:
- rent due from scheduled checks
- demerit thresholds from demerit service
- complaint approve/reject from owner decision flow
3. Continue using existing Notification model/table with `is_read` support.
4. Ensure notifications component supports unread filtering and mark-as-read actions for new types.
5. Surface latest critical alerts in tenant and owner dashboards.

### Content Rules
- Keep messages concise and action-oriented.
- Include key context (unit, due date, reason, threshold state).

### System Design Alignment
1. Role and scope alignment:
- Deliver notifications only to authorized recipients within owner/tenant role boundaries.
- Ensure read and mark-as-read actions are limited to the authenticated recipient.
2. Laravel and Livewire pattern alignment:
- Reuse existing Notification model and shared notifications component pathways.
- Trigger events from existing services/commands instead of duplicating dispatch logic per page.
3. Data model alignment:
- Keep notification type keys stable and centrally defined.
- Preserve `is_read` semantics and existing query/index behavior for unread views.
4. Integration alignment:
- Consume demerit/enforcement state changes from Features 07 and 08.
- Support scheduler-driven reminders and automation logging integration from Feature 10.
5. Dependency alignment:
- Feature dependencies: Features 07 and 08.
- Optional complaint decision alerts depend on Feature 06 decision actions.

### System Design Checklist
1. Recipient access is enforced server-side for notification data and actions.
2. Existing model/component notification architecture is reused.
3. Notification type contracts remain stable across producers.
4. Demerit/enforcement/scheduler integrations are wired to shared event points.
5. Unread and read-state behavior remains backward compatible.

### Verification Checklist
1. Each target event creates exactly one notification per recipient per event instance.
2. Unread tab displays newly created alerts.
3. Mark-as-read and mark-all-read still work.
4. Dashboard snippets show recent critical notifications.

### Test Targets
- tests/Feature/Common/NotificationEventDispatchTest.php
- tests/Feature/Common/NotificationsReadStateTest.php
