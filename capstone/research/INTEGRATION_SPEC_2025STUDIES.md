# Integration Specification: ISPSC 2025 Studies → SecureCAT Triage Module

**Document type:** Integration specification + manuscript edit plan
**Date:** 2026-06-09
**Status:** Draft — pending library visit for data gaps
**Authors:** David (synthesis), informed by Study 1 (Yukee et al.) and Study 2 (Ballesteros et al.) extractions

---

## Part A. ML-Assisted Course Triage and Recommender Module Specification

### A.1 Design Boundary

The triage module implements **static rule matching**, not live K-Means. Both Yukee et al. (2025) and Ballesteros et al. (2025) validated K=4 clusters using K-Means on ISPSC student data. SecureCAT does not re-run K-Means at classification time. Instead, it encodes their validated cluster centroids and classification thresholds as a static decision surface. The module classifies each applicant into a pre-existing profile by rule-matching their intake data against these published parameters.

This boundary is deliberate: live clustering requires retraining, validation, and computational overhead that is inappropriate for an admission workflow where each applicant is classified once and where reproducibility matters. The static approach guarantees deterministic output — the same applicant profile always produces the same triage suggestion, which counselors can verify and override.

### A.2 Input Ingestion — What SecureCAT Already Captures

SecureCAT's application intake form already collects every data dimension used by both studies. The following table maps study clustering features to SecureCAT data fields:

| Study Feature | SecureCAT Field | Source Module | Transformation |
|---|---|---|---|
| **ICAT percentile scores** (Study 1 — Yukee) | Aptitude area scores (Verbal, Math, Abstract Reasoning, etc.) | OMR score import / Direct assessment | Percentile normalization against test cohort |
| **Family Income** (Study 2 — Ballesteros) | Applicant family income | Application intake form | Categorize into PSA-aligned brackets (Poor / Low / Lower Middle / Middle / Rich) using Ballesteros thresholds |
| **Grade 12 GWA** (Study 2 — Ballesteros) | Applicant GWA | Application intake form or transcript upload | Categorize as "With Honors" (≥90, INFERRED) or "Average" |
| **Municipality** (Study 2 — Ballesteros) | Applicant address / municipality | Application intake form | Map to Area Type (Upland / Lowland) via Ballesteros municipality lookup |
| **SHS Type** (Study 2 — Ballesteros) | Senior high school type (Public / Private) | Application intake form | Direct — no transformation |
| **SHS Origin** (Study 2 — Ballesteros) | SHS track / strand | Application intake form | Direct — no transformation |
| **Sex** (Study 2 — Ballesteros) | Applicant sex | Application intake form | Direct — no transformation |

**Key point:** Study 1 (Yukee) uses ICAT aptitude scores as its primary feature space. Study 2 (Ballesteros) uses socio-economic and academic indicators (Income + GWA). The triage module can apply **both** classification surfaces to each applicant, producing a dual-profile: an aptitude cluster from Study 1's parameters and a socio-academic cluster from Study 2's parameters. This dual classification enriches the counselor's decision-support view.

### A.3 Triage Engine Logic — Static Rule-Matching Matrix

#### A.3.1 Aptitude Classification (from Yukee et al. centroids)

After score import, the module classifies the applicant's aptitude profile:

| Rule | Condition | Cluster Assignment |
|---|---|---|
| Language-Dominant | Verbal/Language percentile is the highest among all aptitude components | Cluster 1 (Yukee) |
| Math-Dominant | Mathematical/Quantitative percentile is the highest | Cluster 2 (Yukee) |
| Spatial/Visuospatial | Abstract/Spatial reasoning percentile is the highest | Cluster 3 (Yukee) |
| Mixed/Gaps | No single component dominates; either balanced or notable gaps between components | Cluster 4 (Yukee) |

**NOTE:** The exact thresholds (whether "highest" means strictly highest, or above a minimum percentile) are EXTRACTED from the study description but may need refinement from the full manuscript. Currently INFERRED that classification uses relative ranking of component scores. If Yukee et al. used absolute cutoffs (e.g., "above 75th percentile"), the rule matrix must be updated post-library-visit.

