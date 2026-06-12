# Role Model Analysis & RBAC Evolution
## SecureCAT-v2 Governance Framework

> [!IMPORTANT]
> **Purpose:** Document the transition from the informal, shared-account administrative model used in the deployed baseline (Phase 1) to the zero-trust Role-Based Access Control (RBAC) governance framework engineered in SecureCAT-v2 (Phase 2).
>
> This analysis serves as empirical evidence for Chapter 1 data governance problems and informs the backend policies and route gating implemented in the codebase.

---

## 1. The Deployed Baseline (Phase 1 Shared Account)

During the Phase 1 deployment at the Guidance Office, staff were provided a single **Super Admin** credential set. While this was useful for exploring the system's features, an analysis of their usage patterns revealed several operational and security vulnerabilities:

1.  **Shared-Account Accountability Failure:** Multiple staff members logged in using the same credentials, making it impossible to trace who modified applicant files or recorded exam scores.
2.  **Privilege Creep / Excessive Access:** Guidance counselors and OJT assistants had full system capabilities, including creating user accounts and system parameter configuration, which are Registrar functions.
3.  **Cross-Department Security Risk:** Guidance and Registrar staff operate under different administrative purviews, yet the shared account bypassed this separation of duties.

---

## 2. SecureCAT-v2 Role Hierarchy

SecureCAT-v2 enforces a strict, six-role taxonomy. Each role is bound to specific system policies, preventing cross-role API manipulation even if client-side views are bypassed.

| Role | Access Boundary | Primary Operations | Laravel Policy Guard Example |
|---|---|---|---|
| **Applicant / Examinee** | Self-data only | Register application, view status, download admission slip, take test, query AI companion. | `ApplicantPolicy@view` |
| **Proctor** | Assigned exam session | Confirm examinee attendance, track proctor status, check in candidates (offline/online). | `ExamSessionPolicy@proctor` |
| **Test Administrator** | All exam sessions | Schedule exam sessions, assign proctors, import raw test scores via CV-OMR or CSV. | `ExamSessionPolicy@manage` |
| **Guidance Counselor** | Score reports & results | Review candidate scores, write consultation comments, recommend college programs. | `ConsultationPolicy@create` |
| **Registrar Staff** | Application data | Review and approve/reject applications, encode demographics, manage course lists. | `ApplicationPolicy@review` |
| **Registrar Admin** | System setup & schedules | Approve schedules, configure rooms/buildings, supervise overall operations, review AI scheduling proposals. | `SchedulePolicy@configure` |
| **Super Administrator** | System governance | Manage user creation, assign roles to personnel, view and export comprehensive write-only security audit logs. | `UserPolicy@manage` |

---

## 3. Role Evolution & Multi-Campus Expansion

As the system moves toward a multi-tenant database architecture to support multiple ISPSC campuses, the role model must evolve to support hierarchical isolation:

### Proposed Addition: Campus Administrator
In multi-tenant operations, a **Campus Administrator** role is required to manage campus-specific rooms, courses, and schedules without seeing data from other campuses.

*   *Hierarchy Position:* Sitting between Registrar Admin and Super Admin.
*   *Tenancy Constraint:* Access is scoped to a specific `Tenant ID` (e.g., Tagudin Campus vs. Santa Maria Campus).
*   *Super Admin Role:* The Super Admin remains tenant-agnostic, managing global user provisioning and system maintenance across all tenants.

---

## 4. Transition Strategy: Shared Admin to Zero-Trust

```mermaid
graph TD
    A["Phase 1: Shared Super Admin Account"] -->|DPA (RA 10173) Compliance Check| B{"Segregation of Duties Required?"}
    B -->|Yes| C["Phase 2: SecureCAT-v2 RBAC (6 Roles)"]
    C -->|API Gating| D["Laravel Policies & Form Request validation"]
    C -->|Accountability| E["Immutable Write-Only Audit Logging (actor-bound)"]
    C -->|Future Expansion| F["Multi-Tenant Campus Isolation (Tenant-scoped Admin)"]
```
This migration path satisfies the data privacy requirements of RA 10173 and provides the institution with clear, auditable trail evidence of data integrity.
