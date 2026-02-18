# SecureCAT — Security Controls

This document defines authentication, authorization, and security requirements for Phase 1.

---

## 1. Authentication Strategy

### Staff Authentication (Users table)

| Aspect | Specification |
|--------|---------------|
| Method | Email + Password |
| Password Storage | bcrypt (cost factor 12) or Argon2id |
| Session Type | Server-side sessions (Laravel default) |
| Session Duration | 2 hours idle timeout, 8 hours max |
| Remember Me | Optional, 30-day token |
| MFA | Not in Phase 1 scope (deferred) |

### Applicant Authentication (Applicants table)

| Aspect | Specification |
|--------|---------------|
| Method | Email + Password |
| Account Creation | Auto-created on application acceptance |
| Password Setup | Email with time-limited setup link (72h expiry) |
| Password Storage | bcrypt (cost factor 12) or Argon2id |
| Session Type | Server-side sessions |
| Session Duration | 2 hours idle timeout |
| OTP Fallback | Email OTP for password recovery |
| Rate Limiting | 5 attempts per 15 minutes on login |

### Password Policy

| Rule | Requirement |
|------|-------------|
| Minimum Length | 8 characters |
| Complexity | At least 1 uppercase, 1 lowercase, 1 number |
| Prohibited | Cannot match email or name |
| History | Not enforced in Phase 1 |

---

## 2. Authorization Model

### Role Definitions

| Role | Office | Description |
|------|--------|-------------|
| super_admin | System | Creates users, assigns roles, system configuration |
| staff | Registrar | Processes applications, marks acceptance/rejection |
| admin | Registrar | Manages rooms, proctors, schedules; publishes exams |
| proctor | Guidance | Marks attendance, logs submissions during exam |
| grader | Guidance | Inputs scores, manages grading sessions |
| counselor | Guidance | Reviews scores, creates decision rules, releases consultations |

### Authorization Matrix

#### Application Module

| Action | super_admin | staff | admin | proctor | grader | counselor | applicant |
|--------|-------------|-------|-------|---------|--------|-----------|-----------|
| View all applications | ✓ | ✓ | ✓ | ✗ | ✗ | ✓ | ✗ |
| View own application | — | — | — | — | — | — | ✓ |
| Create application | ✗ | ✓ | ✗ | ✗ | ✗ | ✗ | ✓ (public) |
| Process (accept/reject) | ✗ | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Search applications | ✓ | ✓ | ✓ | ✗ | ✗ | ✓ | ✗ |

#### Scheduling Module

| Action | super_admin | staff | admin | proctor | grader | counselor |
|--------|-------------|-------|-------|---------|--------|-----------|
| View rooms | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Create/edit rooms | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ |
| View proctors | ✓ | ✗ | ✓ | ✓ | ✗ | ✗ |
| Create/edit proctors | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ |
| View exam sessions | ✓ | ✓ | ✓ | ✓ (assigned) | ✓ | ✓ |
| Create/edit sessions | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ |
| Publish schedule | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ |
| Set release date | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ |

#### Examination Module

| Action | super_admin | staff | admin | proctor | grader | counselor |
|--------|-------------|-------|-------|---------|--------|-----------|
| View assigned roster | ✓ | ✗ | ✓ | ✓ (own sessions) | ✗ | ✗ |
| Mark attendance | ✗ | ✗ | ✗ | ✓ (own sessions) | ✗ | ✗ |
| Log submission | ✗ | ✗ | ✗ | ✓ (own sessions) | ✗ | ✗ |
| Start/close session | ✗ | ✗ | ✓ | ✓ (own sessions) | ✗ | ✗ |
| Start/close outside schedule (override) | ✗ | ✗ | ✓ | ✗ (proctor only within window) | ✗ | ✗ |
| View session status | ✓ | ✗ | ✓ | ✓ | ✓ | ✓ |

**Note**: Start is allowed only within the session's scheduled date/time window; outside that window only admin/super_admin may start (override). Override audit logging to be added in a future task.

#### Grading Module

| Action | super_admin | staff | admin | proctor | grader | counselor |
|--------|-------------|-------|-------|---------|--------|-----------|
| View grading sessions | ✓ | ✗ | ✗ | ✗ | ✓ | ✓ |
| Open grading session | ✗ | ✗ | ✗ | ✗ | ✓ | ✗ |
| Input scores | ✗ | ✗ | ✗ | ✗ | ✓ | ✗ |
| Finalize session | ✗ | ✗ | ✗ | ✗ | ✓ | ✗ |
| View scores (read-only) | ✓ | ✗ | ✗ | ✗ | ✓ | ✓ |

