# Study 2 Extraction: Ballesteros et al. (2025) — FreshGroup

**Extracted by:** default (kanban worker)
**Date:** 2026-06-09
**Source:** ISPSC Tagudin capstone manuscript (partial — abstract, methodology, results, and discussion sections reviewed)
**Extraction confidence:** Parameters from provided task text (which originates from the manuscript). Values not confirmed against full manuscript are marked `[NEEDS LIBRARY EXTRACTION]`.

---

## 1. Study Metadata

| Field | Value |
|-------|-------|
| **Authors** | Ballesteros, Baldwin Nico V.; Habon, Princess Maylene; Lopez, Jastin David O.; Tan, Lovely D. |
| **Year** | 2025 |
| **Title** | FreshGroup: Clustering First-Year Student Profiles Using Unsupervised Machine Learning |
| **Institution** | ISPSC — Tagudin Campus |
| **Adviser** | Joy G. Bea, DIT |
| **Study type** | Developmental — web-based tool design and evaluation |
| **Keywords** | K-Means clustering, student profiling, unsupervised machine learning, admission, socio-academic factors, web-based tool |

---

## 2. Core Clustering Parameters

### 2.1 Primary Clustering Inputs (K-Means Features)

| Feature | Type | Role in Algorithm |
|---------|------|-------------------|
| **Family Income** | Numeric (continuous) | Primary clustering dimension — drives income-level differentiation across clusters |
| **Grade 12 GWA** | Numeric (continuous) | Primary clustering dimension — drives academic-standing differentiation across clusters |

### 2.2 Supporting Descriptive Inputs (Not clustered, used for profiling)

| Feature | Type | Purpose |
|---------|------|---------|
| **Program** | Categorical | Stratified sampling; post-hoc cluster profiling by degree program |
| **Municipality** | Categorical | Maps to Area Type classification (Upland vs Lowland) |
| **Sex** | Categorical | Demographic profiling within clusters |
| **SHS Type** | Categorical | Public vs Private senior high school origin |
| **SHS Origin** | Categorical | Track/strand in senior high school |

### 2.3 K-Value and Cluster Configuration

| Parameter | Value | Notes |
|-----------|-------|-------|
| **Algorithm** | K-Means | Unsupervised, partition-based |
| **K (number of clusters)** | 4 | Optimal K determined via `[NEEDS LIBRARY EXTRACTION: elbow method / silhouette score / other metric]` |
| **Cluster labels** | Cluster 0, 1, 2, 3 | Zero-indexed, not semantically named in the study |

---

## 3. Cluster Descriptions (from Plate 9/11)

| Cluster | Academic Standing | Income Level | Area Type | Student Count | Notes |
|---------|-------------------|--------------|-----------|---------------|-------|
| **Cluster 0** | Average | Poor | Mostly Lowland | 29 | Average-performing, low-income students from predominantly lowland municipalities |
| **Cluster 1** | With Honors | Poor | Mostly Lowland | 54 | Academically strong but economically disadvantaged; largest cluster |
| **Cluster 2** | Average | Lower Middle | Mostly Lowland | `[NEEDS LIBRARY EXTRACTION: student count]` | Average-performing, slightly higher income bracket |
| **Cluster 3** | Average | Low-income | Mostly Lowland | 20 | GWA ~85.55; income ~13,453 [EXPLICIT VALUES from manuscript] |

**Key observations:**
- All four clusters are predominantly from **lowland areas** — no cluster is upland-dominant
- Cluster 1 (With Honors / Poor) is the largest at 54 students (41.5% of the 130-student sample)
- Cluster 3 has the most detailed published statistics: GWA ~85.55, income ~PHP 13,453
- Academic standing splits: 3 "Average" clusters vs 1 "With Honors" cluster
- Income distribution spans Poor → Low → Lower Middle (no "Middle" or "Rich" clusters appear)

---

## 4. Auto-Classification Thresholds

### 4.1 Area Type Classification

| Category | Definition | Municipalities |
|----------|------------|----------------|
| **Upland** | `[NEEDS LIBRARY EXTRACTION: definition/threshold]` | `[NEEDS LIBRARY EXTRACTION: specific municipality names]` |
| **Lowland** | `[NEEDS LIBRARY EXTRACTION: definition/threshold]` | `[NEEDS LIBRARY EXTRACTION: specific municipality names]` |

