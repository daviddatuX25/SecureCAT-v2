# C1-04: Background P4 — Local Context (ISPSC Tagudin)

**Task ID:** C1-04
**Assigned to:** Jaypee
**Date:** June 8, 2026
**Dependencies:** C1-01 (Background P1)

---

## Background of the study (Local context)

[indent] The local operational context at the Ilocos Sur Polytechnic State College (ISPSC) Tagudin Campus mirrors the challenges common to regional Philippine State Universities and Colleges (SUCs) during peak administrative periods. As a public institution providing free tertiary education under Republic Act No. 10931, the campus receives approximately 300 to 400 college applications per admission cycle, with peak days seeing 30 to 50 applicants arriving at the Registrar Office. The admission pipeline begins with the Registrar's existing registration system for the initial application, but immediately fragments into manual processes: applicant data from the registration system is manually re-encoded into an Excel tracking sheet, and the registration system itself is not connected to the Guidance Office, preventing any collaborative data flow or faster intake processing between the two offices. Supporting documents — Form 138, birth certificates, ID photos, and certificates of good moral character — are stored exclusively in physical folders with no systematic digital backup. The Registrar generates individual admission slips from a Word template, a process requiring two to three minutes per applicant, and physically hands the applicant off to the Guidance Office for examination. No automated status notification exists; applicants must physically return to campus or call by phone to check their progress, a burden amplified for those traveling from remote municipalities in Ilocos Sur and neighboring provinces. Examination scheduling between the Registrar and Guidance Offices relies on verbal communication and text messages with no shared scheduling platform, which risks miscommunication and delays when either office is understaffed or busy. The Guidance Office handles all proctoring internally without delegation to other faculty, tracks attendance on paper sign-in sheets, and scores examinations by manually comparing each answer sheet to a physical answer key — a process that takes two to three days for a batch of 50 applicants and contributes to a one-to-two-week delay between examination and result release. Score results are compiled in an unprotected Excel file with no system-level audit trail, meaning any modification to a recorded score is technically undetectable. Course recommendations are produced by manually cross-referencing exam scores against printed program quota lists provided by the Campus Director. Internet infrastructure at the campus presents access challenges: the campus network is shared across all users, causing speeds to slow during peak usage periods, and occasional service interruptions from the internet service provider occur roughly once or twice per month. The more persistent issue, however, is that many students do not get to access the campus internet at all, limiting their ability to engage with any digital processes and reinforcing dependence on in-person visits for admission-related transactions. The Registrar's tracking spreadsheet is not password-protected, and data privacy practices extend only to a locked filing cabinet and a computer account password, with no formal institutional protocol for compliance with Republic Act No. 10173, the Data Privacy Act of 2012. Physical applicant files are occasionally misplaced, requiring applicants to resubmit documents they may have traveled hours to obtain. These conditions — high applicant volume under free tertiary education, fully manual scoring with no OMR technology, fragmented inter-office coordination, minimal data privacy safeguards, and unreliable internet infrastructure — collectively define the operational environment that SecureCAT is designed to address.

---

## Notes

**Interview-grounded revisions (June 8):**
- Replaced `[N]` applicant volume placeholder with "approximately 300 to 400" per admission cycle, 30 to 50 per peak day
- **CORRECTED:** No OMR overlay templates are in use. Scoring is entirely manual using physical answer key comparison
- Added offline infrastructure context: shared campus internet bandwidth, ISP outages ~1-2x/month, students often unable to access campus internet
- Added data privacy gap: unprotected spreadsheet, no formal RA 10173 protocol
- Added document loss incidents and duplicate entry problems
- Added full workflow description based on process walk-through

**Evidence tags:** `[SIM-REG-01]`, `[SIM-REG-02]`, `[SIM-REG-03]`, `[SIM-REG-04]`, `[SIM-REG-10]`, `[SIM-REG-18]`, `[SIM-REG-19]`, `[SIM-REG-20]`, `[SIM-GUID-01]`, `[SIM-GUID-02]`, `[SIM-GUID-03]`, `[SIM-GUID-05]`, `[SIM-GUID-08]`, `[SIM-GUID-13]`, `[SIM-GUID-20]`

**Citation requirement:** Minimum 5 citations from 2022-2026. This paragraph currently has NO citations — they need to be added. Sources should cover: regional Philippine SUC digitalization challenges, free tertiary education impact on application volumes, data privacy in Philippine educational institutions, offline-capable systems for rural areas, and manual process inefficiencies in academic settings.
