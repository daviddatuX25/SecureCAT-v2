# Task: Revise Scope and Delimitations Section (Chapter 1)

## Context

The current Scope and Delimitations section in the manuscript (`C1-11_David_Scope_Delimitations.md`) and mirrored in `SecureCAT_Ch1_Ch2_Manuscript.md` (lines 108–117) is:

- **Too long** (~1,200 words total: Scope ~697, Limitations ~508)
- **Not serving its purpose** per GUIDE-2 requirements
- **Fatally framed** — uses an explicit "existing modules / planned research modules" binary that invites defense panel interrogation

## Core Problem: The Two-Tier Framing Is a Defense Liability

The current draft explicitly divides the system into:

1. "Existing system modules, developed prior to the capstone research phase and deployed for operational use..."
2. "The planned research modules, which constitute the advanced capstone contributions..."

This framing invites one of the most dangerous panel questions:

> *"So which features are ACTUALLY built? If these modules already existed before your capstone, what exactly is your contribution? Is this a capstone or a documentation project?"*

The moment this binary appears in writing, panelists will trace it. The scope section is **not the place** to disclose development history, deployment timelines, or pre-capstone work. That distinction serves an internal strategic purpose — it should never appear in the manuscript.

---

## Strategic Reframing: The Unified Capstone System Principle

**Foundational stance:** SecureCAT is the system this capstone develops. Period. There is no "pre-capstone baseline" and no "research enhancement layer" in the manuscript's language. The capstone covers all of it.

This is not dishonesty — it is appropriate academic framing. A capstone project formally documents, validates, architects, and deploys a system. The scope section describes **what the system encompasses**, not when individual parts were first prototyped.

### Language Rules

**NEVER use:**
- "existing features" / "existing modules" / "existing system"
- "planned features" / "planned research modules"
- "baseline system" / "research enhancements"
- "pre-capstone modules" / "capstone contributions"
- "developed prior to the capstone research phase"
- "deployed for operational use" (as a descriptor for pre-capstone modules)
- Any language that temporally separates system components

**ALWAYS use:**
- "The system encompasses..." / "SecureCAT integrates..." / "The study covers..."
- "The system operationalizes..."
- "Capabilities developed within this study include..."
- Feature descriptions stated in the **present tense of what the system does**, not when it was built
- The same grammatical weight for all features — no tier distinction

### Structural Rule

Do NOT organize the scope paragraph into two blocks (existing → planned). Instead, organize by **function cluster** or **user role cluster**, weaving all capabilities together into a single coherent description. The reader should not be able to identify which features came "first."

---

## Reframing the "Prior Deployment" Mention

The current draft explicitly says the foundational platform was "deployed at the Guidance Office for operational use" and that "the capstone research formally documents, validates, and architecturally advances this foundation."

**This sentence is the most dangerous sentence in the draft.** It tells panelists: some parts of this system are not capstone work.

**Revised treatment:** The manuscript may acknowledge that the researchers conducted prior institutional consultation and observed the processes at ISPSC Tagudin — this is research context, not a disclaimer about deployment. The operational context reference belongs in the Background of the Study (already written), not in the Scope section. The Scope section describes the system as it will be evaluated — all of it.

---

## GUIDE-2 Compliance Checklist (Section 4)

### Scope Paragraph — Target: ~350–400 words, 1 paragraph

Must include — woven into prose, NOT listed, NO tier distinctions:

- [ ] **Locale:** ISPSC Tagudin Campus, Tagudin, Ilocos Sur, Philippines
- [ ] **User roles (6):** Applicants/Examinees, Proctors/Test Administrators, Guidance Counselors, Registrar Staff, Registrar Administrators, Super Administrators — described by what they do, not just named
- [ ] **RBAC flexibility note:** multi-role assignment, configurable role definitions, super admin as highest privilege
- [ ] **System capabilities as a unified list** (no tier labels):
  - Web-based application intake with form validation and time-limited activation
  - Real-time applicant status lifecycle tracking
  - Admission slip and result document generation (PDF and DOCX)
  - Examination session scheduling and roster management
  - Proctor assignment and digital attendance tracking
  - Aptitude area and score management with strictly confidential conversion table enforcement
  - CSV score import
  - Consultation summary recording by guidance counselors
  - Bulk and single result release
  - Staff and applicant notification systems
  - Audit log viewing and export
  - Computer-vision-based Optical Mark Recognition (OMR) for automated answer sheet scoring
  - Cryptographic score integrity via HMAC-SHA256 signature locks
  - Offline-resilient Progressive Web Application (PWA) with IndexedDB caching for proctor operations
  - AI Companion powered by Retrieval-Augmented Generation (RAG) using local vector embeddings for pre- and post-examination applicant guidance
  - AI-assisted scheduling system with human-in-the-loop approval for registrar administrators
  - ML-assisted course triage module executing live K-Means clustering (K=4, adopted and adapted from Yukee et al., 2025; Ballesteros et al., 2025) to classify aptitude-based and socio-academic profiles at consultation time
  - Role-based database architecture with strict access boundaries