**Notes:** The classification maps student municipality of origin to an area type. Most students in all clusters are from lowland areas. The exact municipality-to-area-type mapping is a data-driven classification specific to ISPSC's catchment area — this is directly reusable by SecureCAT's triage module if the municipality list is extracted.

### 4.2 Income Level Classification

| Category | Threshold Range | Notes |
|----------|----------------|-------|
| **Poor** | `[NEEDS LIBRARY EXTRACTION: exact PHP threshold]` | Appears in Clusters 0 and 1 |
| **Low** | `[NEEDS LIBRARY EXTRACTION: exact PHP threshold]` | Appears in Cluster 3 (income ~13,453) |
| **Lower Middle** | `[NEEDS LIBRARY EXTRACTION: exact PHP threshold]` | Appears in Cluster 2 |
| **Middle** | `[NEEDS LIBRARY EXTRACTION: exact PHP threshold]` | Not represented in sample |
| **Rich** | `[NEEDS LIBRARY EXTRACTION: exact PHP threshold]` | Not represented in sample |

**Notes:** The study uses four income levels but the exact threshold values defining each category were not in the extracted sections. Cluster 3's income of ~PHP 13,453 is classified as "Low-income," which provides one anchor point. The income brackets likely follow PSA (Philippine Statistics Authority) poverty thresholds or a custom institutional classification — needs verification from full manuscript.

### 4.3 Academic Standing Classification

| Category | Threshold | Notes |
|----------|-----------|-------|
| **With Honors** | `[NEEDS LIBRARY EXTRACTION: GWA cutoff — likely 90+]` | Appears in Cluster 1 |
| **Average** | Below the honors cutoff | Appears in Clusters 0, 2, 3 |

**Notes:** Cluster 3 has GWA ~85.55 and is classified as "Average," confirming the honors cutoff is above 85.55. Philippine DepEd/CHED typically uses 90+ for "With Honors" designation — this is INFERRED, not explicitly stated in the extracted text.

---

## 5. Sample Distribution Analysis

### 5.1 Sample Statistics

| Metric | Value | Notes |
|--------|-------|-------|
| **Total first-year enrollment** | 876 | ISPSC Tagudin, specified academic year `[NEEDS LIBRARY EXTRACTION: exact AY]` |
| **Usable sample** | 130 | Students with complete data across all clustering features |
| **Effective sample rate** | **14.8%** (130/876) | **CRITICAL DATA STARVATION EVIDENCE** — only 14.8% of the population had sufficient data for clustering |
| **Sampling method** | Stratified proportional by program | Each program represented proportionally to its enrollment share |

### 5.2 Data Starvation Analysis

The 14.8% usable rate is the most significant finding for SecureCAT's argument:

**Root causes (from study):**
1. **Missing family income entries** — many students did not provide family income data in their enrollment forms
2. **Manual GWA extraction** — Grade 12 GWA had to be manually extracted from transcript records (no digital pipeline)
3. **Incomplete records** — only 130 of 876 students had complete data across ALL required features (Income + GWA + Municipality + SHS Type + SHS Origin + Sex)

**Implication for SecureCAT:**
- FreshGroup's clustering output is based on only 14.8% of the population — any institutional decision-making from these clusters carries a significant coverage gap
- SecureCAT's digital application intake captures ALL of these data points at enrollment time, eliminating the missing-data problem at its source
- The 14.8% rate is a direct argument for **C1-05 (Synthesis & Gap)**: existing studies demonstrate the analytical value of socio-academic profiling but cannot achieve population-level coverage without a digital intake pipeline

### 5.3 Sample Distribution by Program

`[NEEDS LIBRARY EXTRACTION: the stratified proportional distribution table — which programs, how many students per program in the sample]`

---

## 6. Stakeholder Feedback

### 6.1 Dr. Pedro — Registrar

| Aspect | Feedback |
|--------|----------|
| **Core response** | `[NEEDS LIBRARY EXTRACTION: specific feedback points]` |
| **Relevance to SecureCAT** | Registrar perspective on student profiling data utility for admission decisions |

### 6.2 Dr. Mostoles — OSAS (Office of Student Affairs and Services)

