## Feature Plan Index

Use these plans independently to avoid cross-feature ambiguity and reduce implementation hallucination risk.

1. plan-feature-01-faq-management.prompt.md
2. plan-feature-02-tenant-faq-view.prompt.md
3. plan-feature-03-complaint-system-core.prompt.md
4. plan-feature-04-general-complaint-flow.prompt.md
5. plan-feature-05-specific-complaint-flow.prompt.md
6. plan-feature-06-owner-complaint-dashboard.prompt.md
7. plan-feature-07-demerit-system-logic.prompt.md
8. plan-feature-08-warning-enforcement-flow.prompt.md
9. plan-feature-09-notification-system.prompt.md
10. plan-feature-10-smart-automation.prompt.md

### Recommended Execution Order
1. 03 Complaint System Core
2. 04 General Complaint Flow
3. 05 Specific Complaint Flow
4. 06 Owner Complaint Dashboard
5. 07 Demerit System Logic
6. 08 Warning and Enforcement Flow
7. 09 Notification System
8. 10 Smart Automation
9. 01 FAQ Management System
10. 02 Tenant FAQ View

### Notes
- Plans 01 and 02 are FAQ-focused and can be implemented in parallel with complaint tracks after schema alignment.
- Keep decisions fixed: demerits come from approved complaints only; FAQ scope is per property.
