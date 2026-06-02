# Member Task Direction — Christine
## Team Member — OMR/CV Literature & Significance

> **Role:** Automated scoring and OMR/CV literature review, significance of the study
> **Total Claimed Tasks:** 2 tasks
> **Estimated Effort:** 6-9 hours
> **Focus:** Technical literature on automated scoring and computer vision OMR, system-specific beneficiary significance

---

## Your Tasks at a Glance

### Chapter 1
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| C1-12 | Significance of the Study | 2-3h | Jun 5 | None |

### Chapter 2
| Task ID | Task | Hours | Due | Dependencies |
|---------|------|-------|-----|--------------|
| C2-02 | Lit Review — Automated Scoring & OMR Technologies | 4-6h | Jun 5 | None |

---

## Formatting Rules (Apply to ALL Your Deliverables)

These rules come from `GUIDE-1-FORMATTING.md` and are non-negotiable. Every file you produce must comply:

- Left margin: 1.5 inches. Right margin: 1.0 inch. Top/Bottom: 1.0 inch.
- Font: Times New Roman, 12pt. Line spacing: Double throughout.
- Paragraph indent: Exactly 5 spaces at the start of each paragraph.
- Alignment: Justified for all body text.
- No bullet points anywhere in any deliverable. No bold body text. Bold is reserved for structural labels (headings, table/figure captions) only.
- No extra line spacing between paragraphs. Set "space before/after paragraph" to 0pt; rely only on double line spacing.
- All cited literature must be published 2022-2026. No exceptions without justification.

---

## Detailed Task Directions

### Week 1 (June 1-5): OMR Literature & Significance

#### C2-02: Lit Review — Automated Scoring and OMR Technologies (June 5)
**Priority:** HIGH — OMR and computer vision literature foundation

**What to Do:**

1. **Research Automated Scoring and OMR Technologies** (90 min):
   - Search Google Scholar for:
     - "automated test scoring optical mark recognition"
     - "computer vision answer sheet processing"
     - "OMR bubble detection accuracy"
     - "automated grading vs manual scoring accuracy comparison"
     - "image-based OMR answer sheet recognition"
     - "deep learning multiple choice scoring"
   - Filter: 2022-2026 only, peer-reviewed.
   - Target: 5-7 sources covering:
     - Automated test scoring methods and algorithms
     - Optical mark recognition (OMR) technologies and their evolution
     - Computer vision-based answer sheet processing (image capture, bubble detection, answer extraction)
     - Accuracy comparisons between manual and automated scoring
     - Integration of OMR/CV scoring into educational platforms
     - Error rates, throughput, and reliability metrics for automated scoring

2. **Extract Key Findings** (60 min):
   - For each source, extract:
     - The scoring method or technology described (OMR hardware, software-based OMR, computer vision, deep learning)
     - Accuracy metrics (e.g., percentage agreement with manual scoring, error rates)
     - Processing speed and throughput compared to manual methods
     - Technical requirements and infrastructure needs
     - Limitations and challenges reported
   - Focus on findings that are relevant to SecureCAT's planned computer vision OMR ingestion feature.

3. **Write Literature Review** (90-120 min):
   - Exactly 1-2 paragraphs in thematic synthesis (not author-by-author).
   - Structure (if 2 paragraphs):
     - Paragraph 1: Automated scoring methods and OMR technologies — cover the evolution from hardware-based OMR to software-based and computer vision approaches. Synthesize findings on how these technologies detect marked responses, process answer sheets, and integrate with educational platforms. Compare accuracy and throughput across different methods.
     - Paragraph 2: Accuracy comparisons between manual and automated scoring — synthesize studies that compare error rates, processing speed, and reliability between human graders and automated systems. Address how automated scoring reduces human error, accelerates result turnaround, and enables real-time score ingestion into digital platforms. Connect to SecureCAT's planned feature of computer vision-based OMR answer sheet ingestion with direct database import.
   - Minimum 5 in-text citations (Author, Year), all 2022-2026.
   - Thematic synthesis pattern: "While traditional OMR systems require specialized hardware for mark detection (Author A, 2023), recent computer vision approaches using image processing have demonstrated comparable accuracy using standard cameras (Author B, 2024), enabling integration into web-based platforms without dedicated scanning equipment (Author C, 2025)."
   - Do NOT use author-by-author listing. Synthesize by theme.

4. **Compile Draft References** (30 min):
   - Create APA 7 draft references for all 5-7 sources.
   - Format: Author, A. A. (Year). Title. *Source*, vol(issue), pages. DOI/URL
   - Save for Jaypee's CC-01 (References Compilation).

