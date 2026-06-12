# Feature Verification Protocol
## SecureCAT-v2 Descriptive Research Phase (Step 0 Data Gathering)

> [!IMPORTANT]
> **Purpose:** To formally verify the operational adoption, features used, and role-usage patterns of the foundational digital system (Phase 1) deployed at the ISPSC Tagudin Guidance Office.
>
> This data gathering serves as the empirical baseline for the descriptive phase (Objective 1) of the capstone research and directly resolves timeline and workflow gaps in Chapters 1 and 2.

---

## 1. Objectives

1. **Verify Digital Adoption:** Quantify how much of the admission process was transitioned to the digital system (Phase 1) during the last admission period.
2. **Determine Feature Utilization:** Confirm which features were actively used (e.g., result sheet generation, new applications, direct assessment walk-ins).
3. **Analyze Role Behavior:** Verify how the Guidance Office staff used the Super Admin credentials and identify if additional campus-level roles are required.
4. **Identify Pain Points & Manual Work:** Document what tasks remained manual or required workarounds due to Phase 1 limitations.

---

## 2. Interview Guide (Guidance Office Staff)

### 2.1 General System Usage
- Who in the office used the system? (Single user, multiple staff, OJT students?)
- What was the overall user experience during the last admission period?
- How did the team access the system (local network, specific PC, remote hosting)?

### 2.2 Feature-Specific Utilization

Use the following checklist to evaluate each suggested feature:

| Suggested Feature | Recommended Usage Scenario | Actually Used? (Y/N/Partial) | Details / Feedback |
|-------------------|----------------------------|------------------------------|--------------------|
| **Result Sheet Generation** | Print score reports / result sheets | | |
| **New Applications** | Register new applicants directly into the system | | |
| **Direct Assessment** | Walk-in grading (recording scores without scheduling) | | |
| **Exam Scheduling** | Scheduling applicants for specific exam sessions | | |
| **Proctor Management** | Assigning proctors to rooms/sessions | | |

### 2.3 Role & Account Governance
- You were provided a Super Admin account for exploration. Who used it?
- Did any staff members express a need for distinct individual logins with limited access?
- Did you experience any situation where a staff member made unauthorized edits or viewed sensitive data?
- If a new campus-level administrator role were introduced to manage multiple campuses, would that match your administrative hierarchy?

### 2.4 Unresolved Manual Steps
- What steps in the admission process did you still complete using paper or Excel?
- Did you have to manually copy scores from paper answer sheets to the system?
- How did you handle disputes or corrections on scores?

---

## 3. System Audit Guide (Log & Database Queries)

To back up the interview responses with empirical proof, the developer/researcher will execute the following queries or inspect logs in the Laravel application context:

### 3.1 Verify Recorded Application Volume
Run an Eloquent command via Artisan Tinker to count total applications and compare them with the school's physical log:

```bash
php artisan tinker --execute 'echo "Total Applicants: " . \App\Models\Applicant::count();'
```

### 3.2 Verify Feature Usage Patterns
Check if the scheduling modules or direct assessment modules contain records:

* **Checking session/schedule creation:**
  ```bash
  php artisan tinker --execute 'echo "Scheduled Sessions: " . \App\Models\ExamSession::count();'
  ```

* **Checking walk-in / direct assessments (applicants graded without a session association or session marked as walk-in):**
  ```bash
  php artisan tinker --execute 'echo "Direct/Walk-in Applicants: " . \App\Models\Applicant::whereNull("exam_session_id")->whereNotNull("score")->count();'
  ```

### 3.3 Verify User Account Footprint
Check if multiple user accounts were created, or if all actions were run under the Super Admin:

```bash
php artisan tinker --execute 'echo "Total Registered Users: " . \App\Models\User::count(); \App\Models\User::all()->each(fn($u) => print($u->name . " - " . $u->role . "\n"));'
```

---

## 4. Verification Data Template (For Thesis Appendix)

Once the data gathering is complete, the researchers will populate the following synthesis table to be included in Chapter 4 or as an appendix:

| Workflow Step | Prior Manual Baseline (Pre-Phase 1) | Phase 1 Deployed Baseline | Phase 1 Adoption Proof / Metrics | Remaining Gap (Why Phase 2 is Needed) |
|---|---|---|---|---|
| **Intake / Application** | Paper form submissions, manual encoding | Portal entry / admin registration | [e.g., X applicants encoded] | Lack of offline support, no multi-campus isolation |
| **Exam Scheduling** | Manual room assignments on paper | Digital session builder | [e.g., Y sessions scheduled] | Heavy proctor workload, no automated slot balancing |
| **Scoring / Grading** | Hand-keying scores, manual tallying | Direct entry page / walk-in mode | [e.g., Z scores entered] | Risk of tampering, manual grading of paper OMR sheets |
| **Report Generation** | Excel mail-merge or manual printing | PDF/Word template renderer | [e.g., printed report counts] | No document verification hash (HMAC), templates rigid |
| **System Governance** | None | Share Super Admin account | 1 Shared Account | Lack of role-based segregation (RBAC) and audit trail |

---

## 5. Protocol Execution Checklist

- [ ] Schedule data gathering meeting with Guidance Office head/staff.
- [ ] Conduct face-to-face or virtual interview using the guide in Section 2.
- [ ] Run Artisan Tinker verification queries on the deployed database (Section 3).
- [ ] Export system logs for analysis (if any active logs are maintained).
- [ ] Synthesize findings into the template in Section 4.
- [ ] Submit synthesized findings to the thesis team for incorporation into Chapter 1 and Chapter 2.
