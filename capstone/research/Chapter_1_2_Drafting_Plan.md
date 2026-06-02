# Chapter 1 and Chapter 2 Drafting Plan

> **NOTE (June 2026 Update):** This document was written before Chapter 2 was restructured. Chapter 2 is now **METHODOLOGY** (per the BSIT Capstone Template) — it no longer contains "Review of Related Literature" sections. The narrative structure below for Chapter 1 remains valid. Chapter 2 content follows GUIDE-3; see TEAM_META_GUIDE_Ch1_Ch2.md and your personal DIRECTION.md for current task assignments.

This document defines the drafting sequence, narrative structure, and blending strategy for Chapter 1 and Chapter 2. It is intended to ensure that existing built features and planned research features are presented side by side without creating a structural mismatch.

## Drafting Objective
- Create a manuscript where the existing system and the future research features are presented as one coherent research narrative.
- Preserve the locked capstone title while showing the advanced system scope clearly.
- Support defense readiness by preparing coverage maps that panelists commonly expect in proposal manuscripts.

---

## Chapter 1 Overview Plan

Chapter 1 should establish the problem context, the beneficiaries, the research objectives, the research questions, the scope and delimitations, and the significance of the study.

### Narrative Structure

#### 1. Introduction
- Present the operational context of ISPSC Tagudin admission and testing workflows.
- Explain the practical problems arising from current manual or fragmented systems.
- Include both general admissions and testing operations pain points.

#### 2. Project Context and Beneficiaries
- State the General and Specific Objectives aligned with the system title.
- List primary beneficiaries: Registrar Office, Guidance Office, Proctors, Test Administrators, Applicants.
- Mention secondary beneficiaries: department heads, IT support, future campuses if multi-tenant research feature is included.

#### 3. General and Specific Objectives
- General objective should reflect the title directly.
- Specific objectives should include both implementation and research objectives.
- Use the advanced feature list to enrich Specific Objectives, not to introduce a new title.

#### 4. Research Questions
- Formulate questions covering operational needs, role-based access needs, security needs, and operational resilience needs.

#### 5. Significance of the Study
- Map significance by stakeholder.
- Highlight the DPA and data governance relevance, operational continuity, and institutional reporting value.

#### 6. Scope and Delimitations
- Clearly state what is in scope and what is excluded.
- Include explicit delimitations that allow advanced features to appear as research contributions rather than out-of-scope expansion.

---

## Chapter 2 Overview Plan

Chapter 2 should review related literature and systems relevant to the title, followed by the conceptual and technical frameworks used in the study.

### Narrative Structure

#### 1. Review of Related Literature
- Role-Based Access Control.
- Zero trust and data integrity models for assessments.
- Admission testing and automated scoring approaches.
- Natural language interfaces and applicant-facing AI assistants.
- Offline-capable mobile systems and queueing strategies.
- Philippine Data Privacy Act considerations.

#### 2. Review of Related Systems
- Existing admission systems locally and abroad.
- Electronic assessment platforms.
- OMR grading systems.
- Document generation systems in academic workflows.

#### 3. Conceptual Framework
- Input-process-output or systems view of admission testing.
- Explicitly include existing modules and planned research modules as stages of one process.

#### 4. Technical Framework
- Laravel/Inertia/Svelte stack as the implementation base.
- Security models including HMAC and audit logging.
- Vision and embedding frameworks tied to planned OMR and RAG features.

---

## Blending Strategy: Existing vs Planned Features

The key requirement is to treat built features and planned research features as components of one system story.

### Recommended Approach
1. Group content by title component rather than by “built” vs “planned.”
2. For each component, describe what the system does today, then describe what the research adds, why it is necessary, and how it aligns with the component of the title.

### Example Blend Templates

#### Role-Based
- **Existing:** policies and middleware-driven role restrictions.
- **Planned:** HMAC integrity, audit immutability, zero-trust enforcement for score governance.
- **Blend sentence:** “The role-based design is operationalized through policy-based access control and cryptographic score verification, ensuring that actions are traceable and tamper-resistant.”

#### Admission Testing System
- **Existing:** scheduling, roster, grading, direct assessment, OMR CSV import.
- **Planned:** computer vision OMR ingestion and offline-capable proctor portal.
- **Blend sentence:** “Testing operations are supported by a scheduling and grading core, while research extensions introduce image-based answer ingestion and offline-resilient proctor workflows.”

#### Guidance and Registrar Offices
- **Existing:** consultation summaries, reports, applicant records, result release.
- **Planned:** enhanced AI Companion with external data integration and course recommendations.
- **Blend sentence:** “Office operations are strengthened by optimized reporting and natural-language assistance, reducing manual cognitive load during intake and counseling.”

#### ISPSC Tagudin
- **Existing:** single-campus repository structure.
- **Planned:** tenant isolation architecture.
- **Blend sentence:** “Though deployed for Tagudin, the database architecture follows tenant isolation patterns that align with future campus expansion.”

---

## Drafting Sequence

1. Draft existing-feature平行的section first in each chapter.
2. Insert research enhancement side by side in the same section.
3. Validate that no subsection presents built features and research features as competing scopes.
4. Cross-check wording against `SYSTEM_FEATURES.md` and `ROADMAP.md`.
5. Confirm title component mapping against `pre_proposal_defense.md`.

---

## Suggested Chapter 1 Markers

- Each objective should be traceable to a system module or enhancement.
- Scope statements should include current system modules and planned research modules.

## Suggested Chapter 2 Markers

- Literature review should include at least one source per advanced feature cluster.
- Technical framework should explain how the advanced feature will fit inside the current architecture.

---

## Final Output Targets

| Section | Purpose |
|---|---|
| Chapter 1 objectives and scope | Establish a unified system narrative |
| Chapter 2 literature/framework | Provide justification for both baseline and advanced features |
| Cross-reference matrix | Map title components, existing features, planned features, chapter sections, and panel-expected justifications |

---

## Notes for Future Writers
- Avoid saying “later versions” when describing planned features; present them as part of the same research target system.
- Use present perfect or present tense when describing the implemented system.
- Use future-oriented research wording only during planning contributions and implementation-roadmap discussion.
