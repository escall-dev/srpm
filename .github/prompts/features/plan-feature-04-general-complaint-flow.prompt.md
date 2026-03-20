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

### Verification Checklist
1. Tenant can submit general complaint in under a few fields.
2. Complaint appears instantly in owner request list.
3. Priority is standard for all general complaints.
4. Invalid topic values are rejected server-side.

### Test Targets
- tests/Feature/Tenant/GeneralComplaintFlowTest.php
- tests/Feature/Owner/GeneralComplaintVisibilityTest.php