#### A.3.2 Socio-Academic Classification (from Ballesteros et al. centroids)

After intake data is captured, the module applies auto-classification:

**Step 1 — Classify raw inputs:**
- Income → Poor / Low / Lower Middle / Middle / Rich (thresholds NEEDS LIBRARY EXTRACTION)
- GWA → With Honors / Average (cutoff INFERRED at 90+)
- Municipality → Upland / Lowland (lookup table NEEDS LIBRARY EXTRACTION)

**Step 2 — Match to nearest validated cluster:**

| Cluster | Academic Standing | Income Level | Area Type | Known Size | Suggested Intervention |
|---|---|---|---|---|---|
| B-Cluster 0 | Average | Poor | Mostly Lowland | 29 | Financial aid priority; foundational academic support |
| B-Cluster 1 | With Honors | Poor | Mostly Lowland | 54 | Scholarship referral; honors program recommendation |
| B-Cluster 2 | Average | Lower Middle | Mostly Lowland | NEEDS LIBRARY EXTRACTION | Standard academic support; career exploration |
| B-Cluster 3 | Average | Low-income | Mostly Lowland | 20 | Moderate financial aid consideration; targeted advising |

**Step 3 — Produce dual profile:**
- Output: `{ aptitude_cluster: "Math-Dominant", socio_academic_cluster: "B-Cluster 1", suggested_programs: [...], support_flags: [...] }`

### A.4 Quota Alert Mechanism

The triage module interfaces with SecureCAT's existing room/course management and AI-assisted scheduling to provide quota-aware suggestions:

1. **Program quota lookup:** When the triage module produces a course recommendation (e.g., "BSIT" for a Math-Dominant + With Honors applicant), it queries the current seat availability for that program from the scheduling engine.
2. **Quota status flags:**
   - **Green** — program has >20% seats remaining → recommend confidently
   - **Yellow** — program has 5–20% seats remaining → recommend with note ("limited slots")
   - **Red** — program has <5% seats remaining → flag as full, suggest alternatives from the applicant's cluster profile
3. **Counselor view:** The quota status is displayed alongside the triage recommendation. The counselor makes the final call — the system does not auto-reject or auto-assign based on quotas.
4. **Registrar oversight:** Registrar Admin can set per-program seat caps in the existing course management module. The triage engine reads these caps in real time.

### A.5 Counselor Console — Decision-Support UI Specification

The guidance counselor's consultation summary interface is extended with a triage panel:

**Layout (within existing Guidance Portal):**

```
┌─────────────────────────────────────────────────────────────────┐
│ Applicant: [Name] │ Status: [Current] │ Session: [Date/Time]   │
├─────────────────────┬───────────────────────────────────────────┤
│ TRIAGE PROFILE      │ CONSULTATION NOTES (existing)             │
│                     │                                           │
│ Aptitude Cluster:   │ [Free-text area for counselor notes —    │
│  ■ Math-Dominant    │  existing functionality, unchanged]       │
│                     │                                           │
│ Socio-Academic:     │                                           │
│  ■ B-Cluster 1      │                                           │
│  (Honors / Poor)    │                                           │
│                     │                                           │
│ Support Flags:      │ COURSE RECOMMENDATION                     │
│  ⚠ Scholarship      │                                           │
│  ⚠ Financial Aid    │ ■ BSIT (Math-Dominant)  — 🟢 42/60       │
│                     │ ■ BSCE (Spatial)        — 🟡 55/60       │
│ [Override ▼]        │ ■ BSENT (Alternative)   — 🟢 30/40       │
│                     │                                           │
│                     │ [Approve] [Modify] [Defer]                │
└─────────────────────┴───────────────────────────────────────────┘
```

**Key behaviors:**
- **Override:** Counselor can manually override the cluster assignment if they have additional context (e.g., student interview revealed interests not captured by scores).
- **Approve:** Locks the recommendation into the consultation summary. Generates audit log entry.
- **Modify:** Counselor can rearrange, add, or remove course suggestions before approving.
- **Defer:** Marks the applicant for follow-up consultation — no recommendation locked yet.
- **Audit trail:** Every override, approval, and deferral is logged in the existing immutable audit ledger.

