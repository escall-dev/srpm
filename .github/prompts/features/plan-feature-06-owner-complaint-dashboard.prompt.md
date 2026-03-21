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

### System Design Alignment
1. Role and scope alignment:
- Keep all owner complaint review and decisions scoped to active property data.
- Ensure decision actions cannot be executed across unauthorized requests.
2. Laravel and Livewire pattern alignment:
- Extend existing owner requests page and modal patterns rather than building a disconnected dashboard stack.
- Reuse shared concerns for consistent toast/state handling.
3. Data model alignment:
- Consume complaint metadata fields added in Feature 03 without introducing redundant storage.
- Keep decision state transitions idempotent and auditable.
4. Integration alignment:
- Route approvals through centralized demerit service from Feature 07.
- Keep notification/event hooks compatible with Feature 09.
5. Dependency alignment:
- Feature dependencies: Features 03, 04, and 05.
- Must not regress existing non-complaint request workflows.

### System Design Checklist
1. Owner actions enforce active-property authorization on server side.
2. Existing owner requests architecture and concerns are reused.
3. Complaint decision states remain consistent and idempotent.
4. Demerit and notification integrations are triggered through shared services.
5. Non-complaint request behavior remains intact.

### Verification Checklist
1. Filters by type/subtype/decision/status all work.
2. Owner can approve/reject complaint requests.
3. Tenant history context is visible in review flow.
4. Existing request workflows still work for non-complaint records.

### Test Targets
- tests/Feature/Owner/ComplaintDashboardFiltersTest.php
- tests/Feature/Owner/ComplaintDecisionWorkflowTest.php