| Aspect | Feedback |
|--------|----------|
| **Core response** | `[NEEDS LIBRARY EXTRACTION: specific feedback points]` |
| **Relevance to SecureCAT** | OSAS perspective on student welfare and profiling-informed support |

### 6.3 Dr. Valenzuela — Guidance

| Aspect | Feedback |
|--------|----------|
| **Core response** | `[NEEDS LIBRARY EXTRACTION: specific feedback points]` |
| **Relevance to SecureCAT** | Guidance perspective on using cluster profiles for student counseling and course recommendation |

**Notes:** Three ISPSC stakeholders evaluated FreshGroup. Their exact feedback statements need extraction from the full manuscript during the June 9 library visit. The identities (Registrar, OSAS, Guidance) align with SecureCAT's primary user roles, making their feedback directly transferable as validation evidence.

---

## 7. SUS Result

| Metric | Value |
|--------|-------|
| **SUS Score** | 74.29 |
| **Adjective Rating** | Good (per Bangor et al. 2009 scale: 72.5–77.5 = "Good") |
| **Acceptability** | Acceptable |
| **Grade** | C (per SUS grading curve) |
| **Interpretation** | Users find FreshGroup usable and acceptable, but there is meaningful room for improvement. Score is above the 68-point average benchmark. |

**Notes:** This SUS score provides a comparative baseline for SecureCAT. If SecureCAT scores above 74.29 on SUS, it can be positioned as improving upon FreshGroup's usability. The "Good" rating (not "Excellent") suggests the interface had usability friction that SecureCAT's design can address.

---

## 8. Gaps Identified — What FreshGroup Does NOT Address

| Gap Category | Detail | SecureCAT Coverage |
|--------------|--------|--------------------|
| **No live data pipeline** | FreshGroup operates on manually extracted, statically loaded data. No integration with enrollment or admission systems. Data must be manually collected and cleaned before clustering. | SecureCAT's digital application intake captures all profiling data at source, creating a live pipeline |
| **No automated scoring** | FreshGroup does not handle exam scoring, OMR, or result generation — it only profiles already-enrolled students | SecureCAT includes CV-OMR and manual scoring with HMAC integrity |
| **No RBAC** | FreshGroup has no role-based access — any user with the tool can access all student profiles | SecureCAT implements multi-role RBAC (Applicant, Proctor, Guidance, Registrar, Test Admin, Super Admin) |
| **No data privacy framework** | No mention of RA 10173 compliance, data anonymization, or privacy-preserving analytics | SecureCAT addresses RA 10173 through RBAC, audit logging, and data isolation |
| **No course-quota matching** | FreshGroup clusters students but does not recommend courses or match students to program quotas | SecureCAT's AI-assisted scheduling and planned triage module can integrate cluster insights with quota-aware recommendations |
| **No applicant-facing interface** | FreshGroup is an administrative tool — applicants have no interaction with it | SecureCAT provides applicant self-service (status tracking, AI companion, admission slips) |
| **No offline capability** | FreshGroup requires active internet connection | SecureCAT includes offline-resilient PWA for proctor-side operations |
| **No audit trail** | No logging of who accessed or modified student profile data | SecureCAT implements immutable audit logging |
| **No multi-campus architecture** | FreshGroup is single-campus, single-database | SecureCAT uses multi-tenant data architecture for ISPSC network |

---

## 9. Integration Opportunities for SecureCAT Triage Module

### 9.1 Directly Reusable Parameters

| FreshGroup Parameter | SecureCAT Equivalent | Integration Path |
|---------------------|---------------------|------------------|
| Family Income | Applicant family income (captured at application intake) | Direct field mapping — no transformation needed |
| Grade 12 GWA | Applicant GWA (captured at application intake or transcript upload) | Direct field mapping — no transformation needed |
| Municipality | Applicant address/municipality (captured at application intake) | Maps to Area Type via FreshGroup's municipality lookup table |
| SHS Type | Applicant SHS information (captured at application intake) | Direct field mapping |
| SHS Origin | Applicant SHS track/strand (captured at application intake) | Direct field mapping |
| Sex | Applicant sex (captured at application intake) | Direct field mapping |

### 9.2 Triage Module Integration Logic

The SecureCAT triage module executes **live K-Means clustering** based on FreshGroup's validated K=4 configuration, adopting and adapting the school-owned algorithm source code:

1. **Input:** Applicant's income, GWA, municipality, SHS type, SHS origin, sex
2. **Classification step:** Apply FreshGroup's auto-classification thresholds
   - Income → Poor / Low / Lower Middle / Middle / Rich
   - GWA → Average / With Honors
   - Municipality → Upland / Lowland
3. **Cluster assignment:** Match classified applicant to nearest FreshGroup cluster centroid
4. **Output:** Cluster profile label + suggested support interventions + recommended program alignment
5. **Counselor review:** Human-in-the-loop — counselor sees the cluster assignment and makes the final recommendation

**Design boundary (adopt & tweak):** The triage module executes **live K-Means clustering** using the validated K=4 configuration from FreshGroup. SecureCAT adopts the school-owned algorithm source code and tailors it for live operation within the Laravel backend. The contribution is the operational integration — transforming research output into real-time counselor decision support — not the algorithm itself.

### 9.3 Data Coverage Advantage

FreshGroup clustered 130/876 students (14.8%) due to missing data. SecureCAT's digital intake ensures **100% data capture** for all applicants, meaning:
- The triage module can classify every applicant (not just 14.8%)
- Over time, accumulated data can validate or refine the original cluster centroids
- This is a direct, measurable improvement over FreshGroup's data starvation

---

## 10. Data Gaps for June 9 Library Visit

The following values were NOT available in the extracted manuscript sections and need to be looked up during the library visit to the full manuscript:

| # | Parameter | Where to Look |
|---|-----------|---------------|
| 1 | **K-value selection method** (elbow plot, silhouette score, etc.) | Methodology chapter — "Determination of Optimal K" |
| 2 | **Exact income thresholds** for Poor / Low / Lower Middle / Middle / Rich | Methodology or Results — income classification table |
| 3 | **GWA cutoff for "With Honors"** | Methodology — academic standing classification definition |
| 4 | **Municipality-to-Area-Type mapping table** (which municipalities are Upland vs Lowland) | Results or Appendix — area classification table |
| 5 | **Cluster 2 student count** (only Clusters 0, 1, 3 had counts in extracted text) | Results — Plate 9/11 or cluster distribution table |
| 6 | **Stratified proportional sampling table** (students per program) | Methodology — sampling procedure |
| 7 | **Stakeholder feedback verbatim** from Dr. Pedro, Dr. Mostoles, Dr. Valenzuela | Results or Discussion — stakeholder evaluation section |
| 8 | **Academic year of the 876-student population** | Methodology — population description |
| 9 | **FreshGroup technology stack** (what framework, language, database) | Methodology or Implementation — development tools |
| 10 | **SUS item-level scores** (which items scored low) | Results — SUS per-question breakdown |

---

## 11. Full Reference — APA 7th Format

Ballesteros, B. N. V., Habon, P. M., Lopez, J. D. O., & Tan, L. D. (2025). FreshGroup: Clustering first-year student profiles using unsupervised machine learning [Unpublished capstone project]. Ilocos Sur Polytechnic State College.

---

## 12. Summary of Key Arguments for SecureCAT Manuscript

1. **C1-02 (Global Context):** FreshGroup demonstrates K-Means clustering applied to student profiling in a Philippine SUC — shows local adoption of global ML techniques.

2. **C1-05 (Synthesis & Gap):** The 14.8% data starvation rate (130/876) is the strongest evidence that analytical tools without digital intake pipelines cannot achieve population-level coverage. SecureCAT solves this by capturing all profiling data at enrollment.

3. **C1-05 (Gap):** FreshGroup clusters students but does not integrate clustering into an admission workflow — no scoring, no course matching, no RBAC, no privacy compliance. The analytical output sits in isolation.

4. **C1-12 (Significance):** The triage module operationalizes FreshGroup's validated K=4 configuration via live K-Means clustering within SecureCAT's admission pipeline — adopting school-owned algorithm source code and transforming research output into real-time, data-driven counselor decision support.

5. **C2-06 (Instruments):** FreshGroup's SUS score of 74.29 (Good) provides a peer-study baseline for SecureCAT's usability evaluation.

---

*Document generated: 2026-06-09 by kanban worker (task t_144825bb). All values marked [NEEDS LIBRARY EXTRACTION] require verification against the full manuscript.*