---

## Part B. Manuscript Edit Instructions

### B.1 C1-05 (Background P5 — Synthesis and Gap Identification)

**Insertion point:** After the existing paragraph that discusses prior computerized admission platforms (paragraph beginning "The literature reviewed above..."), and before the paragraph discussing the synthesis of limitations.

**More precisely:** After the sentence ending "...perpetuates reliance on visual inspection and manual tallying" and before the sentence beginning "Beyond procedural inefficiency."

**Draft insertion text:**

> Complementary to the general digitalization efforts described above, recent work at the same institution has explored machine-learning techniques for applicant profiling. Yukee, Bonifacio, Salvador, and Macabitas (2025) applied K-Means clustering to archived ICAT percentile scores at ISPSC Tagudin, identifying four aptitude-based student profiles — language-dominant, math-dominant, spatial-visuospatial, and mixed — through the Elbow method with K equal to four, and evaluated their web-based tool using the System Usability Scale, achieving a score of 68.75, which falls in the marginally acceptable range. Ballesteros, Habon, Lopez, and Tan (2025) similarly employed K-Means clustering on a socio-academic feature space comprising family income and Grade 12 general weighted average, also converging on four clusters, but encountered a significant data coverage constraint: only 130 of 876 first-year students, or 14.8 percent, had sufficiently complete records for clustering, revealing that analytical tools operating on manually collected data cannot achieve population-level coverage. Both studies demonstrate the institutional relevance of data-driven applicant profiling; neither, however, integrates its clustering output into a live admission workflow, nor do they address automated scoring, role-based access control, cryptographic data integrity, data-privacy compliance under Republic Act No. 10173, or applicant-facing self-service — capabilities that SecureCAT is specifically designed to provide.

**Citations:**
- Ballesteros, B. N. V., Habon, P. M., Lopez, J. D. O., & Tan, L. D. (2025). FreshGroup: Clustering first-year student profiles using unsupervised machine learning [Unpublished capstone project]. Ilocos Sur Polytechnic State College.
- Yukee, A. J. M., Bonifacio, C. L., Salvador, J. M. A., & Macabitas, A. P. (2025). A clustering student ICAT score using machine learning algorithm [Unpublished capstone project]. Ilocos Sur Polytechnic State College, Tagudin Campus.

**Rationale:** This insertion bridges the general literature review to the specific institutional gap by introducing the two ISPSC studies that most closely relate to SecureCAT's planned triage module. It positions SecureCAT as addressing both the analytical gap (no integration of clustering into admission workflow) and the data coverage gap (14.8% usable data rate). The connection to Christine Bonifacio as a co-author of Study 1 is noted for the manuscript authors to handle per their discretion.

### B.2 C1-06 (Background P6 — Clinching Statement)

**No structural edit needed.** The clinching paragraph already references "the reviewed literature on computerized admission systems, automated scoring frameworks, and data-privacy-compliant platform design" — after inserting the ISPSC studies into C1-05, the clinching statement naturally encompasses them. Verify the phrase "corroborated by the reviewed literature" reads correctly with the new citations included above it.

### B.3 C1-11 (Scope and Limitations)

**Insertion point:** Within the Scope paragraph, after the sentence that begins "The planned research modules, which constitute the advanced capstone contributions..." and before the sentence listing the specific planned features.

**Draft insertion text:**

> These planned capabilities include a course triage and recommendation module that applies static rule-matching logic derived from validated K-Means cluster parameters published in recent institutional studies (Yukee et al., 2025; Ballesteros et al., 2025), classifying applicants into aptitude-based and socio-academic profiles to support guidance counselors in making informed course recommendations while maintaining human-in-the-loop decision authority.

**Rationale:** The scope paragraph needs to mention the triage module since it is a planned research feature. This phrasing clearly distinguishes static rule matching from live ML, which is critical for the panel's assessment of technical depth vs. scope realism.

### B.4 C1-09 (Objectives)

**Insertion point:** In Objective 2 (Develop), within the existing list of capabilities, add the triage module.

**Draft insertion text to append after "AI-assisted scheduling system with human-in-the-loop constraint optimization":**

