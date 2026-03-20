## Feature 02 Plan: Tenant FAQ View

### Objective
Show tenants only FAQs tied to their active lease property, with search, category filter, fast-reading layout, and helpful/unhelpful feedback.

### Existing Foundation To Reuse
- routes/web.php
- app/Livewire/Tenant/Pages/Requests.php (search/filter/pagination pattern)
- app/Models/Lease.php
- app/Models/Tenant.php
- app/Livewire/Concerns/HasToast.php
- resources/views/components/tenant/sidebar-content.blade.php

### Required Schema Changes
1. Create `faq_feedback` table:
- id
- faq_id (FK faqs.id, indexed)
- tenant_id (FK tenants.id, indexed)
- vote (enum: helpful, not_helpful)
- timestamps
2. Add uniqueness guard:
- unique(faq_id, tenant_id) to enforce one active vote per tenant per FAQ.

### New Files
- database/migrations/YYYY_MM_DD_HHMMSS_create_faq_feedback_table.php
- app/Models/FaqFeedback.php
- app/Livewire/Tenant/Pages/Faqs.php
- resources/views/livewire/tenant/pages/faqs.blade.php

### Files To Modify
- routes/web.php
- resources/views/components/tenant/sidebar-content.blade.php
- app/Models/Faq.php (relations for feedback aggregates)

### Implementation Steps
1. Add `faq_feedback` migration with FK/index/unique constraints.
2. Implement `FaqFeedback` model and add relationships on `Faq` and `Tenant`.
3. Add tenant route:
- GET /tenant/faqs -> Tenant\Pages\Faqs
4. Add tenant sidebar entry for FAQs.
5. In Tenant FAQ page, derive property scope from tenant active lease:
- tenant -> active lease -> unit -> property_id
6. Query only visible FAQs for that property.
7. Add search and category filter using live bindings.
8. Add simple reading layout:
- compact cards or accordion
- category label
- updated date for relevance
9. Add feedback buttons:
- helpful / not helpful
- upsert vote (update existing vote instead of duplicate insert)
10. Display aggregate feedback counts per FAQ.

### Scope And Guard Rules
- Tenant cannot read FAQ outside active property scope.
- Tenant cannot submit feedback for FAQ outside active property scope.

### Verification Checklist
1. Tenant sees only property-scoped visible FAQs.
2. Search and category filter work together.
3. Helpful/unhelpful voting is idempotent.
4. Feedback count updates correctly.
5. Tenant with no active lease gets graceful empty-state message.

### Test Targets
- tests/Feature/Tenant/TenantFaqVisibilityTest.php
- tests/Feature/Tenant/TenantFaqFeedbackTest.php
