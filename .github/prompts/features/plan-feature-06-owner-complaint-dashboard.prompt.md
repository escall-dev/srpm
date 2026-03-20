## Feature 06 Plan: Owner Complaint Dashboard

### Objective
Provide owners a complaint-focused dashboard with filtering, decision controls, penalty trigger points, and tenant history context.

### Existing Foundation To Reuse
- app/Livewire/Owner/Pages/Requests.php
- resources/views/livewire/owner/pages/requests.blade.php
- app/Models/Request.php
- app/Models/Tenant.php
- app/Livewire/Concerns/HasToast.php

### Prerequisite
- Features 03, 04, and 05 implemented.

### Files To Modify
- app/Livewire/Owner/Pages/Requests.php
- resources/views/livewire/owner/pages/requests.blade.php
- app/Models/Request.php
- app/Models/Tenant.php

### Implementation Steps
1. Expand owner filters:
- request type (complaint, maintenance, others)
- complaint_type (general/specific)
- owner_decision status (pending_review/approved/rejected)
- current status
2. Add complaint-focused columns/badges:
- subtype
- priority
- target tenant/unit (for specific)
3. Add decision actions for complaints:
- approve complaint
- reject complaint
4. Keep existing markInProgress/markCompleted/reject flow for non-complaint requests.
5. Add tenant context panel in details modal:
- demerit count
- recent approved complaints
- recent complaint statuses
6. Ensure approval action calls centralized demerit service/logic (Feature 07).

### Guardrails
- Owner can review only requests tied to active property.
- Decision action should be idempotent and locked after final decision.

### Verification Checklist
1. Filters by type/subtype/decision/status all work.
2. Owner can approve/reject complaint requests.
3. Tenant history context is visible in review flow.
4. Existing request workflows still work for non-complaint records.

### Test Targets
- tests/Feature/Owner/ComplaintDashboardFiltersTest.php
- tests/Feature/Owner/ComplaintDecisionWorkflowTest.php
