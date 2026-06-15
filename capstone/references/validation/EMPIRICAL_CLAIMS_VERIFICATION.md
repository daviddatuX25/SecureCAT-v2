# Empirical Claims Verification Tracker

**Status:** DRAFT — For field validation at ISPSC Tagudin Campus
**Last Updated:** June 14, 2026
**Purpose:** Track all non-referenced factual claims in the manuscript that require verification through interviews, observations, or document review during the June 8 field visit and follow-up.

---

## Verification Status Legend
- ⏳ **Pending** — Not yet verified
- 🔄 **In Progress** — Being verified (interview scheduled/in progress)
- ✅ **Verified** — Confirmed by source
- ⚠️ **Needs Adjustment** — Partially true or needs correction
- ❌ **Invalid** — Not supported by evidence

---

## Section 1: Background of the Study (Chapter 1)

| # | Claim | Manuscript Location | Source to Verify | Status | Evidence/Notes |
|---|-------|---------------------|-------------------|--------|----------------|
| 1.1 | Freshman cohorts of 700 to 1,000 applicants per academic year | Background, para 3 | Registrar enrollment records / CHED reports | ✅ Verified | |
| 1.2 | Applicant intake at Registrar Office conducted exclusively through paper forms | Background, para 3 | Registrar staff interview / direct observation | ✅ Verified | |
| 1.3 | Physical documents filed in locked cabinets without digital backups | Background, para 3 | Registrar staff interview / direct observation | ✅ Verified | |
| 1.4 | Paper-based test routing (no digital tracking) | Background, para 4 (direct observations) | Guidance/Registrar interview / process walkthrough | ✅ Verified | |
| 1.5 | Absent audit trails for score modifications and examinee records | Background, para 4 (direct observations) | Registrar staff interview / system check | ✅ Verified | |
| 1.6 | Scoring errors from manual stencil checks | Background, para 4 (direct observations) | Guidance counselor interview / error logs if any | ⚠️ Needs Adjustment | |
| 1.7 | Fragmented inter-office coordination during peak periods | Background, para 4 (direct observations) | Both offices interview / coordination artifacts | ✅ Verified | |

---

## Section 2: Importance of the Study (Chapter 1)

| # | Claim | Manuscript Location | Source to Verify | Status | Evidence/Notes |
|---|-------|---------------------|-------------------|--------|----------------|
| 2.1 | Applicants from remote municipalities travel >2 hours each way | Importance, "The Community" | Applicant survey / guidance records / LGU data | ⚠️ Needs Adjustment (travel time depends from nearby municiaplities from tagudin or to ... ) | |
| 2.2 | Applicants make multiple visits (submit reqs, check schedules, claim results) | Importance, "The Community" | Applicant flow analysis / staff interview | ⚠️ Needs Adjustment | |
| 2.3 | Two-to-three-day stencil-method grading process | Importance, "The Guidance Office" | Guidance counselor interview / direct timing | ⚠️ Needs Adjustment | |
| 2.4 | Manual Microsoft Word encoding and paper-based filing at Registrar | Importance, "The Registrar Office" | Registrar staff interview / direct observation | ✅ Verified | |
| 2.5 | Verbal and text-based coordination between offices (no formal handoff) | Importance, "The Registrar Office" | Both offices interview / communication artifacts | ✅ Verified | |
| 2.6 | Cognitive burden of manual cross-referencing for counselors | Importance, "The Guidance Office" | Guidance counselor interview / task walkthrough | ⚠️ Needs Adjustment | |

---

## Section 3: Population and Locale (Chapter 2)

| # | Claim | Manuscript Location | Source to Verify | Status | Evidence/Notes |
|---|-------|---------------------|-------------------|--------|----------------|
| 3.1 | 500 to over 1,000 freshman applicants per academic year | Pop & Locale, para 1 | Registrar enrollment records / CHED reports | ⏳ Pending | (Duplicate of 1.1 — consider merging) |
| 3.2 | Shared campus bandwidth (not dedicated) | Pop & Locale, para 1 | ICT Office / network admin interview | ✅ Verified | |
| 3.3 | Internet "sometimes slow but not frequently down" | Pop & Locale, para 1 | ICT Office / staff experience / speed tests | ✅ Verified | |
| 3.4 | Wi-Fi primarily reserved for campus staff/faculty, not students | Pop & Locale, para 1 | ICT policy document / network admin interview | ✅ Verified | |
| 3.5 | Guidance Office and Registrar Office co-manage admission testing | Pop & Locale, para 1 | Org chart / both offices interview | ✅ Verified | |
| 3.6 | Six user roles: applicants, proctors, guidance counselors, registrar staff, registrar admins, super admins | Scope & Limitation | System design / office role confirmation | ⚠️ Needs Adjustment | (Now 7 roles with Program Head added) |
| 3.7 | OMR requires camera/scanner with clear answer sheet images + pre-printed QR codes | Scope & Limitation | Guidance Office equipment check / test scan | ⚠️ Needs Adjustment | |

