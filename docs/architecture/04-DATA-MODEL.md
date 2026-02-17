# SecureCAT — Data Model

This document defines the database schema for Phase 1. Field types use Laravel/MySQL conventions.

---

## Entity Relationship Overview

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│    User     │       │   Course    │       │ Department  │
│  (staff)    │       │             │       │             │
└──────┬──────┘       └──────┬──────┘       └──────┬──────┘
       │                     │                     │
       │                     └─────────────────────┘
       │                              │
       │    ┌─────────────────────────┼─────────────────────────┐
       │    │                         │                         │
       ▼    ▼                         ▼                         │
┌─────────────────┐           ┌─────────────┐                   │
│   Applicant     │◄─────────►│  Application │                  │
│   (portal user) │           │              │                  │
└────────┬────────┘           └──────┬───────┘                  │
         │                           │                          │
         │    ┌──────────────────────┼──────────────────┐       │
         │    │                      │                  │       │
         ▼    ▼                      ▼                  ▼       │
┌─────────────────┐    ┌─────────────────┐    ┌────────────────┐│
│  ExamSession    │◄───│ SessionApplicant│───►│ Attendance     ││
│                 │    │                 │    │ Submission     ││
└────────┬────────┘    └─────────────────┘    └────────────────┘│
         │                                                      │
         │    ┌─────────────────────────────────────────────────┘
         │    │
         ▼    ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  GradingSession │───►│  ApplicantScore │───►│   ScoreItem     │
│                 │    │  (per domain)   │    │   (per item)    │
└────────┬────────┘    └─────────────────┘    └─────────────────┘
         │
         ▼