**Reference:** `GUIDE-3-CHAPTER2-CONTENT.md` (literature review pattern); `SYSTEM_FEATURES.md` Section 2 (Computer Vision Ingestion for OMR); `Existing_and_Planned_Features.md` (Planned Feature 2)

**Deliverable:** `C2-02_Christine_OMR_CV_Scoring_Review.md` with 1-2 paragraphs + draft APA references

---

#### C1-12: Significance of the Study (June 5)
**Priority:** HIGH — Beneficiary impact description

**What to Do:**

1. **Identify Beneficiary Groups** (30 min):
   - The beneficiary groups are system-specific and directly tied to SecureCAT's operational context. These are the ONLY groups to include:
     - Registrar Office staff
     - Guidance Office counselors
     - Proctors and Test Administrators
     - Applicants and Examinees
     - ISPSC Administration
     - Future Researchers
   - Do NOT include generic groups such as "The Community," "The College/Department," "The Students," or "The Researchers."
   - Do NOT include a generic "new knowledge" paragraph — weave contributions into each group's paragraph instead.

2. **Write One Paragraph Per Beneficiary Group** (90-120 min):
   - Exactly 6 paragraphs, one per beneficiary group listed above.
   - Each paragraph must be in paragraph form only — no bullets, no numbered lists, no bold body text.
   - Each paragraph structure:
     - First sentence: Name the beneficiary group specifically.
     - Middle sentences: Explain specific benefits they receive from SecureCAT, grounded in system features.
     - Last sentence: Connect to broader impact or institutional value.
   - Key themes to weave across paragraphs:
     - DPA compliance — how SecureCAT's role-based access, audit logging, and data handling support RA 10173 requirements.
     - Operational continuity — how the system ensures admission workflows continue functioning even under infrastructure constraints (offline PWA, cached data).
     - Institutional reporting value — how automated scoring, audit trails, and real-time dashboards improve reporting and decision-making.

   - Content guidance per group:

     **Registrar Office staff.** SecureCAT provides a centralized digital application pipeline replacing manual paper-based review and approval workflows. Staff benefit from automated application processing, bulk import capabilities, real-time status tracking, and room and course management tools. The system's audit logging ensures accountability and compliance with RA 10173, while role-based access prevents unauthorized data access. This reduces manual data entry errors, accelerates processing during peak admission periods, and provides a verifiable record trail for institutional reporting.

     **Guidance Office counselors.** The system streamlines test administration through session roster management, proctor assignment, and digital attendance tracking. Counselors benefit from automated scoring via OMR CSV import (and planned computer vision ingestion), consultation summary documentation, and aptitude area management. The enhanced AI Companion (planned) will provide applicants with course recommendations and admission guidance via natural language, reducing repetitive applicant inquiries and allowing counselors to focus on student guidance rather than administrative overhead.

     **Proctors and Test Administrators.** SecureCAT equips proctors with real-time session management tools, digital attendance confirmation, and QR-based applicant verification. The planned offline-resilient PWA allows proctors to continue scanning applicant QR codes at exam room doors even when campus WiFi is unreliable, with data cached locally and synchronized automatically upon reconnection. Computer vision-based OMR answer sheet processing (planned) will enable instant automated scoring, eliminating manual grading and reducing turnaround time for result release.

     **Applicants and Examinees.** Applicants benefit from a transparent, real-time status tracker showing their progression from application submission through exam scheduling, attendance, score processing, and result release. The system provides admission slip generation with printable PDF rendering, reducing the need for physical office visits. Token-based account activation ensures secure access to personal records, while the AI companion chatbot provides instant guidance on application status and requirements. Faster score processing and automated result generation mean applicants receive outcomes more quickly and reliably.

     **ISPSC Administration.** The administration gains institutional-level visibility into admission operations through automated reporting, audit logs, and real-time dashboards. The system's role-based architecture ensures data governance aligned with RA 10173, while cryptographic score integrity (planned HMAC signatures) provides tamper-evident records for institutional accountability. Multi-tenant database isolation (planned) prepares the institution for future campus expansion without compromising data privacy. These capabilities strengthen the institution's capacity for evidence-based decision-making, regulatory compliance, and operational reporting.

     **Future Researchers.** SecureCAT serves as a reference implementation for role-based admission testing systems in Philippine state universities. The system's architecture — including RBAC with zero-trust data governance, computer vision OMR processing, offline-resilient PWA proctoring, applicant-facing AI Companion with RAG, and multi-tenant database isolation — provides a comprehensive baseline for future studies in educational technology, automated assessment, and institutional digital transformation. Future researchers can build upon the design patterns, security models, and architectural decisions documented in this study.

