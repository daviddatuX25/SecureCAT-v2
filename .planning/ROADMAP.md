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

### Phase 1: application create edit for staff admin registrar superadmin

**Goal:** Enable staff/admin to create and edit applications bypassing public application window restrictions
**Requirements:** REQ-APP-01, REQ-APP-02, REQ-APP-03, REQ-APP-04
**Depends on:** Phase 0
**Plans:** 2/2 plans complete

Plans:
- [x] 01-01-PLAN.md — Backend: UpdateApplicationRequest, policy create(), routes, controller methods
- [x] 01-02-PLAN.md — Frontend: Admin Create.svelte and Edit.svelte pages

---

## Future Milestones

### Phase F1: Student Portal
- Student registration
- Self-service profile management

### Phase F2: Notification System
- Email notifications
- In-app alerts

### Phase F3: Audit Logging
- Complete audit trail
- Activity logs
