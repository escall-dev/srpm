## Feature 05 Plan: Specific Complaint Flow

### Objective
Require tenant, unit, and detailed reason for serious complaints, route clearly to owner, and mark high priority.

### Existing Foundation To Reuse
- app/Livewire/Forms/Tenant/RequestForm.php
- app/Livewire/Tenant/Pages/Requests.php
- app/Models/Lease.php
- app/Models/Tenant.php
- app/Models/Unit.php
- resources/views/livewire/tenant/pages/requests.blade.php
- app/Livewire/Owner/Pages/Requests.php

### Prerequisite
- Feature 03 schema fields in `requests` are available.

### Files To Modify
- app/Livewire/Forms/Tenant/RequestForm.php
- app/Livewire/Tenant/Pages/Requests.php
- resources/views/livewire/tenant/pages/requests.blade.php
- app/Livewire/Owner/Pages/Requests.php
- resources/views/livewire/owner/pages/requests.blade.php

### Implementation Steps
1. For complaint_type = specific, expose additional tenant form fields:
- reported_tenant_id
- reported_unit_id
- detailed reason (long form)
2. Build safe option lists constrained to owner’s property context if needed by tenancy/business rules.
3. Validate specific complaint fields:
- reported_tenant_id required and exists
- reported_unit_id required and exists
- detailed reason required with higher minimum length
4. Force `complaint_priority = high`.
5. Set owner-facing labels:
- Complaint / Specific / High Priority
6. In owner details modal/table, show target tenant/unit clearly.

### Validation Rules
- Disallow mismatched tenant-unit combinations when applicable.
- Prevent self-targeting if policy requires (optional business rule).

### Verification Checklist
1. Specific complaint cannot submit without target tenant/unit/reason.
2. Specific complaint always receives high priority.
3. Owner can immediately identify target tenant and unit.
4. Invalid IDs and tampered payloads are rejected.

### Test Targets
- tests/Feature/Tenant/SpecificComplaintValidationTest.php
- tests/Feature/Owner/SpecificComplaintLabelingTest.php