3. **Review for Compliance** (15-30 min):
   - Verify all 6 paragraphs are in paragraph form only — no bullets, no bold body text.
   - Verify each paragraph names a specific, direct beneficiary group.
   - Verify DPA compliance, operational continuity, and institutional reporting value are addressed across the paragraphs.
   - Remove any vague or indirect beneficiaries.

**Reference:** `GUIDE-2-CHAPTER1-CONTENT.md` Section 5; `SYSTEM_FEATURES.md`; `Existing_and_Planned_Features.md`

**Deliverable:** `C1-12_Christine_Significance.md` with 6 paragraphs (one per beneficiary group, paragraph form only)

---

## Your Week-by-Week Schedule

### Week 1 (June 1-5): Literature & Significance
- [ ] June 1-3: C2-02 (Automated Scoring and OMR Technologies Review) — research and draft
- [ ] June 4-5: C1-12 (Significance) — draft 6 beneficiary paragraphs in paragraph form

---

## Communication Responsibilities

1. **Submit Draft References Early:**
   - Your draft references for C2-02 should be ready by June 5.
   - Send these to Jaypee for CC-01 (References Compilation).

2. **Provide ISPSC Context:**
   - Jaypee will need your first-hand knowledge of ISPSC Tagudin for C1-04 (Local Context).
   - Share what you know about Guidance Office processes, Registrar workflows, infrastructure constraints (WiFi, computer labs), and peak admission period challenges.

3. **Ask for Clarifications:**
   - If you are unsure about APA formatting, ask David or consult `GUIDE-1-FORMATTING.md`.
   - If you're stuck finding OMR/CV sources, broaden your search terms (e.g., "automated grading," "bubble sheet recognition," "answer sheet image processing").

4. **Daily Progress Updates:**
   - Post brief updates in the group Discord.
   - Flag blockers immediately (e.g., cannot find sources, unclear instructions).

---

## Your Strengths (Lean Into These)

Based on your self-assessment:

- ISPSC familiarity: You are enrolled at ISPSC Tagudin — use this insider knowledge to help Jaypee with C1-04 context.
- Technical literature focus: You can understand and synthesize automated scoring and computer vision research.
- Beneficiary impact: You understand how SecureCAT helps Guidance, Registrar, and applicants.
- Research skills: You can use Google Scholar and evaluate sources.

Focus on what you know: automated scoring literature, benefits to specific stakeholders.

---

## What to Avoid (Tasks Assigned to Others)

- Background global and national context paragraphs — David's tasks now.
- Local context writing — Jaypee's task (with your guidance).
- IPO diagram, objectives, scope — David's tasks.
- Formatting QA, citation cross-check, references compilation — Jaypee's tasks.

Your focus: Automated scoring and OMR/CV literature review, significance of the study.

---

## Quick Reference: Your Guides

- Formatting: `guides/GUIDE-1-FORMATTING.md` (especially APA 7 references, margins, paragraph rules)
- Chapter 1 Content: `guides/GUIDE-2-CHAPTER1-CONTENT.md` (Significance)
- Chapter 2 Content: `guides/GUIDE-3-CHAPTER2-CONTENT.md` (literature review patterns)
- System Features: `SYSTEM_FEATURES.md` (to understand SecureCAT's impact on ISPSC)
- Existing vs Planned: `drafts/Existing_and_Planned_Features.md` (baseline and research features)
- Task Distribution: `TASK_DISTRIBUTION_PLAN.md`

---

## Success Criteria for You

- C2-02 (OMR/CV Scoring Review) completed by June 5 — 1-2 paragraphs on automated scoring methods, OMR, computer vision answer sheet processing, and manual vs. automated accuracy comparisons, 5+ APA citations.
- C1-12 (Significance) completed by June 5 — 6 paragraphs in paragraph form only, system-specific beneficiary groups (Registrar Office staff, Guidance Office counselors, Proctors/Test Administrators, Applicants/Examinees, ISPSC Administration, Future Researchers), highlights DPA compliance, operational continuity, institutional reporting value.
- Draft references submitted to Jaypee by June 5 for CC-01.
- No bullet points, no bold body text, paragraph indent 5 spaces in any deliverable.

---

You are the OMR/CV literature researcher and significance specialist. Your ability to synthesize technical literature on automated scoring will establish the foundation for SecureCAT's computer vision OMR feature, and your clear beneficiary-focused significance section will demonstrate exactly who benefits and how. Keep all beneficiary descriptions specific, system-specific, and directly tied to SecureCAT's features.
