## Feature 04 Plan: General Complaint Flow

### Objective
Enable quick complaint submission for common issues (noise, littering, etc.), auto-route to owner dashboard, and mark standard priority.

### Existing Foundation To Reuse
- app/Livewire/Forms/Tenant/RequestForm.php
- app/Livewire/Tenant/Pages/Requests.php
- resources/views/livewire/tenant/pages/requests.blade.php
- app/Livewire/Owner/Pages/Requests.php

### Prerequisite
- Feature 03 schema fields in `requests` are available.

### Files To Modify
- app/Livewire/Forms/Tenant/RequestForm.php
- resources/views/livewire/tenant/pages/requests.blade.php
- app/Livewire/Owner/Pages/Requests.php
- resources/views/livewire/owner/pages/requests.blade.php

### Implementation Steps
1. Add predefined topic options for general complaints:
- noise
- littering
- parking_obstruction
- vandalism
- pets
- other
2. In tenant form, when request type is complaint and complaint_type is general:
- require complaint_topic
- allow short description with lower minimum threshold
3. Force `complaint_priority = standard`.
4. Set `owner_decision = pending_review`.
5. In owner queue, display badge:
- Complaint / General / Standard
6. Ensure search/filter can include complaint_topic.
7. Maintain minimal-friction UX:
- fewer fields visible for general complaint path.

### Validation Rules
- complaint_topic required and in allowed list for general complaints.
- description required but minimal length can be lower than specific complaints.

### System Design Alignment
1. Role and scope alignment:
- Keep general complaint submission and owner review within existing property scope controls.
- Reject topic payload tampering through server-side validation allow-list.
2. Laravel and Livewire pattern alignment:
- Reuse existing complaint form flow from Feature 03 without introducing separate submit endpoints.
- Keep owner queue rendering and filters aligned with current requests page patterns.
3. Data model alignment:
- Persist only approved general complaint topic values.
- Preserve priority/decision defaults expected by shared request processing.
4. Integration alignment:
- Feed owner dashboard decision workflow from Feature 06 using same request records.
- Keep compatibility with demerit awarding trigger path in Feature 07.
5. Dependency alignment:
- Feature dependencies: Feature 03.
- Must not diverge from complaint schema contracts defined in Feature 03.

### System Design Checklist
1. General complaint submission respects property scope and topic allow-list.
2. Existing request form and owner queue architecture is reused.
3. Stored complaint metadata follows Feature 03 contracts.
4. Owner decision workflow integration remains intact.
5. Demerit-trigger compatibility is preserved for approved complaints.

### Verification Checklist
1. Tenant can submit general complaint in under a few fields.
2. Complaint appears instantly in owner request list.
3. Priority is standard for all general complaints.
4. Invalid topic values are rejected server-side.

### Test Targets
- tests/Feature/Tenant/GeneralComplaintFlowTest.php
- tests/Feature/Owner/GeneralComplaintVisibilityTest.php