> , a course triage module implementing static rule matching based on validated institutional cluster parameters to provide decision support for guidance counselors during applicant course recommendation

**Rationale:** The objectives must enumerate all planned features. The triage module, while using pre-computed parameters rather than live ML, is a meaningful research contribution that bridges the Yukee and Ballesteros findings into an operational workflow.

---

## Part C. Gap Analysis Table

### C.1 Feature-by-Feature Comparison: Study 1 vs Study 2 vs SecureCAT

| Capability / Feature | Study 1 (Yukee et al.) | Study 2 (Ballesteros et al.) | SecureCAT | Coverage Note |
|---|---|---|---|---|
| **Clustering algorithm** | K-Means (K=4, Elbow method) | K-Means (K=4, method NEEDS EXTRACTION) | Static rule matching derived from both studies' validated centroids | SecureCAT uses their outputs, not live K-Means |
| **Primary feature space** | ICAT aptitude percentile scores | Family Income + Grade 12 GWA | Both — dual classification surface | SecureCAT captures both dimensions at intake |
| **Supporting features** | Not specified beyond ICAT scores | Municipality, Sex, SHS Type, SHS Origin | All of the above captured at application intake | Complete coverage |
| **Live data pipeline** | No — archived static data | No — manually extracted static data | Yes — digital application intake captures all fields at source | SecureCAT solves the 14.8% data starvation problem |
| **Automated scoring (OMR)** | No | No | Yes — CV-based OMR with HMAC integrity | Neither study addresses scoring automation |
| **Role-Based Access Control** | Not mentioned | No RBAC | Yes — 6-role RBAC with Laravel Policies | Critical gap in both studies |
| **Data privacy (RA 10173)** | Not mentioned | No privacy framework | Yes — RBAC, audit logging, multi-tenant isolation, DPA alignment | Neither study addresses Philippine data privacy law |
| **Cryptographic score integrity** | No | No | Yes — HMAC-SHA256 signature locks | Neither study addresses score tampering prevention |
| **Applicant-facing portal** | No applicant interface | No applicant interface | Yes — full applicant dashboard with status tracking, AI companion, admission slips | Neither study provides applicant self-service |
| **AI-assisted guidance** | No — clustering provides grouping only, no individualized recommendations | No — clustering only, no course matching or guidance | Yes — RAG-powered AI Companion with course recommendations enhanced by triage profiles | SecureCAT operationalizes their cluster outputs |
| **Course-quota matching** | No | No | Yes — quota-aware triage suggestions with Green/Yellow/Red status | Neither study integrates with institutional quota management |
| **Counselor decision support** | Not mentioned | Not mentioned | Yes — triage panel in Guidance Portal with cluster display, support flags, override capability | New capability enabled by synthesizing both studies |
| **Scheduling management** | Not mentioned | Not mentioned | Yes — AI-assisted scheduling with human-in-the-loop | Outside both studies' scope |
| **Offline resilience** | Not mentioned | Requires internet | Yes — PWA with IndexedDB caching for proctor portal | Neither study addresses infrastructure constraints |
| **Audit trail** | Not mentioned | No logging | Yes — immutable write-only audit ledger | Neither study tracks data access |
| **Multi-tenant architecture** | Not mentioned | Single-campus | Yes — database segregation for multi-campus expansion | Neither study addresses institutional scalability |
| **Document generation** | Not mentioned | Not mentioned | Yes — admission slips, result sheets (PDF/DOCX), consultation summaries | Outside both studies' scope |
| **Proctor management** | Not mentioned | Not mentioned | Yes — proctor assignment, roster management, attendance tracking | Outside both studies' scope |
| **SUS evaluation** | 68.75 (marginally acceptable) | 74.29 (Good) | Planned — SUS + NASA-TLX dual evaluation | SecureCAT targets above 74.29 |
| **Development methodology** | CRISP-DM | Not extracted | AIDLC (AI-assisted development lifecycle) | Different methodologies; SecureCAT's is honest about AI-assisted workflow |
| **Sample size** | NOT PROVIDED | 130 students (14.8% of 876) | Full applicant population via digital intake | SecureCAT eliminates data starvation entirely |

