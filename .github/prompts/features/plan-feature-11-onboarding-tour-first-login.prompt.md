## Feature 11 Plan: Onboarding Tour for First-Time Login

### Objective
Automatically launch an onboarding tour on first login, highlight key menus/buttons/features, require completion of critical steps before full proceed, persist progress per user to prevent repeat, and allow manual revisit later.

### Existing Foundation To Reuse
- routes/web.php
- app/Models/User.php
- app/Livewire/Owner/Pages/Dashboard.php
- app/Livewire/Tenant/Pages/Dashboard.php
- resources/views/components/owner/sidebar-content.blade.php
- resources/views/components/tenant/sidebar-content.blade.php
- app/Livewire/Common/Notifications.php (optional hint/reminder)

### Required Schema Changes
1. Create `onboarding_tours` table:
- id
- key (string unique, e.g., owner_default, tenant_default)
- role (string/indexed)
- version (string, default v1)
- is_active (bool default true)
- steps (json)
- timestamps

2. Create `onboarding_progress` table:
- id
- user_id (FK users.id, indexed)
- onboarding_tour_id (FK onboarding_tours.id, indexed)
- started_at (nullable timestamp)
- completed_at (nullable timestamp)
- last_step_key (nullable string)
- required_steps_completed (json nullable)
- is_completed (bool default false)
- last_seen_version (string nullable)
- timestamps

3. Add uniqueness guard:
- unique(user_id, onboarding_tour_id)

### New Files
- database/migrations/YYYY_MM_DD_HHMMSS_create_onboarding_tours_table.php
- database/migrations/YYYY_MM_DD_HHMMSS_create_onboarding_progress_table.php
- app/Models/OnboardingTour.php
- app/Models/OnboardingProgress.php
- app/Livewire/Common/OnboardingTour.php
- resources/views/livewire/common/onboarding-tour.blade.php
- app/Support/Onboarding/OnboardingRegistry.php

### Files To Modify
- app/Models/User.php
- routes/web.php
- app/Livewire/Owner/Pages/Dashboard.php
- app/Livewire/Tenant/Pages/Dashboard.php
- resources/views/components/layouts/owner.blade.php
- resources/views/components/layouts/tenant.blade.php
- resources/views/components/owner/sidebar-content.blade.php
- resources/views/components/tenant/sidebar-content.blade.php

### Implementation Steps
1. Add migrations and models for tours and per-user progress.
2. Seed default tours for owner and tenant roles with step metadata:
- step key
- target selector
- content/title
- is_required flag
- next-step rules
3. Create shared Livewire onboarding component:
- loads active tour by role
- checks user progress
- auto-opens if first login and not completed
4. Render onboarding component in owner and tenant layouts so it appears globally after login.
5. Implement client interaction API:
- start tour
- complete step
- skip optional step
- block finishing tour until required steps are complete
- complete tour
6. Persist progress incrementally per step to avoid restart on page refresh.
7. Add “Revisit Tour” actions:
- owner sidebar item/button
- tenant sidebar item/button
- reset to first step (without deleting history if audit needed)
8. Add version handling:
- if tour version changes and policy requires re-show, prompt user to retake updated tour.

### UX Rules
- Auto-trigger only for first-time users (or users without completed progress).
- Required steps must be interacted with before marking completed.
- Optional steps can be skipped.
- Tour should not block normal app usage except completion gating for required onboarding path.

### Security/Scope Rules
- Users can only read/write their own onboarding progress.
- Tour definitions are role-scoped (owner vs tenant).

### Verification Checklist
1. First login triggers tour automatically.
2. Highlight anchors target key menus/buttons/features correctly.
3. Required steps cannot be bypassed for completion.
4. Progress persists after refresh/navigation.
5. Completed users are not auto-shown repeatedly.
6. Revisit option launches tour again on demand.
7. Owner and tenant each receive role-appropriate tour content.

### Test Targets
- tests/Feature/Common/OnboardingFirstLoginTriggerTest.php
- tests/Feature/Common/OnboardingRequiredStepsEnforcementTest.php
- tests/Feature/Common/OnboardingProgressPersistenceTest.php
- tests/Feature/Common/OnboardingRevisitTest.php
