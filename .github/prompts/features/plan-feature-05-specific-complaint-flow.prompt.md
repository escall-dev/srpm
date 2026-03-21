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

### System Design Alignment
1. Role and scope alignment:
- Enforce that reported tenant and reported unit are valid within the same property scope.
- Keep all target validation server-side to prevent payload tampering.
2. Laravel and Livewire pattern alignment:
- Build specific complaint fields as a conditional extension of existing request form flows.
- Reuse owner-side request detail rendering patterns for target metadata display.
3. Data model alignment:
- Persist `reported_tenant_id` and `reported_unit_id` using established FK conventions.
- Keep high-priority assignment deterministic for specific complaints.
4. Integration alignment:
- Maintain compatibility with owner decision flow from Feature 06.
- Provide clean inputs for demerit processing and enforcement in Features 07 and 08.
5. Dependency alignment:
- Feature dependencies: Feature 03.
- Must follow complaint metadata contracts established by Feature 03 and consumed by Feature 06.

### System Design Checklist
1. Reported tenant/unit are property-valid and server-verified.
2. Existing request form architecture is extended, not duplicated.
3. Specific complaint fields use compliant FK conventions.
4. Owner dashboard and decision integration remains seamless.
5. Downstream demerit/enforcement modules receive valid targeting data.

### Verification Checklist
1. Specific complaint cannot submit without target tenant/unit/reason.
2. Specific complaint always receives high priority.
3. Owner can immediately identify target tenant and unit.
4. Invalid IDs and tampered payloads are rejected.

### Test Targets
- tests/Feature/Tenant/SpecificComplaintValidationTest.php
- tests/Feature/Owner/SpecificComplaintLabelingTest.php
