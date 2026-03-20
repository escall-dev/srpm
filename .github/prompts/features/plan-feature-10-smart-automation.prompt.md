## Feature 10 Plan: Smart Automation

### Objective
Run daily checks for rent due reminders and demerit threshold monitoring, trigger warnings automatically, and log automated actions for traceability.

### Existing Foundation To Reuse
- routes/console.php
- app/Console/Commands/CheckLeasePayments.php
- app/Console/Commands/CheckLeaseExpirations.php
- app/Models/Notification.php

### Required Schema Changes
1. Create `automation_logs` table:
- id
- action_type (string/indexed)
- reference_type (nullable string)
- reference_id (nullable bigint)
- payload (json nullable)
- executed_at (timestamp indexed)
- created_at/updated_at

### New Files
- database/migrations/YYYY_MM_DD_HHMMSS_create_automation_logs_table.php
- app/Models/AutomationLog.php
- app/Console/Commands/ReconcileDemeritThresholds.php

### Files To Modify
- routes/console.php
- app/Console/Commands/CheckLeasePayments.php
- app/Support/Services/DemeritService.php

### Implementation Steps
1. Add automation logs migration/model.
2. Add new command `app:reconcile-demerit-thresholds`:
- scans tenants with non-zero demerits
- enforces status consistency (warned/final/terminated)
- emits missing notifications when needed
3. Update scheduler in routes/console.php:
- run rent reminder checks daily
- run demerit reconciliation daily
4. In both commands, write structured records to automation_logs for each action.
5. Ensure operations are idempotent to avoid duplicate notifications/log spam.

### Scheduling Policy
- Daily cadence is primary requirement.
- If retaining hourly checks, add once-per-day guards for reminder events.

### Verification Checklist
1. Scheduled commands execute on configured daily cadence.
2. Rent reminders are sent ahead of due date.
3. Demerit thresholds are reconciled automatically.
4. automation_logs capture key action metadata.
5. Duplicate runs do not duplicate business outcomes.

### Test Targets
- tests/Feature/Console/AutomationScheduleTest.php
- tests/Feature/Console/DemeritReconciliationCommandTest.php
- tests/Feature/Console/AutomationLoggingTest.php