### C.2 What SecureCAT Covers That Neither Study Addresses

1. **End-to-end admission lifecycle** — Both studies analyze pre-existing data in isolation. SecureCAT manages the full pipeline from application through result release.
2. **Data capture at source** — The 14.8% usable rate in Study 2 is direct evidence that manual data collection fails. SecureCAT's digital intake guarantees 100% field completion.
3. **Operational integration of ML outputs** — Both studies produce cluster labels but do nothing with them operationally. SecureCAT's triage module transforms their analytical outputs into actionable counselor decision support.
4. **Institutional governance** — RBAC, audit trails, cryptographic integrity, and RA 10173 compliance are absent from both studies. SecureCAT treats these as foundational, not optional.
5. **Applicant experience** — Neither study gives applicants any interface or visibility. SecureCAT provides a self-service portal with real-time status tracking and AI-assisted guidance.

### C.3 What Data from Their Studies Feeds into the Triage Module

| Data Element | Source Study | Extraction Status | Triage Module Usage |
|---|---|---|---|
| K=4 optimal cluster count (aptitude) | Study 1 (Yukee) | EXTRACTED | Defines the number of aptitude profiles in the rule matrix |
| Cluster profile descriptions (Language/Math/Spatial/Mixed) | Study 1 (Yukee) | EXTRACTED | Maps aptitude scores to profile labels |
| ICAT aptitude component list | Study 1 (Yukee) | PARTIALLY EXTRACTED — full list NEEDS LIBRARY EXTRACTION | Determines which SecureCAT aptitude areas participate in classification |
| Percentile thresholds for aptitude dominance | Study 1 (Yukee) | NOT EXTRACTED — NEEDS LIBRARY EXTRACTION | Defines the exact cutoff rules for cluster assignment |
| K=4 optimal cluster count (socio-academic) | Study 2 (Ballesteros) | EXTRACTED | Defines the number of socio-academic profiles in the rule matrix |
| Income classification thresholds (Poor/Low/Lower Middle) | Study 2 (Ballesteros) | NOT EXTRACTED — NEEDS LIBRARY EXTRACTION | Required for Step 1 of socio-academic classification |
| GWA honors cutoff | Study 2 (Ballesteros) | INFERRED at 90+ — NEEDS LIBRARY EXTRACTION | Required for academic standing classification |
| Municipality-to-Area-Type mapping | Study 2 (Ballesteros) | NOT EXTRACTED — NEEDS LIBRARY EXTRACTION | Required for area type classification |
| Cluster centroid statistics | Both studies | PARTIALLY EXTRACTED (Cluster 3 GWA~85.55, Income~13,453) | Provides anchor points for nearest-cluster matching |
| Cluster sizes | Study 2 (Ballesteros) | PARTIALLY EXTRACTED (C0=29, C1=54, C3=20; C2 MISSING) | Used for prevalence-based weighting of recommendations |

---

## Part D. June 9 Data Gap List

The following values are still needed from the full manuscripts during the library visit. Items are ordered by impact on the triage module specification.

### Critical (blocks triage rule matrix implementation)

| # | Parameter | Source | Where to Look in Manuscript |
|---|---|---|---|
| D-1 | Exact ICAT aptitude component names and count | Study 1 (Yukee) | Methodology or Data Description — feature list |
| D-2 | Percentile thresholds for aptitude dominance classification | Study 1 (Yukee) | Results — cluster assignment criteria |
| D-3 | Exact income thresholds for Poor / Low / Lower Middle / Middle / Rich | Study 2 (Ballesteros) | Methodology — income classification table |
| D-4 | GWA cutoff for "With Honors" | Study 2 (Ballesteros) | Methodology — academic standing classification |
| D-5 | Municipality-to-Area-Type mapping table | Study 2 (Ballesteros) | Results or Appendix — area classification |
| D-6 | Exact cluster centroid coordinates or descriptive statistics for all 4 clusters | Both studies | Results — cluster profile tables |
| D-7 | Cluster 2 student count (Ballesteros) | Study 2 (Ballesteros) | Results — Plate 9/11 or distribution table |

### High (needed for manuscript quality but triage module can proceed with placeholders)