- [ ] **Principal variables:** applicant profiles, exam configurations, OMR scan data, role credentials, QR scans, natural language queries
- [ ] **Timeframe:** Capstone 1 (May 2026) → Capstone 2 (projected August 2026) — cover development through evaluation
- [ ] **Justification for boundaries:** Why Tagudin only, why admission testing only, why these roles, why these variables

### Limitations Paragraph — Target: ~300–350 words, 1 paragraph

Must include — woven into prose, NO reference to "existing" vs "planned":

- [ ] What the system does NOT do: LMS, post-admission enrollment, tuition/payment, academic records management after admission, advising beyond AI companion course recommendation
- [ ] **OMR hardware dependency:** requires camera or scanner; QR codes as primary identity linkage; handwritten name recognition excluded as primary method
- [ ] **PWA network dependency:** initial internet connection required for service worker registration; background sync on restore
- [ ] **Single-site constraint:** ISPSC Tagudin Campus only; multi-campus requires separate coordination and data isolation
- [ ] **Manual processes outside scope:** test content/question design, scoring methodology determination, admission policy decisions (thresholds, criteria) — set by administration, not automated
- [ ] **Conversion table confidentiality delimitation:** strictly enforced, never exposed to applicants, unauthorized staff, API responses, or exports
- [ ] **RA 10173 data privacy:** named explicitly; RBAC and audit logging as the compliance mechanism; NPC formal audit is out of scope

---

## Style Rules (GUIDE-2 Compliance)

- Paragraph form ONLY — no bullets, no numbered lists, no bold text in body
- First-line indent: 5 spaces in markdown source (converts to 0.312 in docx indent)
- Academic prose: parentheses only for citations, abbreviation first use, statistics, legal references
- One idea per sentence; vary sentence length; avoid noun clusters
- Active voice where clarity improves; passive voice acceptable for definitions
- No "etc." — use "including," "such as," or "among others"
- No em-dashes in running prose — rewrite to avoid

---

## Source Documents to Reference

| Document | Purpose |
|----------|---------|
| `capstone/SecureCAT_Ch1_Ch2_Manuscript.md` | Current manuscript (lines 108–117 = current scope section) |
| `capstone/drafts/C1-11_David_Scope_Delimitations.md` | Working draft — **mine for capabilities, discard the two-tier structure** |
| `capstone/research/Existing_and_Planned_Features.md` | Feature inventory — **mine for capabilities, DO NOT copy framing** |
| `capstone/guides/GUIDE-2-CHAPTER1-CONTENT.md` | Content requirements and style rules |
| `capstone/strategy/pre_proposal_defense.md` | Internal strategy (Trojan Horse) — reference for rationale, NEVER name in manuscript |
| `capstone/STRATEGY_NOTES.md` | AIDLC model, SUS+TLX, descriptive developmental design |

---

## Deliverable

Revised `C1-11_David_Scope_Delimitations.md` containing:

1. **Scope paragraph** (~350–400 words) — unified framing, all capabilities presented as one coherent system with no tier distinction
2. **Limitations paragraph** (~300–350 words) — complete GUIDE-2 coverage with no reference to pre-capstone vs. capstone modules
3. **Internal notes section** — documents framing decisions for team reference (what was changed and why)

After draft is approved:
- Sync the revised paragraphs into `SecureCAT_Ch1_Ch2_Manuscript.md` (lines 108–117)

---

## Constraints

- Do NOT start writing until explicitly directed
- This task definition is for review and alignment first
- Final output must pass GUIDE-2 self-check: parenthesis count, sentence variety, noun cluster check, no tier-language scan
- Final output must pass the defense simulation test: *"Which of these features are actually built?"* — the answer implied by the text should be: **all of them, as part of this capstone**

---

## Related Tasks (After Completion)

- Sync revised section into `SecureCAT_Ch1_Ch2_Manuscript.md`
- Verify alignment with Objectives (Section 3) — especially Objective 2 which already uses unified language
- Cross-check with Chapter 2 Methodology references (no conflicting tier language)
- Verify Background of the Study does not accidentally create the same tier framing when it references the prior operational context