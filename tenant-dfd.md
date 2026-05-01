# Tenant Data Flow Diagram (DFD)

This document contains a multi-layer Data Flow Diagram for the system from the **Tenant's** perspective, showing how they interact with their lease data, submit payments, and raise requests.

## Context Diagram (Level 0)
The Context Diagram establishes what the system takes from the Tenant and what it returns back.

```text
      ┌───────────────┐                             ┌───────────────┐
      │    Tenant     │                             │   Property    │
      │  (End User)   │────────► Payments, Requests │  Management   │
      │               │◄──────── Lease Data, Notices│    System     │
      └───────────────┘                             │               │
                                              │  - Leases     │
                                              │  - Balances   │
                                              │  - Tickets    │
                                              │  - Receipts   │
                                              └───────┬───────┘
                                                     │
                                  Review/Approve         │
                                              ┌───────▼───────┐
                                              │     Owner     │
                                              │ (Admin User)  │
                                              └───────────────┘
```

## Level 1 DFD: Tenant Core Processes
The Level 1 diagram highlights the internal processes tailored to the tenant experience.

```text
                               ┌───────────────┐
                               │    Tenant     │
                               │  (End User)   │
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
                         │ - Balances & alerts      │
                         │ - Recent payments        │
                         └───────────┬─────────────┘
                                     │
                                     ▼
  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐
  │ My Leases           │  │ Payments            │  │ Complaints/Requests │
  │ - View lease terms  │  │ - Upload POP        │  │ - File ticket       │
  │ - Check due dates   │  │ - View receipts     │  │ - Track status      │
  └──────────┬──────────┘  └──────────┬──────────┘  └──────────┬──────────┘
             │                        │                        │
             ▼                        ▼                        ▼
         ┌────────────┐          ┌────────────┐          ┌────────────┐
         │    D1      │          │    D2      │          │    D3      │
         │ Tenants /  │          │ Payments / │          │ Requests / │
         │ Leases DB  │          │ Penalties  │          │ Complaints │
         └────────────┘          └────────────┘          └────────────┘

  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐
  │ FAQs                │  │ Settings            │  │ Revisit Tour        │
  │ - Read help topics  │  │ - Update profile    │  │ - Restart onboarding│
  │ - View policies     │  │ - Change password   │  │ - View tips         │
  └─────────────────────┘  └─────────────────────┘  └─────────────────────┘

  ┌─────────────────────┐
  │ Logout              │
  │ - End session       │
  │ - Clear access      │
  └─────────────────────┘
```
