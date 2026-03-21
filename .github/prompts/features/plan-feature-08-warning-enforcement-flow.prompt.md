## Feature 08 Plan: Warning and Enforcement Flow

### Objective
At 3 demerits send warning, at 5 trigger contract termination state, restrict tenant actions after termination, and show clear status in dashboards.

### Existing Foundation To Reuse
- app/Models/Lease.php (terminate())
- app/Models/Tenant.php
- app/Livewire/Owner/Pages/Requests.php
- app/Livewire/Tenant/Pages/Dashboard.php
- app/Livewire/Owner/Pages/Dashboard.php
- app/Models/Notification.php

### Prerequisite
- Feature 07 demerit counting logic is implemented.

### Files To Modify
- app/Support/Services/DemeritService.php
- app/Models/Tenant.php
- app/Models/Lease.php
- app/Livewire/Tenant/Pages/Requests.php
- app/Livewire/Tenant/Pages/Payments.php
- app/Livewire/Tenant/Pages/Dashboard.php
- app/Livewire/Owner/Pages/Dashboard.php
- app/Models/Notification.php

### New Files (if needed)
- app/Http/Middleware/BlockTerminatedTenantActions.php
- app/Providers/AppServiceProvider.php (register middleware alias if required)

### Implementation Steps
1. Add threshold transitions in demerit service:
- count == 3 -> enforcement_status = warned
- count == 4 -> enforcement_status = final_warning
- count >= 5 -> enforcement_status = terminated
2. On termination threshold:
- find active lease(s) for tenant
- invoke lease termination flow using Lease::terminate()
3. Restrict tenant actions after terminated status:
- block create request/complaint/payment actions
- allow read-only pages for transparency
4. Show status badges/messages:
- tenant dashboard enforcement status
- owner dashboard tenant enforcement indicators
5. Emit notifications for warning/final/termination events (feature 09 integration).

### Guardrails
- Termination transition should be idempotent.
- Once terminated, demerit count cannot increase beyond cap.

### System Design Alignment
1. Role and scope alignment:
- Apply enforcement transitions only from authenticated system flows tied to tenant records.
- Keep restrictions server-enforced for terminated tenants on write actions.
2. Laravel and Livewire pattern alignment:
- Implement threshold transitions in shared demerit service, not scattered component logic.
- Apply UI restrictions through existing tenant page actions and middleware where appropriate.
3. Data model alignment:
- Keep tenant enforcement status as single source of truth for warning/final/terminated state.
- Ensure lease termination calls are idempotent and consistent with existing lease lifecycle methods.
4. Integration alignment:
- Trigger notification events through Feature 09-compatible paths.
- Keep automation reconciliation compatibility for Feature 10 scheduled checks.
5. Dependency alignment:
- Feature dependencies: Feature 07.
- Must preserve existing read-only transparency views for tenant and owner dashboards.

### System Design Checklist
1. Enforcement transitions are service-driven and idempotent.
2. Terminated tenant write actions are blocked server-side.
3. Lease termination flow is executed consistently and safely.
4. Notification and automation hooks remain compatible.
5. Dashboard status outputs remain clear and role-appropriate.

### Verification Checklist
1. Exactly at 3 demerits warning state is set.
2. At 5 demerits tenant enters terminated state.
3. Active lease is terminated when threshold is hit.
4. Restricted actions are blocked post-termination.
5. Dashboards show clear current enforcement status.

### Test Targets
- tests/Feature/Owner/EnforcementThresholdTest.php
- tests/Feature/Tenant/TerminatedTenantRestrictionsTest.php
