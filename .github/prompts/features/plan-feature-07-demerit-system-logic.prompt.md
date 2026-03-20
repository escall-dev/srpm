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

### Verification Checklist
1. Each approved complaint adds exactly 1 demerit.
2. Re-approving same complaint does not add another point.
3. Count never exceeds 5.
4. Count is visible to owner and tenant views.

### Test Targets
- tests/Feature/Owner/DemeritAwardingTest.php
- tests/Feature/Tenant/DemeritVisibilityTest.php
- tests/Unit/Services/DemeritServiceTest.php
