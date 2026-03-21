## Feature 07 Plan: Demerit System Logic

### Objective
Apply one demerit per approved complaint, cap at 5, block further increments after cap, and show count to owner and tenant.

### Existing Foundation To Reuse
- app/Models/Tenant.php
- app/Models/Request.php
- app/Livewire/Owner/Pages/Requests.php
- app/Livewire/Owner/Pages/Dashboard.php
- app/Livewire/Tenant/Pages/Dashboard.php

### Required Schema Changes
1. Add tenant fields:
- demerit_count (unsigned tinyint default 0)
- enforcement_status (enum: normal, warned, final_warning, terminated)
2. Create `complaint_demerits` table:
- id
- request_id (FK requests.id, unique)
- tenant_id (FK tenants.id, indexed)
- awarded_by_user_id (FK users.id)
- points (unsigned tinyint default 1)
- reason (string/text)
- awarded_at (timestamp)
- timestamps
3. Optional audit table:
- enforcement_events for status transitions and actor/source metadata.

### New Files
- database/migrations/YYYY_MM_DD_HHMMSS_alter_tenants_add_demerit_fields.php
- database/migrations/YYYY_MM_DD_HHMMSS_create_complaint_demerits_table.php
- app/Models/ComplaintDemerit.php
- app/Support/Services/DemeritService.php

### Files To Modify
- app/Models/Tenant.php
- app/Models/Request.php
- app/Livewire/Owner/Pages/Requests.php
- app/Livewire/Owner/Pages/Dashboard.php
- app/Livewire/Tenant/Pages/Dashboard.php

### Implementation Steps
1. Add tenant demerit/enforcement columns.
2. Create demerit ledger table with unique request_id for idempotency.
3. Implement service method `awardForApprovedComplaint(request, actor)`:
- return early if request already has demerit ledger row
- increment by 1 only if tenant demerit_count < 5
- never exceed 5
4. Trigger service from owner complaint approval action.
5. Surface demerit count:
- owner complaint details and dashboard
- tenant dashboard/status section
6. Add server-side guard against direct over-increment updates.

### System Design Alignment
1. Role and scope alignment:
- Award demerits only for approved complaints already authorized in owner workflow.
- Ensure demerit updates are tied to property-valid complaint records.
2. Laravel and Livewire pattern alignment:
- Centralize awarding logic in service layer and call it from existing owner request actions.
- Reuse current dashboard rendering patterns for exposing demerit status.
3. Data model alignment:
- Keep one demerit ledger row per complaint request via unique `request_id`.
- Enforce cap logic at service layer and protect against direct model over-increment writes.
4. Integration alignment:
- Expose enforcement status transitions for Feature 08.
- Provide deterministic event points for notifications in Feature 09 and automation in Feature 10.
5. Dependency alignment:
- Feature dependencies: Feature 06 owner decision flow.
- Must consume complaint metadata from Feature 03 and preserve downstream enforcement contracts.

### System Design Checklist
1. Demerit awards only occur from approved complaint decisions.
2. Service-based idempotent logic is the single write path.
3. Ledger and tenant counters stay consistent and capped.
4. Enforcement and notification integrations receive stable trigger states.
5. Dashboard visibility reuses established owner/tenant page patterns.

### Verification Checklist
1. Each approved complaint adds exactly 1 demerit.
2. Re-approving same complaint does not add another point.
3. Count never exceeds 5.
4. Count is visible to owner and tenant views.

### Test Targets
- tests/Feature/Owner/DemeritAwardingTest.php
- tests/Feature/Tenant/DemeritVisibilityTest.php
- tests/Unit/Services/DemeritServiceTest.php
