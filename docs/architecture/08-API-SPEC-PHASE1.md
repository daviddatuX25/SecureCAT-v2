# SecureCAT — API Specification (Phase 1)

This document defines all Phase 1 endpoints. Each endpoint includes method, path, purpose, inputs, outputs, business rules, and authorization.

> **Convention**: All endpoints return JSON for API calls. Inertia pages return `Inertia::render()`. Error responses follow Laravel's standard validation error format.

---

## Auth Module

### POST /login
**Purpose**: Authenticate staff user

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| email | string | Yes | Valid email, exists in users table |
| password | string | Yes | Min 8 chars |
| remember | boolean | No | Default false |

**Outputs**:
- **200**: Redirect to dashboard (Inertia)
- **422**: Validation errors `{ errors: { email: [...], password: [...] } }`
- **429**: Rate limit exceeded

**Business Rules**:
- Lock account for 15 minutes after 5 failed attempts
- Log all login attempts (success/failure)

**Authorization**: Public

**Rate Limit**: 5 attempts / 15 minutes

---

### POST /logout
**Purpose**: End staff session

**Inputs**: None (CSRF token required)

**Outputs**:
- **200**: Redirect to login

**Business Rules**:
- Invalidate session immediately
- Log logout event

**Authorization**: Authenticated user

---

### POST /portal/login
**Purpose**: Authenticate applicant

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| email | string | Yes | Valid email, exists in applicants table |
| password | string | Yes | Min 8 chars |

**Outputs**:
- **200**: Redirect to portal dashboard
- **422**: Validation errors
- **429**: Rate limit exceeded

**Business Rules**:
- Account must have password set (setup complete)
- Log login attempts

**Authorization**: Public

**Rate Limit**: 5 attempts / 15 minutes

---

### GET /portal/setup/{token}
**Purpose**: Display password setup form for new applicant

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| token | string (path) | Yes | Valid, non-expired setup token |

**Outputs**:
- **200**: Render password setup page
- **404**: Invalid or expired token

**Authorization**: Public (token-based)

---

### POST /portal/setup/{token}
**Purpose**: Set initial password for applicant account

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| token | string (path) | Yes | Valid, non-expired |
| password | string | Yes | Min 8, 1 upper, 1 lower, 1 number |
| password_confirmation | string | Yes | Must match password |

**Outputs**:
- **200**: Redirect to portal login with success message
- **422**: Validation errors
- **404**: Invalid or expired token

**Business Rules**:
- Invalidate token after successful setup
- Log account activation

**Authorization**: Public (token-based)

---

### POST /portal/forgot-password
**Purpose**: Request password reset OTP

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| email | string | Yes | Valid email |

**Outputs**:
- **200**: Success message (always, to prevent email enumeration)

**Business Rules**:
- Send OTP email if account exists
- OTP valid for 15 minutes
- Rate limit enforced

**Authorization**: Public

**Rate Limit**: 3 attempts / 15 minutes

---

## User Management Module (Super Admin)

### GET /admin/users
**Purpose**: List all staff users

**Inputs** (query):
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| search | string | No | Name or email search |
| role | string | No | Filter by role name |
| page | integer | No | Pagination |

