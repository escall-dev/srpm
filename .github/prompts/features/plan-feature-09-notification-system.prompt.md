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

### Verification Checklist
1. Each target event creates exactly one notification per recipient per event instance.
2. Unread tab displays newly created alerts.
3. Mark-as-read and mark-all-read still work.
4. Dashboard snippets show recent critical notifications.

### Test Targets
- tests/Feature/Common/NotificationEventDispatchTest.php
- tests/Feature/Common/NotificationsReadStateTest.php