---

## Section 4: Data Analysis Themes (Chapter 2)

| # | Theme to Verify | Manuscript Location | Source to Verify | Status | Evidence/Notes |
|---|-----------------|---------------------|-------------------|--------|----------------|
| 4.1 | Process bottlenecks in admission pipeline | Data Analysis, para 1 (thematic analysis) | Staff interview / process mapping | ✅ Verified | |
| 4.2 | Inter-office coordination failures | Data Analysis, para 1 | Both offices interview / communication logs | ✅ Verified | |
| 4.3 | Data integrity vulnerabilities (score tampering risk) | Data Analysis, para 1 | Registrar interview / audit trail review | ✅ Verified | |
| 4.4 | Infrastructure constraints (bandwidth, hardware, Wi-Fi) | Data Analysis, para 1 | ICT Office / direct observation | ✅ Verified | |

---

## Section 5: Operational Workflow Details (for Chapter 3 / Design Validation)

| # | Claim | Source to Verify | Status | Evidence/Notes |
|---|-------|-------------------|--------|----------------|
| 5.1 | Current exam scheduling process (manual, no centralized calendar) | Registrar + Guidance interview + artifact review | ✅ Verified | |
| 5.2 | Score recording: manual entry from stencil to Excel/records | Guidance counselor walkthrough | ✅ Verified | |
| 5.3 | Consultation process: how counselors guide applicants currently | Guidance counselor walkthrough | ⚠️ Needs Adjustment | |
| 5.4 | Proctor assignment and roster management process | Registrar interview | ⚠️ Needs Adjustment | |
| 5.5 | Result release: how applicants currently learn outcomes | Registrar + Guidance interview / applicant feedback | ⏳ Pending | |
| 5.6 | Notification/communication channels used (FB, text, verbal) | Both offices interview / message logs | ⏳ Pending | |

---

## Verification Interview Protocol

### Primary Sources
1. **Registrar Office Staff** — Intake, scheduling, records, result release
2. **Guidance Counselors** — Scoring, consultation, applicant guidance
3. **ICT/Network Admin** — Bandwidth, Wi-Fi policies, infrastructure
4. **Program Heads** — Course quota/slot allocation, program capacity limits, applicant profiling alignment
5. **Registrar Administrator** — Policy, approval gates, coordination

### Artifacts to Collect
- [ ] Enrollment statistics (AY 2024-2025, 2025-2026)
- [ ] Current admission forms (paper)
- [ ] Stencil answer sheets / scoring keys
- [ ] Staff duty schedules / org charts
- [ ] ICT network topology / bandwidth reports
- [ ] Communication logs between offices
- [ ] Error incident reports (if any)
- [ ] Course quota tables / Program Head slot allocations
- [ ] Applicant residency/distance data (for travel time validation)

---

## Field Visit Log (June 8, 2026)

| Time | Activity | Participants | Claims Addressed |
|------|----------|--------------|------------------|
| 10:50–11:10 | Campus Director Interview | David (lead), Christine (audio), Jaypee (map) | 1.1, 3.1, 3.5 |
| TBD | Registrar Office Walkthrough | Christine + Jaypee | 1.2, 1.3, 1.4, 1.5, 2.4, 2.5, 5.1, 5.2, 5.4, 5.5 |
| TBD | Guidance Office Walkthrough | David + Jaypee | 1.6, 2.3, 2.6, 5.2, 5.3, 5.6 |
| TBD | ICT Office Visit | David | 3.2, 3.3, 3.4, 4.4 |
| TBD | Program Heads Interview | David + Christine | 3.6 (quota), course capacity limits, triage alignment |

---

## Next Steps

1. **Complete field interviews** (June 8)
2. **Update status column** for each claim based on evidence
3. **Adjust manuscript claims** where evidence contradicts or refines
4. **Add citations** to manuscript for verified claims (e.g., "per Registrar Office records, AY 2025-2026")
5. **Archive raw interview notes** in `capstone/references/integration/`
6. **Generate verification report** for defense appendix