#### Consultation Module

| Action | super_admin | staff | admin | proctor | grader | counselor |
|--------|-------------|-------|-------|---------|--------|-----------|
| View applicant scores | ✓ | ✗ | ✗ | ✗ | ✓ | ✓ |
| Create decision rules | ✗ | ✗ | ✗ | ✗ | ✗ | ✓ |
| Edit decision rules | ✗ | ✗ | ✗ | ✗ | ✗ | ✓ |
| Add counselor comments | ✗ | ✗ | ✗ | ✗ | ✗ | ✓ |
| Release consultation | ✗ | ✗ | ✗ | ✗ | ✗ | ✓ |
| View released summary | — | — | — | — | — | — | ✓ (own only) |

#### User Management

| Action | super_admin | staff | admin | proctor | grader | counselor |
|--------|-------------|-------|-------|---------|--------|-----------|
| View users | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Create users | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Assign roles | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Delete users | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ |
| Reset applicant password | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ |

---

## 3. Resource Scoping Rules

### Applicant Data Isolation
- **Applicants can only view their own data**: application, exam schedule, scores, consultation
- **No cross-applicant data visibility** in portal
- **Staff/Admin can search all applicants** within their authorized modules

### Proctor Session Scoping
- **Proctors can only access sessions they are assigned to**
- Cannot view or modify other sessions' rosters

### Counselor Access
- **Read access to all finalized scores** (needed for consultation)
- **Write access only to consultation summaries and decision rules**

---

## 4. API Security

### All Endpoints
- **Authentication required** except: login, password setup, application submission (public)
- **HTTPS enforced** (TLS 1.2+) in production
- **CSRF protection** on all form submissions (Inertia handles this)
- **Authorization middleware** on every route

### Rate Limiting

| Endpoint | Limit | Window |
|----------|-------|--------|
| POST /login | 5 attempts | 15 minutes |
| POST /forgot-password | 3 attempts | 15 minutes |
| POST /applications (public) | 10 submissions | 1 hour per IP |
| All authenticated endpoints | 60 requests | 1 minute |

### Input Validation
- **All inputs validated via Form Request classes**
- **No inline controller validation**
- **Sanitize HTML** in text fields (strip tags)

---

## 5. Session Security

| Setting | Value |
|---------|-------|
| Session Driver | Database or Redis |
| Cookie Name | securecat_session |
| Secure Cookie | true (production) |
| HTTP Only | true |
| Same Site | lax |
| Idle Timeout | 2 hours |
| Absolute Timeout | 8 hours |

### Session Invalidation
- On logout: immediate invalidation
- On password change: all other sessions invalidated
- Admin can force-invalidate applicant sessions (password reset)

---

## 6. Audit Requirements

### Events to Log (NFR-08)

| Event | Data Captured |
|-------|---------------|
| User login | user_id, ip, timestamp, success/failure |
| User logout | user_id, timestamp |
| Application submitted | application_id, ip, timestamp |
| Application status changed | application_id, old_status, new_status, actor_id |
| Attendance marked | session_applicant_id, actor_id, timestamp |
| Submission logged | session_applicant_id, actor_id, timestamp |
| Score entered | applicant_score_id, actor_id, old/new values |
| Consultation released | consultation_id, actor_id, timestamp |
| User created/modified | user_id, actor_id, changes |
| Role assigned/removed | user_id, role_id, actor_id |

### Audit Log Properties
- **Immutable**: No UPDATE or DELETE on audit_logs table
- **Retention**: Per institutional policy (minimum 3 years recommended)
- **Access**: Super Admin only (read-only)

---

## 7. Data Protection

### Sensitive Fields
- `users.password`: Hashed, never logged
- `applicants.password`: Hashed, never logged
- `applicants.setup_token`: Hashed or encrypted
- `applications.*`: PII — access controlled, logged

### Data in Transit
- TLS 1.2+ required for all connections
- No sensitive data in URL parameters

### Data at Rest
- Database encryption: Per deployment environment
- Backup encryption: Required

---

## 8. Expand During Sprint

1. **Granular permissions**: May add permission_role pivot if role-based is insufficient
2. **MFA for staff**: Deferred to Phase 2
3. **IP allowlisting for admin**: Consider for production
4. **Session token for applicants**: Currently server-side; evaluate JWT if needed for mobile
