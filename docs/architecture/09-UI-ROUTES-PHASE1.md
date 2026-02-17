# SecureCAT — UI Routes (Phase 1)

This document defines all Inertia.js page routes for Phase 1. Each route specifies the URL, component path, layout, props, user actions, and authorization.

> **Convention**: Pages are in `resources/js/Pages/{Module}/`. Layouts are in `resources/js/Layouts/`. All forms use shadcn-svelte components with Inertia's `useForm()` — reference `developing-gotchas.mdc` for form state binding.

---

## Authentication Routes

### Staff Login
| Aspect | Value |
|--------|-------|
| URL | `/login` |
| Component | `Pages/Auth/Login.svelte` |
| Layout | `GuestLayout.svelte` |
| Props | `{ errors }` |
| Actions | Submit → `POST /login` |
| Auth | Guest only |
| Form Considerations | Simple form — email, password, remember checkbox |

### Staff Dashboard (Post-Login Redirect)
| Aspect | Value |
|--------|-------|
| URL | `/dashboard` |
| Component | `Pages/Dashboard.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ user, stats }` — summary stats based on role |
| Actions | Navigation to role-appropriate sections |
| Auth | Any authenticated staff |

---

## User Management (Super Admin)

### Users List
| Aspect | Value |
|--------|-------|
| URL | `/admin/users` |
| Component | `Pages/Admin/Users/Index.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ users (paginated), roles, filters }` |
| Actions | Search, filter by role, create new, edit, delete |
| Auth | super_admin |

### Create User
| Aspect | Value |
|--------|-------|
| URL | `/admin/users/create` |
| Component | `Pages/Admin/Users/Create.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ roles }` |
| Actions | Submit → `POST /admin/users` |
| Auth | super_admin |
| Form Considerations | Multi-select for roles (shadcn multi-select or checkboxes) |

### Edit User
| Aspect | Value |
|--------|-------|
| URL | `/admin/users/{id}/edit` |
| Component | `Pages/Admin/Users/Edit.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ user, roles }` |
| Actions | Submit → `PUT /admin/users/{id}` |
| Auth | super_admin |

---

## Application Module (Staff)

### Applications List
| Aspect | Value |
|--------|-------|
| URL | `/applications` |
| Component | `Pages/Applications/Index.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ applications (paginated), filters, statuses }` |
| Actions | Search by name/reference, filter by status/date, view details |
| Auth | staff, admin, counselor, super_admin |

### Application Details
| Aspect | Value |
|--------|-------|
| URL | `/applications/{id}` |
| Component | `Pages/Applications/Show.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ application, courses }` |
| Actions | Accept → `PUT /applications/{id}/accept`, Reject (with modal) → `PUT /applications/{id}/reject`, Download slip |
| Auth | staff, admin, counselor, super_admin |
| Form Considerations | Rejection modal with textarea for reason |

### Public Application Form
| Aspect | Value |
|--------|-------|
| URL | `/apply` |
| Component | `Pages/Applications/Apply.svelte` |
| Layout | `GuestLayout.svelte` |
| Props | `{ courses, appointments (available slots) }` |
| Actions | Submit → `POST /applications` |
| Auth | Public |
| Form Considerations | **Complex form** — multiple sections (personal info, contact, course preferences). Use stepped form or accordion. Course preference dropdowns with validation (no duplicates). Optional appointment slot picker. Reference `developing-gotchas.mdc` for shadcn form binding. |

### Application Success
| Aspect | Value |
|--------|-------|
| URL | `/apply/success` |
| Component | `Pages/Applications/Success.svelte` |
| Layout | `GuestLayout.svelte` |
| Props | `{ reference_number, appointment_details? }` |
| Actions | None (confirmation page) |
| Auth | Public |

---

## Scheduling Module (Admin)

### Rooms List
| Aspect | Value |
|--------|-------|
| URL | `/admin/rooms` |
| Component | `Pages/Admin/Rooms/Index.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ rooms (paginated) }` |
| Actions | Create, edit, delete |
| Auth | admin, super_admin |

### Create/Edit Room (Modal or Page)
| Aspect | Value |
|--------|-------|
| URL | `/admin/rooms/create` or modal |
| Component | `Pages/Admin/Rooms/Create.svelte` (or inline modal) |
| Props | `{ room? }` for edit |
| Actions | Submit → `POST /admin/rooms` or `PUT /admin/rooms/{id}` |
| Auth | admin, super_admin |
| Form Considerations | Facilities as checkboxes or tags |