┌─────────────────┐    ┌─────────────────┐
│  Consultation   │───►│  DecisionRule   │
│  Summary        │    │  (counselor)    │
└─────────────────┘    └─────────────────┘
```

---

## Core Entities

### users
Staff and admin users (not applicants).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| name | varchar(255) | NO | — | Full name |
| email | varchar(255) | NO | — | Unique, login identifier |
| email_verified_at | timestamp | YES | NULL | |
| password | varchar(255) | NO | — | Hashed |
| remember_token | varchar(100) | YES | NULL | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `email` (unique)

---

### roles
Role definitions for RBAC.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| name | varchar(50) | NO | — | Unique: super_admin, staff, admin, proctor, grader, counselor |
| display_name | varchar(100) | NO | — | Human-readable |
| description | text | YES | NULL | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `name` (unique)

**Seed Data**:
- super_admin: System administrator, manages users and roles
- staff: Registrar staff, processes applications
- admin: Registrar admin, manages scheduling
- proctor: Guidance office, monitors exams
- grader: Guidance office, inputs scores
- counselor: Guidance office, releases consultations

---

### role_user (pivot)
Many-to-many: users can have multiple roles.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| user_id | bigint unsigned | NO | — | FK → users.id |
| role_id | bigint unsigned | NO | — | FK → roles.id |
| created_at | timestamp | YES | NULL | |

**Indexes**: `(user_id, role_id)` (unique)

---

### permissions
Granular permissions (optional for Phase 1 — can start with role-based).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| name | varchar(100) | NO | — | Unique: e.g., applications.view, applications.approve |
| display_name | varchar(150) | NO | — | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Note**: Permission assignment can be role-based initially. Granular permission_role pivot added if needed during sprint.

---

### departments

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| name | varchar(255) | NO | — | e.g., "College of Information Technology" |
| code | varchar(20) | NO | — | Unique: e.g., "CIT" |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `code` (unique)

---

### courses

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| department_id | bigint unsigned | NO | — | FK → departments.id |
| name | varchar(255) | NO | — | e.g., "Bachelor of Science in Information Technology" |
| code | varchar(20) | NO | — | Unique: e.g., "BSIT" |
| quota | int unsigned | YES | NULL | Max enrollees (NULL = unlimited) |
| score_cutoff | decimal(5,2) | YES | NULL | Minimum score threshold (TBD — expand during sprint) |
| is_active | boolean | NO | true | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `code` (unique), `department_id`

---

### applicants
Applicant portal users (separate from staff users).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| application_id | bigint unsigned | YES | NULL | FK → applications.id (set on acceptance) |
| email | varchar(255) | NO | — | Unique, from application |
| password | varchar(255) | YES | NULL | NULL until setup complete |
| setup_token | varchar(100) | YES | NULL | Password setup link token |
| setup_token_expires_at | timestamp | YES | NULL | Token expiry (48-72h) |
| email_verified_at | timestamp | YES | NULL | |
| remember_token | varchar(100) | YES | NULL | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `email` (unique), `setup_token`, `application_id`

---

### applications

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| reference_number | varchar(20) | NO | — | Unique, system-generated (e.g., APP-2026-00001) |
| first_name | varchar(100) | NO | — | |
| middle_name | varchar(100) | YES | NULL | |
| last_name | varchar(100) | NO | — | |
| suffix | varchar(20) | YES | NULL | e.g., Jr., III |
| birthdate | date | NO | — | |
| age | tinyint unsigned | NO | — | Computed or stored |
| sex | enum('male','female') | NO | — | |
| email | varchar(255) | NO | — | Contact email |
| phone | varchar(20) | YES | NULL | |
| address_line | varchar(255) | YES | NULL | Street address |
| city | varchar(100) | YES | NULL | |
| province | varchar(100) | YES | NULL | |
| zip_code | varchar(10) | YES | NULL | |
| course_preference_1 | bigint unsigned | NO | — | FK → courses.id |
| course_preference_2 | bigint unsigned | NO | — | FK → courses.id |
| course_preference_3 | bigint unsigned | NO | — | FK → courses.id |
| status | enum('pending','accepted','rejected') | NO | 'pending' | |
| processed_by | bigint unsigned | YES | NULL | FK → users.id (staff who processed) |
| processed_at | timestamp | YES | NULL | |
| rejection_reason | text | YES | NULL | |
| appointment_id | bigint unsigned | YES | NULL | FK → appointments.id (if booked) |
| submitted_at | timestamp | NO | — | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `reference_number` (unique), `email`, `status`, `last_name`

---

### appointments
Appointment slots for application submission.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| date | date | NO | — | |
| time_slot | time | NO | — | Start time |
| duration_minutes | smallint unsigned | NO | 30 | |
| max_slots | smallint unsigned | NO | — | Max applicants per slot |
| booked_count | smallint unsigned | NO | 0 | |
| is_active | boolean | NO | true | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `(date, time_slot)` (unique)

---

### rooms
Exam venue rooms.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| name | varchar(100) | NO | — | e.g., "Room 101" |
| building | varchar(100) | NO | — | e.g., "ITBR" |
| floor | varchar(20) | YES | NULL | e.g., "2nd Floor" |
| capacity | smallint unsigned | NO | — | Max examinees |
| facilities | json | YES | NULL | e.g., {"projector": true, "ac": true} |
| is_active | boolean | NO | true | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `(building, name)` (unique)

---

### proctors
Proctor assignments (can be linked to users or standalone).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| user_id | bigint unsigned | YES | NULL | FK → users.id (if proctor is a system user) |
| name | varchar(255) | NO | — | Display name |
| email | varchar(255) | YES | NULL | |
| phone | varchar(20) | YES | NULL | |
| is_active | boolean | NO | true | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `user_id`, `name`

---

### exam_sessions

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| room_id | bigint unsigned | NO | — | FK → rooms.id |
| date | date | NO | — | |
| start_time | time | NO | — | |
| end_time | time | YES | NULL | |
| status | enum('draft','published','in_progress','completed','cancelled') | NO | 'draft' | |
| published_at | timestamp | YES | NULL | |
| started_at | timestamp | YES | NULL | |
| closed_at | timestamp | YES | NULL | |
| score_release_date | date | YES | NULL | Admin-set countdown target |
| created_by | bigint unsigned | NO | — | FK → users.id (admin) |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `room_id`, `date`, `status`

---

### exam_session_proctor (pivot)
Proctors assigned to exam sessions.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| exam_session_id | bigint unsigned | NO | — | FK → exam_sessions.id |
| proctor_id | bigint unsigned | NO | — | FK → proctors.id |
| created_at | timestamp | YES | NULL | |

**Indexes**: `(exam_session_id, proctor_id)` (unique)

---

### session_applicants
Applicants assigned to exam sessions.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| exam_session_id | bigint unsigned | NO | — | FK → exam_sessions.id |
| applicant_id | bigint unsigned | NO | — | FK → applicants.id |
| attendance_status | enum('pending','present','absent') | NO | 'pending' | |
| attendance_marked_at | timestamp | YES | NULL | |
| attendance_marked_by | bigint unsigned | YES | NULL | FK → users.id (proctor) |
| submission_status | enum('pending','submitted') | NO | 'pending' | |
| submitted_at | timestamp | YES | NULL | |
| submitted_to | bigint unsigned | YES | NULL | FK → users.id (proctor who received) |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `(exam_session_id, applicant_id)` (unique), `attendance_status`, `submission_status`

---

### exam_domains
Score domains (6 domains per architecture).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| name | varchar(100) | NO | — | e.g., "Spatial Awareness" |
| code | varchar(20) | NO | — | Unique: e.g., "SPATIAL" |
| description | text | YES | NULL | |
| max_items | smallint unsigned | NO | — | Number of items in this domain |
| display_order | smallint unsigned | NO | 0 | |
| is_active | boolean | NO | true | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `code` (unique)

**Seed Data (TBD — expand during sprint)**:
- Domain 1: TBD
- Domain 2: TBD
- Domain 3: TBD
- Domain 4: TBD
- Domain 5: TBD
- Domain 6: TBD

---

### grading_sessions

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| exam_session_id | bigint unsigned | NO | — | FK → exam_sessions.id |
| status | enum('open','in_progress','review','finalized') | NO | 'open' | |
| opened_at | timestamp | YES | NULL | |
| opened_by | bigint unsigned | NO | — | FK → users.id (grader) |
| finalized_at | timestamp | YES | NULL | |
| finalized_by | bigint unsigned | YES | NULL | FK → users.id |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `exam_session_id`, `status`

---

### applicant_scores
Per-applicant, per-domain scores.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| grading_session_id | bigint unsigned | NO | — | FK → grading_sessions.id |
| applicant_id | bigint unsigned | NO | — | FK → applicants.id |
| domain_id | bigint unsigned | NO | — | FK → exam_domains.id |
| raw_score | smallint unsigned | NO | — | Items correct |
| max_score | smallint unsigned | NO | — | Total items |
| normalized_score | decimal(5,2) | YES | NULL | Computed later (Phase 2) |
| scored_by | bigint unsigned | NO | — | FK → users.id (grader) |
| scored_at | timestamp | NO | — | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `(grading_session_id, applicant_id, domain_id)` (unique)

---

### score_items
Item-level scores per domain (for detailed analysis / OMR future).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| applicant_score_id | bigint unsigned | NO | — | FK → applicant_scores.id |
| item_number | smallint unsigned | NO | — | 1-indexed |
| is_correct | boolean | NO | — | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `(applicant_score_id, item_number)` (unique)

**Note**: Item-level input optional for MVP. Can be derived from raw_score if not entered per-item.

---

### decision_rules
Counselor-defined rules for score ranges.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| course_id | bigint unsigned | NO | — | FK → courses.id |
| domain_id | bigint unsigned | YES | NULL | FK → exam_domains.id (NULL = overall) |
| min_score | decimal(5,2) | NO | — | Lower bound (inclusive) |
| max_score | decimal(5,2) | NO | — | Upper bound (inclusive) |
| note | text | NO | — | Counselor's note for this range |
| created_by | bigint unsigned | NO | — | FK → users.id (counselor) |
| is_active | boolean | NO | true | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `course_id`, `domain_id`

---

### consultation_summaries

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| applicant_id | bigint unsigned | NO | — | FK → applicants.id (unique) |
| status | enum('pending','draft','released') | NO | 'pending' | |
| recommended_course_id | bigint unsigned | YES | NULL | FK → courses.id |
| counselor_comments | text | YES | NULL | |
| system_notes | json | YES | NULL | Auto-generated notes from decision rules |
| counselor_id | bigint unsigned | YES | NULL | FK → users.id |
| released_at | timestamp | YES | NULL | |
| released_by | bigint unsigned | YES | NULL | FK → users.id |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `applicant_id` (unique), `status`

---

### notifications
In-app notification store.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | uuid | NO | — | PK |
| notifiable_type | varchar(255) | NO | — | Polymorphic: App\Models\Applicant |
| notifiable_id | bigint unsigned | NO | — | |
| type | varchar(255) | NO | — | Notification class name |
| data | json | NO | — | Notification payload |
| read_at | timestamp | YES | NULL | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |

**Indexes**: `(notifiable_type, notifiable_id)`, `read_at`

---

### audit_logs
Immutable event log (NFR-08).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | bigint unsigned | NO | auto | PK |
| auditable_type | varchar(255) | NO | — | Polymorphic model |
| auditable_id | bigint unsigned | NO | — | |
| event | varchar(50) | NO | — | created, updated, deleted, status_changed, etc. |
| old_values | json | YES | NULL | Before state |
| new_values | json | YES | NULL | After state |
| actor_type | varchar(255) | YES | NULL | User or Applicant |
| actor_id | bigint unsigned | YES | NULL | |
| ip_address | varchar(45) | YES | NULL | |
| user_agent | text | YES | NULL | |
| created_at | timestamp | NO | — | Immutable |

**Indexes**: `(auditable_type, auditable_id)`, `actor_id`, `event`, `created_at`

---

## Expand During Sprint

The following require clarification and will be finalized during implementation:

1. **Exam domain names and max_items**: 6 domains TBD
2. **Score normalization logic**: Currently stores raw_score; normalized_score computed in Phase 2
3. **Password policy specifics**: Min length, complexity (implement standard Laravel defaults)
4. **Setup token expiry**: Default 72 hours
5. **Admission slip fields**: Reference number, QR code data, applicant info
6. **File attachments table**: Deferred (labeled as future feature)
