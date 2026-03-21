## Feature 01 Plan: FAQ Management System

### Objective
Implement owner-managed FAQ content per property with CRUD, categories, tenant visibility scoping, and clean-content validation.

### Existing Foundation To Reuse
- routes/web.php
- resources/views/components/owner/sidebar-content.blade.php
- app/Livewire/Owner/Pages/Requests.php (pagination, search/filter patterns)
- app/Livewire/Concerns/HasToast.php
- app/Models/Owner.php

### Required Schema Changes
1. Create `faq_categories` table:
- id
- property_id (FK properties.id, indexed)
- name (string, indexed with property_id)
- sort_order (int default 0)
- is_active (bool default true)
- timestamps
2. Create `faqs` table:
- id
- property_id (FK properties.id, indexed)
- faq_category_id (nullable FK faq_categories.id)
- question (string, max 255)
- answer (text)
- is_visible (bool default true)
- sort_order (int default 0)
- created_by (FK users.id)
- updated_by (nullable FK users.id)
- timestamps
3. Add uniqueness guard:
- unique(property_id, name) on faq_categories

### New Files
- database/migrations/YYYY_MM_DD_HHMMSS_create_faq_categories_table.php
- database/migrations/YYYY_MM_DD_HHMMSS_create_faqs_table.php
- app/Models/FaqCategory.php
- app/Models/Faq.php
- app/Livewire/Owner/Pages/Faqs.php
- resources/views/livewire/owner/pages/faqs.blade.php

### Files To Modify
- routes/web.php
- resources/views/components/owner/sidebar-content.blade.php

### Implementation Steps
1. Add migrations for `faq_categories` and `faqs` with FK/index constraints.
2. Implement `FaqCategory` and `Faq` models with fillable, casts, and relationships.
3. Add owner route:
- GET /owner/faqs -> Owner\Pages\Faqs
4. Add owner sidebar entry for FAQs.
5. Build Owner Livewire page:
- list FAQs scoped to active property only
- create/edit/delete FAQ
- create/edit/delete category
- search by question/answer/category
- filter by category and visibility
6. Enforce validation:
- category name required, max length
- question required, trimmed, max 255
- answer required, min length, max safe bound
- reject blank-only content
7. Track creator/editor IDs on create/update.
8. Add sorting support by `sort_order` then latest updated.

### Authorization And Scope Rules
- Owner can read/write only FAQs where `property_id = auth()->user()->owner->active_property`.
- Category and FAQ cross-property assignment must be blocked in server-side validation.

### System Design Alignment
1. Role and scope alignment:
- Enforce server-side owner scope via active property on all FAQ/category read-write actions.
- Reject payloads that attempt cross-property category or FAQ assignment.
2. Laravel and Livewire pattern alignment:
- Reuse Livewire list/filter/pagination patterns from existing owner pages.
- Reuse shared concerns (for example toast and modal concerns) instead of new duplicate helpers.
3. Data model alignment:
- Follow existing FK naming and indexing conventions for query-heavy columns.
- Keep category uniqueness scoped to property and preserve sort ordering behavior.
4. Integration alignment:
- Keep FAQ management isolated from complaint/demerit flows.
- Ensure tenant FAQ consumers only read owner-published property-scoped data.
5. Dependency alignment:
- Feature dependencies: none.
- Must remain compatible with Feature 02 tenant FAQ consumption.

### System Design Checklist
1. All owner actions are server-scoped by active property.
2. Existing Livewire concern/pattern reuse is preserved.
3. Schema constraints and indexes match established conventions.
4. No coupling introduced with complaint or demerit modules.
5. Contracts required by Feature 02 remain stable.

### Verification Checklist
1. Owner can create, edit, delete FAQ entries.
2. Owner can create and reuse categories.
3. Owner cannot access another owner’s FAQs via URL or payload tampering.
4. Validation blocks empty/dirty content.
5. FAQ list supports search and category filtering.
6. New route appears in owner sidebar and loads correctly.

### Test Targets
- tests/Feature/Owner/FaqManagementTest.php
- tests/Unit/Models/FaqRelationshipsTest.php