### Proctors List
| Aspect | Value |
|--------|-------|
| URL | `/admin/proctors` |
| Component | `Pages/Admin/Proctors/Index.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ proctors (paginated), users (for linking) }` |
| Actions | Create, edit, delete |
| Auth | admin, super_admin |

### Exam Sessions List
| Aspect | Value |
|--------|-------|
| URL | `/admin/exam-sessions` |
| Component | `Pages/Admin/ExamSessions/Index.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ sessions (paginated), filters, rooms }` |
| Actions | Create, edit, view, publish, set release date |
| Auth | admin, super_admin |

### Create Exam Session
| Aspect | Value |
|--------|-------|
| URL | `/admin/exam-sessions/create` |
| Component | `Pages/Admin/ExamSessions/Create.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ rooms, proctors }` |
| Actions | Submit → `POST /admin/exam-sessions` |
| Auth | admin, super_admin |
| Form Considerations | Date picker, time picker, room dropdown (shows capacity), proctor multi-select |

### Exam Session Details
| Aspect | Value |
|--------|-------|
| URL | `/admin/exam-sessions/{id}` |
| Component | `Pages/Admin/ExamSessions/Show.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ session, assigned_applicants, available_applicants, proctors }` |
| Actions | Assign applicants (table with checkboxes), remove applicants, publish → `POST .../publish`, set release date |
| Auth | admin, super_admin |
| Form Considerations | Bulk applicant assignment — data table with selection. Date picker for release date. |

---

## Examination Module (Proctor)

### Proctor Dashboard
| Aspect | Value |
|--------|-------|
| URL | `/proctor` |
| Component | `Pages/Proctor/Dashboard.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ assigned_sessions, today_sessions }` |
| Actions | Navigate to session roster |
| Auth | proctor |

### Session Roster
| Aspect | Value |
|--------|-------|
| URL | `/proctor/sessions/{id}` |
| Component | `Pages/Proctor/SessionRoster.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ session, applicants, stats }` |
| Actions | Search applicant, mark attendance (present/absent), log submission, start session, close session |
| Auth | proctor (assigned), admin |
| Form Considerations | **Real-time feel** — consider optimistic updates. Search input for quick lookup. Action buttons per row (attendance, submission). Bulk actions possible. Stats counter at top. |

---

## Grading Module (Grader)

### Grading Dashboard
| Aspect | Value |
|--------|-------|
| URL | `/grading` |
| Component | `Pages/Grading/Dashboard.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ grading_sessions, completed_exams_without_grading }` |
| Actions | Open new grading session, continue existing |
| Auth | grader |

### Grading Session
| Aspect | Value |
|--------|-------|
| URL | `/grading/sessions/{id}` |
| Component | `Pages/Grading/Session.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ session, applicants, domains, progress_stats }` |
| Actions | Select applicant, input scores, finalize session |
| Auth | grader |

### Applicant Score Input
| Aspect | Value |
|--------|-------|
| URL | `/grading/sessions/{sessionId}/applicants/{applicantId}` |
| Component | `Pages/Grading/ScoreInput.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ applicant, domains, existing_scores }` |
| Actions | Input raw score per domain, save → `PUT .../scores`, next applicant |
| Auth | grader |
| Form Considerations | **Complex form** — 6 domain score inputs. Number inputs with max validation. Optional item-level toggle (accordion per domain). Auto-calculate percentage. Save and navigate pattern. |

---

## Consultation Module (Counselor)

### Consultation Dashboard
| Aspect | Value |
|--------|-------|
| URL | `/consultation` |
| Component | `Pages/Consultation/Dashboard.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ applicants_pending, applicants_released, stats }` |
| Actions | Search applicants, view applicant consultation, manage rules |
| Auth | counselor |

### Decision Rules Management
| Aspect | Value |
|--------|-------|
| URL | `/consultation/rules` |
| Component | `Pages/Consultation/Rules/Index.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ rules, courses, domains }` |
| Actions | Create rule, edit, delete, filter by course/domain |
| Auth | counselor |
| Form Considerations | Filter dropdowns. Inline editing or modal for rule CRUD. Score range inputs with validation (min < max). |

### Create/Edit Rule (Modal recommended)
| Aspect | Value |
|--------|-------|
| URL | Modal on `/consultation/rules` |
| Props | `{ rule?, courses, domains }` |
| Form Considerations | Course dropdown, optional domain dropdown, min/max score inputs, note textarea |

