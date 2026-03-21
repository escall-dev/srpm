## Feature 03 Plan: Complaint System Core

### Objective
Support two complaint types (general and specific), force type selection before submit, validate by type, and route complaints to owner side.

### Existing Foundation To Reuse
- app/Models/Request.php
- database/migrations/2025_10_26_064241_create_requests_table.php
- app/Livewire/Forms/Tenant/RequestForm.php
- app/Livewire/Tenant/Pages/Requests.php
- app/Livewire/Owner/Pages/Requests.php
- resources/views/livewire/tenant/pages/requests.blade.php
- resources/views/livewire/owner/pages/requests.blade.php

### Required Schema Changes
1. Add columns to `requests` table:
- complaint_type (nullable enum: general, specific)
- complaint_topic (nullable string)
- complaint_priority (nullable enum: standard, high)
- reported_tenant_id (nullable FK tenants.id)
- reported_unit_id (nullable FK units.id)
- owner_decision (nullable enum: pending_review, approved, rejected)
- owner_decision_at (nullable timestamp)
2. Add indexes:
- index(unit_id, type, complaint_type, status)
- index(reported_tenant_id)
- index(reported_unit_id)

### New Files
- database/migrations/YYYY_MM_DD_HHMMSS_alter_requests_add_complaint_fields.php

### Files To Modify
- app/Models/Request.php
- app/Livewire/Forms/Tenant/RequestForm.php
- app/Livewire/Tenant/Pages/Requests.php
- resources/views/livewire/tenant/pages/requests.blade.php
- app/Livewire/Owner/Pages/Requests.php
- resources/views/livewire/owner/pages/requests.blade.php

### Implementation Steps
1. Add migration for complaint metadata fields and indexes.
2. Update Request model fillable/casts/relations for new fields.
3. In tenant form, require complaint type when `type = complaint`.
4. Add conditional validation paths:
- general requires topic
- specific requires reported tenant + reported unit + detailed reason
5. Auto-assign complaint_priority:
- general -> standard
- specific -> high
6. On create, initialize owner_decision as pending_review for complaint requests.
7. Expose complaint metadata in owner requests listing/details.
8. Keep maintenance/others behavior unchanged.

### Routing Rules
- Continue using existing owner requests page as receiving queue.
- No separate complaint table/module.

### System Design Alignment
1. Role and scope alignment:
- Keep tenant complaint creation and owner review restricted to active property boundaries.
- Require server-side checks for reported tenant/unit ownership under the same property.
2. Laravel and Livewire pattern alignment:
- Extend existing request form and request pages; avoid parallel complaint module architecture.
- Keep validation branching inside established form pattern for request type handling.
3. Data model alignment:
- Add complaint metadata only on existing `requests` table.
- Add indexes to match owner queue filters and lookup paths.
4. Integration alignment:
- Preserve non-complaint request behavior and existing request lifecycle methods.
- Emit compatible metadata for downstream features 04-10.
5. Dependency alignment:
- Feature dependencies: none.
- Must provide stable complaint fields for features 04, 05, 06, 07, 08, 09, and 10.

### System Design Checklist
1. Complaint paths are property-scoped with server-side enforcement.
2. Existing request architecture is extended rather than replaced.
3. Request schema and index additions align with query patterns.
4. Non-complaint flows remain behaviorally unchanged.
5. Downstream complaint/demerit/automation integrations have required fields.

### Verification Checklist
1. Complaint submit is blocked unless complaint type selected.
2. General and specific validations trigger correctly.
3. Complaint records appear in owner requests queue.
4. Non-complaint requests still work unchanged.
5. Priority is assigned based on complaint type.

### Test Targets
- tests/Feature/Tenant/ComplaintCoreValidationTest.php
- tests/Feature/Owner/ComplaintRoutingTest.php
