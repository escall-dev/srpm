# Owner Data Flow Diagram (DFD)

This document contains a multi-layer Data Flow Diagram for the system from the **Owner's** perspective, capturing the core workflows such as property management, lease administration, payment verification, and reporting.

## Context Diagram (Level 0)
The Context Diagram establishes the high-level boundary of the Property Management System.

```text
      ┌───────────────┐                             ┌───────────────┐
      │     Owner     │                             │   Property    │
      │ (Admin User)  │──────────► Property Data ──►│  Management   │
      │               │◄────────── Reports, Alerts │    System     │
      └───────────────┘                             │               │
                                              │  - Properties │
                                              │  - Leases     │
                                              │  - Payments   │
                                              │  - Requests   │
                                              └───────┬───────┘
                                                     │
                                  Receipts, Invoices     │
                                  Lease Updates          │
                                              ┌───────▼───────┐
                                              │    Tenant     │
                                              │ (End User)    │
                                              └───────────────┘
```

## Level 1 DFD: Owner Core Processes
The Level 1 diagram breaks down the main system into specific processes and data stores that the owner interacts with.

```text
                               ┌───────────────┐
                               │     Owner     │
                               │  (Admin User) │
                               └───────┬───────┘
                                       │
                                       ▼
                               ┌──────────────┐
                               │ 0.0 Login /  │
                               │   Auth       │
                               └──────┬───────┘
                                      │ validate
                                      ▼
                               ┌──────────────┐
                               │     D5       │
                               │ Users / Auth │
                               └──────┬───────┘
                                      │ session
                                      ▼
                         ┌──────────────────────────┐
                         │ 1.0 Dashboard (Home)     │
                         │ - KPIs & alerts          │
                         │ - Recent activity        │
                         └───────────┬─────────────┘
                                     │
                                     ▼
  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐
  │ Leases              │  │ Units/Rooms         │  │ Payments            │
  │ - View active leases│  │ - Manage unit status│  │ - Verify proof (POP)│
  │ - Create/renew      │  │ - Assign tenants    │  │ - Record payments   │
  └──────────┬──────────┘  └──────────┬──────────┘  └──────────┬──────────┘
             │                        │                        │
             ▼                        ▼                        ▼
         ┌────────────┐          ┌────────────┐          ┌────────────┐
         │    D2      │          │    D1      │          │    D3      │
         │ Tenants /  │          │ Properties │          │ Payments / │
         │ Leases DB  │          │ / Units DB │          │ Expenses   │
         └────────────┘          └────────────┘          └────────────┘

  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐
  │ Expenses            │  │ Complaints/Requests │  │ Properties          │
  │ - Log expenses      │  │ - Review tickets    │  │ - Add/edit property │
  │ - Attach receipts   │  │ - Update status     │  │ - Set rules/fees    │
  └──────────┬──────────┘  └──────────┬──────────┘  └──────────┬──────────┘
             │                        │                        │
             ▼                        ▼                        ▼
         ┌────────────┐          ┌────────────┐          ┌────────────┐
         │    D3      │          │    D4      │          │    D1      │
         │ Payments / │          │ Requests / │          │ Properties │
         │ Expenses   │          │ Complaints │          │ / Units DB │
         └────────────┘          └────────────┘          └────────────┘

  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐
  │ Automation Logs     │  │ FAQs                │  │ Settings            │
  │ - View runs/errors  │  │ - Read guides        │  │ - Profile/security  │
  │ - Export logs       │  │ - Update entries    │  │ - Preferences       │
  └─────────────────────┘  └─────────────────────┘  └─────────────────────┘

  ┌─────────────────────┐  ┌─────────────────────┐
  │ Revisit Tour        │  │ Logout              │
  │ - Restart onboarding│  │ - End session       │
  │ - View tips         │  │ - Clear access      │
  └─────────────────────┘  └─────────────────────┘
```