### Applicant Consultation View
| Aspect | Value |
|--------|-------|
| URL | `/consultation/applicants/{id}` |
| Component | `Pages/Consultation/ApplicantView.svelte` |
| Layout | `AuthenticatedLayout.svelte` |
| Props | `{ applicant, application, scores, course_preferences, consultation_summary, matched_rules }` |
| Actions | View scores per domain, see matched decision rules, edit summary, set recommended course, add comments, release |
| Auth | counselor |
| Form Considerations | **Complex page** — multiple sections (applicant info, scores table/chart, matched rules display, editable summary form). Course dropdown for recommendation. Textarea for comments. Confirm dialog before release. |

---

## Applicant Portal

### Portal Login
| Aspect | Value |
|--------|-------|
| URL | `/portal/login` |
| Component | `Pages/Portal/Login.svelte` |
| Layout | `PortalGuestLayout.svelte` |
| Props | `{ errors }` |
| Actions | Submit → `POST /portal/login`, forgot password link |
| Auth | Portal guest |

### Password Setup
| Aspect | Value |
|--------|-------|
| URL | `/portal/setup/{token}` |
| Component | `Pages/Portal/Setup.svelte` |
| Layout | `PortalGuestLayout.svelte` |
| Props | `{ token, email }` |
| Actions | Submit → `POST /portal/setup/{token}` |
| Auth | Token-based |
| Form Considerations | Password + confirmation with strength indicator |

### Forgot Password
| Aspect | Value |
|--------|-------|
| URL | `/portal/forgot-password` |
| Component | `Pages/Portal/ForgotPassword.svelte` |
| Layout | `PortalGuestLayout.svelte` |
| Props | `{}` |
| Actions | Submit email → `POST /portal/forgot-password` |
| Auth | Portal guest |

### Portal Dashboard
| Aspect | Value |
|--------|-------|
| URL | `/portal` |
| Component | `Pages/Portal/Dashboard.svelte` |
| Layout | `PortalLayout.svelte` |
| Props | `{ applicant, status_tracker, exam_schedule, score_release, consultation, notifications }` |
| Actions | View status, view schedule, view countdown, view consultation (if released), mark notifications read |
| Auth | applicant |
| Form Considerations | **Read-only dashboard** with multiple cards/sections. Countdown component. Status stepper component. Notification bell with dropdown. Mobile-responsive layout critical. |

---

## Layouts

### GuestLayout.svelte
- Centered card layout
- Logo header
- Minimal navigation
- Used for: Staff login, public application

### PortalGuestLayout.svelte
- Portal-branded guest layout
- Used for: Applicant login, password setup

### AuthenticatedLayout.svelte
- Sidebar navigation (role-aware)
- Header with user dropdown
- Breadcrumbs
- Used for: All staff pages

### PortalLayout.svelte
- Portal-branded authenticated layout
- Simple header with logout
- Notification indicator
- Used for: Applicant dashboard

---

## Navigation Structure

### Staff Navigation (Sidebar)
Based on user roles, show:

| Role | Menu Items |
|------|------------|
| super_admin | Dashboard, Users, Applications, Scheduling, Rooms, Proctors, Grading, Consultation, Courses, Settings |
| staff | Dashboard, Applications |
| admin | Dashboard, Applications, Scheduling, Exam Sessions, Rooms, Proctors |
| proctor | Dashboard, My Sessions |
| grader | Dashboard, Grading |
| counselor | Dashboard, Applications (view), Grading (view), Consultation, Decision Rules |

### Applicant Portal Navigation
- Dashboard (single page with all surfaces)
- Notifications
- Profile (view-only)
- Logout

---

## Form-Heavy Pages Summary

The following pages have complex forms requiring attention to shadcn-svelte + Inertia integration:

1. **Public Application Form** (`/apply`) — Multi-section, stepped form
2. **Exam Session Applicant Assignment** — Bulk selection data table
3. **Proctor Session Roster** — Real-time updates, action buttons
4. **Score Input** — Multiple domain inputs, validation
5. **Applicant Consultation View** — Multi-section with editable summary

Reference `developing-gotchas.mdc` for implementation guidance on these pages.

---

## Mobile Responsiveness Notes

Priority mobile-responsive pages (WCAG 2.1 AA):
1. **Applicant Portal Dashboard** — Primary applicant access point
2. **Public Application Form** — May be accessed on mobile
3. **Proctor Session Roster** — Used on tablets in exam room

All other pages: Desktop-first with responsive fallback.