| # | Parameter | Source | Where to Look |
|---|---|---|---|
| D-8 | K-value selection method for Ballesteros (elbow / silhouette / other) | Study 2 | Methodology — optimal K determination |
| D-9 | Sample size for Yukee (number of ICAT records clustered) | Study 1 | Methodology — dataset description |
| D-10 | SUS respondent count and demographic breakdown (Yukee) | Study 1 | Results — SUS evaluation section |
| D-11 | SUS item-level scores (Ballesteros — which items scored low) | Study 2 | Results — SUS per-question breakdown |
| D-12 | Stratified proportional sampling table by program (Ballesteros) | Study 2 | Methodology — sampling procedure |
| D-13 | Academic year of the 876-student population (Ballesteros) | Study 2 | Methodology — population description |

### Medium (enriches discussion but does not block implementation or manuscript)

| # | Parameter | Source | Where to Look |
|---|---|---|---|
| D-14 | Elbow method SSE/distortion values at each K (Yukee) | Study 1 | Results — elbow curve data |
| D-15 | K-Means initialization method (k-means++ vs random) | Study 1 | Methodology — algorithm configuration |
| D-16 | Distance metric and convergence iterations | Study 1 | Methodology — algorithm configuration |
| D-17 | Stakeholder feedback verbatim from Dr. Pedro, Dr. Mostoles, Dr. Valenzuela | Study 2 | Results/Discussion — stakeholder evaluation |
| D-18 | FreshGroup technology stack (framework, language, database) | Study 2 | Methodology or Implementation — development tools |
| D-19 | CRISP-DM phase descriptions and system development mapping (Yukee) | Study 1 | Methodology — CRISP-DM application |
| D-20 | Panel member names for both studies | Both | Title page or acknowledgment section |
| D-21 | Cluster naming conventions — verify "Language-Dominant" etc. are Yukee's own terms | Study 1 | Results — cluster profiles |
| D-22 | Programming language/framework for Yukee's web system | Study 1 | Implementation — development tools |

### Extraction Confidence Classification

Throughout this document, each parameter is classified as one of:

- **EXTRACTED** — confirmed from available abstract/summary text; high confidence
- **INFERRED** — logically deduced from context (e.g., GWA 90+ for honors based on Philippine standards); must be verified
- **NEEDS LIBRARY EXTRACTION** — not available in any extracted material; must be obtained from the full manuscript during the June 9 library visit

---

## Appendix A. Key Numeric Parameters Summary

| Parameter | Study 1 (Yukee) | Study 2 (Ballesteros) | Status |
|---|---|---|---|
| Algorithm | K-Means | K-Means | EXTRACTED |
| Optimal K | 4 | 4 | EXTRACTED |
| K selection method | Elbow Method | NEEDS LIBRARY EXTRACTION | Partial |
| Primary features | ICAT percentile scores | Income + GWA | EXTRACTED |
| SUS score | 68.75 | 74.29 | EXTRACTED |
| SUS adjective | Marginally acceptable / "OK" | Good | EXTRACTED |
| Methodology | CRISP-DM | Not extracted | Partial |
| Sample/population | NOT PROVIDED | 130/876 (14.8%) | Partial |
| Data starvation | Unknown | 14.8% (critical finding) | EXTRACTED |
| Christine Bonifacio connection | Co-author (Study 1) | N/A | EXTRACTED — note for manuscript handling |

## Appendix B. Full References

Ballesteros, B. N. V., Habon, P. M., Lopez, J. D. O., & Tan, L. D. (2025). FreshGroup: Clustering first-year student profiles using unsupervised machine learning [Unpublished capstone project]. Ilocos Sur Polytechnic State College.

Yukee, A. J. M., Bonifacio, C. L., Salvador, J. M. A., & Macabitas, A. P. (2025). A clustering student ICAT score using machine learning algorithm [Unpublished capstone project]. Ilocos Sur Polytechnic State College, Tagudin Campus.

---

*Document generated: 2026-06-09 by kanban worker (task t_e7b270c8). All items marked NEEDS LIBRARY EXTRACTION or INFERRED require verification against full manuscripts during the June 9 library visit.*
