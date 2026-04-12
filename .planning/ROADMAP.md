# SecureCAT-v2 Roadmap

## Current Milestone: E9 - Application Management

### Phase E1: Core User Management
- [x] User authentication
- [x] Role-based access control
- [x] Admin user CRUD

### Phase E2: Exam Scheduling
- [x] Create/edit exam sessions
- [x] Room assignment
- [x] Scheduling conflict detection

### Phase E3: Proctor Management
- [x] Proctor assignment
- [x] Session roster
- [x] Attendance tracking

### Phase E4: Grading System
- [x] Score entry
- [x] Grade computation
- [x] Result sheet generation

### Phase E5: Test Administration
- [x] Test management
- [x] Question banks
- [x] Randomization

### Phase E6: AI Integration
- [x] Knowledge documents
- [x] RAG system
- [x] AI companion chat

### Phase E7: Monitoring & Analytics
- [x] Real-time monitoring
- [x] Dashboard analytics
- [x] Reporting

### Phase E8: Application UI Improvements
- [x] UI fixes
- [x] Navigation consistency
- [x] Responsive design

### Phase E9: Application Management
- [ ] Application create/edit for staff/admin

### Phase E10: Bulk Applicant Data Import
- [ ] Import applicant records via CSV/spreadsheet
- [ ] Reusable import service infrastructure

### Phase E11: Bulk Score Import
- [ ] Import applicant scores via CSV/spreadsheet

### Phase 1: application create edit for staff admin registrar superadmin

**Goal:** Enable staff/admin to create and edit applications bypassing public application window restrictions
**Requirements:** REQ-APP-01, REQ-APP-02, REQ-APP-03, REQ-APP-04
**Depends on:** Phase 0
**Plans:** 2/2 plans complete

Plans:
- [x] 01-01-PLAN.md — Backend: UpdateApplicationRequest, policy create(), routes, controller methods
- [x] 01-02-PLAN.md — Frontend: Admin Create.svelte and Edit.svelte pages

### Phase 2: toast notification system with smooth sound

**Goal:** Brief, auto-dismissing toast notifications with optional sound for key events
**Requirements:** D-01, D-02, D-03, D-04, D-05, D-06, D-07
**Depends on:** Phase 1
**Plans:** 3/3 plans complete

Plans:
- [x] 02-01-PLAN.md — Infrastructure: svelte-french-toast package, sound, toast CSS
- [x] 02-02-PLAN.md — Frontend: ToastManager component, layouts integration
- [x] 02-03-PLAN.md — Integration: Polling triggers toast with edge cases

---

## Future Milestones

### Phase F1: Student Portal
- Student registration
- Self-service profile management

### Phase F2: Notification System
- [ ] In-app notifications for key events
- [ ] Bell icon with dropdown UI
- [ ] Poll-based notification delivery
- [ ] Application, grading, scheduling triggers

### Phase F3: Audit Logging
- Complete audit trail
- Activity logs