**Outputs**:
- **200**: Paginated user list with roles
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "roles": ["staff", "admin"],
      "created_at": "2026-01-15T10:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 5, "total": 50 }
}
```

**Authorization**: super_admin

---

### POST /admin/users
**Purpose**: Create new staff user

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| name | string | Yes | Max 255 |
| email | string | Yes | Valid email, unique |
| password | string | Yes | Min 8, complexity rules |
| roles | array | Yes | Array of valid role names |

**Outputs**:
- **201**: Created user object
- **422**: Validation errors

**Business Rules**:
- Cannot create super_admin role via this endpoint (seed only)
- Log user creation

**Authorization**: super_admin

---

### PUT /admin/users/{id}
**Purpose**: Update staff user

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| name | string | No | Max 255 |
| email | string | No | Valid email, unique except self |
| password | string | No | Min 8 if provided |
| roles | array | No | Array of valid role names |

**Outputs**:
- **200**: Updated user object
- **404**: User not found
- **422**: Validation errors

**Business Rules**:
- Cannot remove own super_admin role
- Log changes

**Authorization**: super_admin

---

### DELETE /admin/users/{id}
**Purpose**: Deactivate staff user (soft delete)

**Outputs**:
- **200**: Success
- **404**: User not found
- **403**: Cannot delete self

**Authorization**: super_admin

---

## Application Module

### GET /applications
**Purpose**: List applications with filters

**Inputs** (query):
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| search | string | No | Name or reference number |
| status | string | No | pending, accepted, rejected |
| date_from | date | No | YYYY-MM-DD |
| date_to | date | No | YYYY-MM-DD |
| page | integer | No | |

**Outputs**:
- **200**: Paginated applications
```json
{
  "data": [
    {
      "id": 1,
      "reference_number": "APP-2026-00001",
      "full_name": "Juan Dela Cruz",
      "email": "juan@example.com",
      "status": "pending",
      "submitted_at": "2026-02-15T09:00:00Z",
      "course_preferences": [
        { "rank": 1, "course": { "id": 1, "code": "BSIT", "name": "..." } },
        { "rank": 2, "course": { "id": 2, "code": "BSCS", "name": "..." } },
        { "rank": 3, "course": { "id": 3, "code": "BSIS", "name": "..." } }
      ]
    }
  ],
  "meta": { ... }
}
```

**Authorization**: staff, admin, counselor, super_admin

---

### GET /applications/{id}
**Purpose**: View single application details

**Outputs**:
- **200**: Full application object with all fields
- **404**: Not found

**Authorization**: staff, admin, counselor, super_admin

---

### POST /applications
**Purpose**: Submit new application (public or staff-assisted)

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| first_name | string | Yes | Max 100 |
| middle_name | string | No | Max 100 |
| last_name | string | Yes | Max 100 |
| suffix | string | No | Max 20 |
| birthdate | date | Yes | YYYY-MM-DD, age 15-50 |
| sex | string | Yes | male, female |
| email | string | Yes | Valid email |
| phone | string | No | Max 20 |
| address_line | string | No | Max 255 |
| city | string | No | Max 100 |
| province | string | No | Max 100 |
| zip_code | string | No | Max 10 |
| course_preference_1 | integer | Yes | Valid course ID |
| course_preference_2 | integer | Yes | Valid course ID, different from 1 |
| course_preference_3 | integer | Yes | Valid course ID, different from 1 & 2 |
| appointment_id | integer | No | Valid appointment ID if booking |

**Outputs**:
- **201**: Created application with reference number
- **422**: Validation errors

**Business Rules**:
- Generate unique reference number: `APP-{YEAR}-{SEQUENCE}`
- If appointment_id provided, increment appointment booked_count
- Log submission

**Authorization**: Public (rate limited) or staff

**Rate Limit**: 10 / hour per IP (public)

---

### PUT /applications/{id}/accept
**Purpose**: Mark application as accepted

**Inputs**: None (action endpoint)

**Outputs**:
- **200**: Updated application
- **404**: Not found
- **409**: Already processed

**Business Rules**:
- Status must be `pending`
- Create applicant portal account (auto)
- Send setup email (async)
- Generate admission slip (async)
- Log acceptance with staff ID

**Authorization**: staff

---

### PUT /applications/{id}/reject
**Purpose**: Mark application as rejected

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| reason | string | Yes | Max 500 |

**Outputs**:
- **200**: Updated application
- **404**: Not found
- **409**: Already processed

**Business Rules**:
- Status must be `pending`
- Log rejection with reason

**Authorization**: staff

---

### GET /applications/{id}/admission-slip
**Purpose**: Download admission slip PDF

**Outputs**:
- **200**: PDF file (application/pdf)
- **404**: Not found
- **403**: Application not accepted

**Business Rules**:
- Only available for accepted applications
- Include: reference number, name, photo placeholder, QR code, exam info (if scheduled)

**Authorization**: staff, admin, applicant (own)

---

## Appointment Module

### GET /appointments
**Purpose**: List available appointment slots

**Inputs** (query):
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| date_from | date | No | |
| date_to | date | No | |
| available_only | boolean | No | Default true |

**Outputs**:
- **200**: List of appointment slots
```json
{
  "data": [
    {
      "id": 1,
      "date": "2026-02-20",
      "time_slot": "09:00",
      "duration_minutes": 30,
      "max_slots": 10,
      "booked_count": 3,
      "available": 7
    }
  ]
}
```

**Authorization**: Public (for booking), staff, admin

---

### POST /appointments
**Purpose**: Create appointment slot (admin)

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| date | date | Yes | Future date |
| time_slot | time | Yes | HH:MM |
| duration_minutes | integer | No | Default 30 |
| max_slots | integer | Yes | Min 1 |

**Authorization**: admin, super_admin

---

## Scheduling Module

### GET /admin/rooms
**Purpose**: List exam rooms

**Outputs**:
- **200**: List of rooms with capacity

**Authorization**: admin, super_admin, proctor

---

### POST /admin/rooms
**Purpose**: Create exam room

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| name | string | Yes | Max 100 |
| building | string | Yes | Max 100 |
| floor | string | No | Max 20 |
| capacity | integer | Yes | Min 1 |
| facilities | object | No | JSON: { projector, ac, etc. } |

**Authorization**: admin, super_admin

---

### PUT /admin/rooms/{id}
**Purpose**: Update room

**Authorization**: admin, super_admin

---

### DELETE /admin/rooms/{id}
**Purpose**: Deactivate room

**Business Rules**:
- Cannot delete if assigned to future sessions

**Authorization**: admin, super_admin

---

### GET /admin/proctors
**Purpose**: List proctors

**Authorization**: admin, super_admin

---

### POST /admin/proctors
**Purpose**: Create proctor

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| name | string | Yes | Max 255 |
| email | string | No | Valid email |
| phone | string | No | Max 20 |
| user_id | integer | No | Link to existing user |

**Authorization**: admin, super_admin

---

### GET /admin/exam-sessions
**Purpose**: List exam sessions. Same route used by admin (full list) and proctor (assigned only); response includes a `view` prop (`admin` or `proctor`) so the UI can show the correct title and hide create/edit for proctors.

**Inputs** (query):
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| status | string | No | draft, published, in_progress, completed |
| date_from | date | No | |
| date_to | date | No | |

**Authorization**: admin, super_admin (full list); proctor (sessions where current user is assigned only)

---

### POST /admin/exam-sessions
**Purpose**: Create exam session

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| room_id | integer | Yes | Valid room |
| date | date | Yes | Future date |
| start_time | time | Yes | HH:MM |
| end_time | time | No | HH:MM, after start_time |
| proctor_ids | array | No | Array of valid proctor IDs |

**Outputs**:
- **201**: Created session
- **422**: Validation errors
- **409**: Room conflict (already scheduled at that time)

**Business Rules**:
- Check room availability (no overlapping sessions)
- Status defaults to `draft`

**Authorization**: admin, super_admin

---

### PUT /admin/exam-sessions/{id}
**Purpose**: Update exam session

**Business Rules**:
- Cannot edit if status is `completed`
- If published, edits trigger re-notification (confirm with user)

**Authorization**: admin, super_admin

---

### POST /admin/exam-sessions/{id}/assign-applicants
**Purpose**: Assign applicants to exam session

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| applicant_ids | array | Yes | Array of accepted applicant IDs |

**Outputs**:
- **200**: Updated session with assigned count
- **422**: Capacity exceeded

**Business Rules**:
- Cannot exceed room capacity
- Applicant must be accepted
- Applicant cannot be assigned to multiple sessions

**Authorization**: admin, super_admin

---

### POST /admin/exam-sessions/{id}/publish
**Purpose**: Publish exam schedule

**Outputs**:
- **200**: Published session
- **409**: No applicants assigned

**Business Rules**:
- Status changes to `published`
- Trigger notifications to all assigned applicants
- Set published_at timestamp

**Authorization**: admin, super_admin

---

### PUT /admin/exam-sessions/{id}/release-date
**Purpose**: Set score release countdown date

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| score_release_date | date | Yes | Future date |

**Business Rules**:
- Trigger notification if date changes after initial set
- Applicants see countdown in portal

**Authorization**: admin, super_admin

---

## Examination Module

**Implementation note**: Proctors list their assigned sessions via **GET /admin/exam-sessions** (same route as admin; backend scopes to assigned and returns `view: 'proctor'`). A dedicated `GET /proctor/sessions` may be added later for session-roster flows.

### GET /proctor/sessions (optional / future)
**Purpose**: List proctor's assigned sessions (alternative: use GET /admin/exam-sessions with proctor role, which returns scoped list)

**Outputs**:
- **200**: Sessions where current user is assigned proctor

**Authorization**: proctor

---

### GET /proctor/sessions/{id}/roster
**Purpose**: Get applicant roster for session

**Outputs**:
- **200**: List of assigned applicants with attendance/submission status
```json
{
  "session": { "id": 1, "room": "...", "date": "...", "status": "..." },
  "applicants": [
    {
      "id": 1,
      "session_applicant_id": 10,
      "name": "Juan Dela Cruz",
      "reference_number": "APP-2026-00001",
      "attendance_status": "pending",
      "submission_status": "pending"
    }
  ],
  "stats": {
    "total": 30,
    "present": 25,
    "absent": 2,
    "pending": 3,
    "submitted": 20
  }
}
```

**Authorization**: proctor (assigned), admin, super_admin

---

### POST /proctor/sessions/{id}/attendance
**Purpose**: Mark attendance for applicant

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| applicant_id | integer | Yes | Must be assigned to session |
| status | string | Yes | present, absent |

**Outputs**:
- **200**: Updated attendance record
- **409**: Already marked

**Business Rules**:
- Session must be `published` or `in_progress`
- Log with proctor ID and timestamp
- Immutable once set (audit requirement)

**Authorization**: proctor (assigned)

---

### POST /proctor/sessions/{id}/submission
**Purpose**: Log exam submission

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| applicant_id | integer | Yes | Must be assigned and present |

**Outputs**:
- **200**: Updated submission record
- **409**: Already submitted or not present

**Business Rules**:
- Applicant must have attendance_status = `present`
- Session must be `in_progress`
- Log with timestamp and proctor ID

**Authorization**: proctor (assigned)

---

### POST /proctor/sessions/{id}/start
**Purpose**: Start exam session

**Outputs**:
- **200**: Session status updated to `in_progress`
- **409**: Invalid status transition, or outside scheduled window (proctor only; admin/super_admin may override)

**Business Rules**:
- Must be `published`
- Start allowed only within the scheduled window (session date + start_time/end_time with fixed grace: 15 min before start, 30 min after end). Outside that window, only admin/super_admin may start (override). Override logging and override_reason deferred to a future task.
- Log start time (`started_at`)

**Authorization**: proctor (assigned), admin, super_admin. Proctors may start only within the schedule window; admin/super_admin may start outside the window (override).

---

### POST /proctor/sessions/{id}/close
**Purpose**: Close exam session

**Outputs**:
- **200**: Session status updated to `completed`

**Business Rules**:
- Must be `in_progress`
- Log close time
- Unsubmitted applicants remain as `pending` (proctor can mark absent)

**Authorization**: proctor (assigned), admin

---

## Grading Module

### GET /grading/sessions
**Purpose**: List grading sessions

**Inputs** (query):
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| status | string | No | open, in_progress, review, finalized |

**Authorization**: grader, counselor, super_admin

---

### POST /grading/sessions
**Purpose**: Open grading session for completed exam

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| exam_session_id | integer | Yes | Must be completed exam session |

**Outputs**:
- **201**: Created grading session
- **409**: Grading session already exists for exam

**Business Rules**:
- Exam session must be `completed`
- Trigger "scores being processed" notification
- Log with grader ID

**Authorization**: grader

---

### GET /grading/sessions/{id}
**Purpose**: Get grading session with applicants

**Outputs**:
- **200**: Session with list of applicants and score status

**Authorization**: grader, counselor

---

### GET /grading/sessions/{id}/applicants/{applicantId}/scores
**Purpose**: Get scores for specific applicant

**Outputs**:
- **200**: Scores per domain
```json
{
  "applicant": { "id": 1, "name": "..." },
  "scores": [
    {
      "domain_id": 1,
      "domain_name": "Spatial Awareness",
      "raw_score": 18,
      "max_score": 25,
      "items": [ { "item_number": 1, "is_correct": true }, ... ]
    }
  ]
}
```

**Authorization**: grader, counselor

---

### PUT /grading/sessions/{id}/applicants/{applicantId}/scores
**Purpose**: Input/update scores for applicant

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| scores | array | Yes | Array of domain scores |
| scores.*.domain_id | integer | Yes | Valid domain |
| scores.*.raw_score | integer | Yes | 0 to max_items |
| scores.*.items | array | No | Optional item-level detail |

**Outputs**:
- **200**: Updated scores
- **422**: Validation errors

**Business Rules**:
- Session must be `open` or `in_progress`
- Log all score changes (audit)
- Update session status to `in_progress` if first input

**Authorization**: grader

---

### POST /grading/sessions/{id}/finalize
**Purpose**: Finalize grading session

**Outputs**:
- **200**: Session marked as `finalized`
- **422**: Not all applicants scored

**Business Rules**:
- All submitted applicants must have scores
- Status changes to `finalized`
- Scores become read-only
- Log with grader ID

**Authorization**: grader

---

## Consultation Module

### GET /consultation/applicants
**Purpose**: List applicants with finalized scores for consultation

**Inputs** (query):
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| status | string | No | pending, draft, released |
| search | string | No | Name or reference |

**Authorization**: counselor, super_admin

---

### GET /consultation/applicants/{id}
**Purpose**: View applicant profile with scores for consultation

**Outputs**:
- **200**: Full applicant data, scores, course preferences, consultation status

**Authorization**: counselor

---

### GET /consultation/rules
**Purpose**: List decision rules

**Inputs** (query):
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| course_id | integer | No | Filter by course |
| domain_id | integer | No | Filter by domain |

**Authorization**: counselor

---

### POST /consultation/rules
**Purpose**: Create decision rule

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| course_id | integer | Yes | Valid course |
| domain_id | integer | No | NULL for overall score |
| min_score | decimal | Yes | |
| max_score | decimal | Yes | > min_score |
| note | string | Yes | Max 1000 |

**Authorization**: counselor

---

### PUT /consultation/rules/{id}
**Purpose**: Update decision rule

**Authorization**: counselor (creator only or any counselor — TBD)

---

### DELETE /consultation/rules/{id}
**Purpose**: Deactivate rule

**Authorization**: counselor

---

### PUT /consultation/applicants/{id}/summary
**Purpose**: Update consultation summary (draft)

**Inputs**:
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| recommended_course_id | integer | No | Valid course |
| counselor_comments | string | No | Max 2000 |

**Outputs**:
- **200**: Updated summary (status = draft)

**Business Rules**:
- Creates summary if not exists
- Auto-populate system_notes from matching decision rules

**Authorization**: counselor

---

### POST /consultation/applicants/{id}/release
**Purpose**: Release consultation summary to applicant

**Outputs**:
- **200**: Summary released
- **422**: Missing required fields (recommended course)

**Business Rules**:
- Status changes to `released`
- Trigger notification to applicant
- Log with counselor ID and timestamp
- Immutable after release

**Authorization**: counselor

---

## Applicant Portal

### GET /portal/dashboard
**Purpose**: Applicant portal dashboard

**Outputs**:
- **200**: Inertia page with all portal surfaces
```json
{
  "applicant": { "name": "...", "reference_number": "..." },
  "status_tracker": [
    { "stage": "Application Submitted", "completed": true, "timestamp": "..." },
    { "stage": "Application Accepted", "completed": true, "timestamp": "..." },
    ...
  ],
  "exam_schedule": {
    "assigned": true,
    "room": "ITBR Room 101",
    "building": "IT Building",
    "floor": "1st Floor",
    "date": "2026-03-01",
    "time": "09:00"
  },
  "score_release": {
    "date_set": true,
    "release_date": "2026-03-15",
    "countdown_seconds": 1234567
  },
  "consultation": {
    "status": "pending", // or "released"
    "summary": null // populated when released
  },
  "notifications": [
    { "id": "...", "type": "...", "message": "...", "read": false, "created_at": "..." }
  ]
}
```

**Authorization**: applicant (own data only)

---

### GET /portal/notifications
**Purpose**: List applicant notifications

**Authorization**: applicant

---

### POST /portal/notifications/{id}/read
**Purpose**: Mark notification as read

**Authorization**: applicant (own)

---

## Course & Department (Reference Data)

### GET /courses
**Purpose**: List active courses

**Outputs**:
- **200**: List of courses with department

**Authorization**: Public (for application form), all authenticated

---

### GET /departments
**Purpose**: List departments

**Authorization**: Public, all authenticated

---

### POST /admin/courses
**Purpose**: Create course

**Authorization**: admin, super_admin

---

### PUT /admin/courses/{id}
**Purpose**: Update course

**Authorization**: admin, super_admin

---

### DELETE /admin/courses/{id}
**Purpose**: Deactivate course (soft-delete via `is_active = false`)

**Authorization**: admin, super_admin

---

### POST /admin/departments
**Purpose**: Create department

**Authorization**: super_admin

---

## Exam Domains (Reference Data)

### GET /exam-domains
**Purpose**: List exam domains

**Outputs**:
- **200**: List of domains with max_items

**Authorization**: grader, counselor, super_admin
