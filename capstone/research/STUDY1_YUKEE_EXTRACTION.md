# Study 1 Extraction: Yukee et al. (2025)

## Study Metadata

| Field | Value |
|-------|-------|
| **Title** | A Clustering Student ICAT Score Using Machine Learning Algorithm |
| **Authors** | Yukee, Azriel Jon M.; Bonifacio, Christine Lorence; Salvador, John Mark A.; Macabitas, Aries P. |
| **Year** | 2025 |
| **Institution** | Ilocos Sur Polytechnic State College (ISPSC), Tagudin Campus |
| **Adviser** | George Villanueva, DIT |
| **Panel** | NOT PROVIDED IN ABSTRACT — extract from full manuscript |
| **Program** | NOT PROVIDED IN ABSTRACT — likely BSIT given department |
| **Keywords** | ICAT, K-Means Clustering, Machine Learning, Student Aptitude, Elbow Method, CRISP-DM, SUS |

---

## Data Source

- **Input data:** Archived ICAT percentile scores from the ISPSC Guidance Office
- **Data type:** Historical percentile scores (not raw test scores)
- **Data pipeline:** Static/archived data imported into the system — NOT live test administration
- **Scale:** NOT PROVIDED IN ABSTRACT — number of student records used as training data not specified

---

## Cluster Parameters Extracted

### K-Value Determination

| Parameter | Value |
|-----------|-------|
| **Optimal K** | 4 clusters |
| **Method used** | Elbow Method |
| **Elbow point justification** | NOT PROVIDED IN ABSTRACT — full manuscript needed for distortion/SSE curve details |
| **Alternative methods tried** | NOT PROVIDED IN ABSTRACT — unclear if silhouette analysis or other validation was used |

### Clustering Algorithm

| Parameter | Value |
|-----------|-------|
| **Algorithm** | K-Means |
| **Feature space** | ICAT aptitude component percentile scores |
| **Distance metric** | NOT PROVIDED IN ABSTRACT — likely Euclidean (K-Means default), but unconfirmed |
| **Initialization method** | NOT PROVIDED IN ABSTRACT — k-means++ or random, unknown |
| **Number of iterations/convergence** | NOT PROVIDED IN ABSTRACT |

### ICAT Aptitude Components (Clustering Features)

The ICAT (likely "Ilocos Sur Polytechnic State College College Admission Test" or similar institutional aptitude test) measures multiple aptitude dimensions. The study uses these percentile scores as clustering features.

**Explicitly mentioned as aptitude components in the study:**
- Verbal / Language aptitude
- Mathematical / Quantitative aptitude
- Abstract / Spatial reasoning
- Additional components: NOT PROVIDED IN ABSTRACT — full manuscript needed for complete list

> **INFERENCE:** Based on the cluster profiles described below, the ICAT likely includes at minimum: (1) Verbal/Language, (2) Mathematics, (3) Abstract/Spatial Reasoning, and possibly (4) additional sub-components. The exact component list must be confirmed from the full manuscript.

### Cluster Descriptions / Profiles

Four clusters were identified through K-Means. The task body names these as:

| Cluster | Profile Name | Description |
|---------|-------------|-------------|
| **Cluster 1** | Language-Dominant | Students whose highest percentile scores are in verbal/language aptitude components. Implications: may excel in language-intensive programs (education, communication arts). |
| **Cluster 2** | Math-Dominant | Students whose highest percentile scores are in mathematical/quantitative aptitude. Implications: may be suited for STEM and math-heavy programs. |
| **Cluster 3** | Spatial/Visuospatial | Students with strong performance in abstract reasoning and spatial/visuospatial aptitude. Implications: may perform well in technical and engineering-oriented programs. |
| **Cluster 4** | Mixed/Gaps | Students without a dominant aptitude area, showing either balanced scores across components or notable gaps. Implications: may need additional guidance or exploratory program placement. |

> **NOTE:** The exact cluster profile names ("Language-Dominant", "Math-Dominant", "Spatial/Visuospatial", "Mixed/Gaps") are from the task description. The full manuscript may use different naming conventions. Verify during library visit.

### Percentile Score Thresholds for Aptitude Classification

- **Specific thresholds:** NOT PROVIDED IN ABSTRACT
- Whether students are classified into clusters based on absolute percentile cutoffs (e.g., "above 75th percentile = dominant") or purely on relative K-Means centroid distances is **UNKNOWN**
- This is a critical parameter for SecureCAT integration — extract exact thresholds from full manuscript

---

## SUS Result

| Parameter | Value |
|-----------|-------|
| **SUS Score** | 68.75 |
| **SUS Interpretation** | Marginally acceptable / "OK" range. SUS scores above 68 are considered above average; 68.75 sits just above the acceptability threshold. Per Bangor et al. (2009) adjective ratings, this corresponds to "OK" usability — functional but with room for improvement. |
| **Respondent count** | NOT PROVIDED IN ABSTRACT |
| **Respondent breakdown by role** | NOT PROVIDED IN ABSTRACT — unclear if students, staff, or both |
| **Comparison group** | Useful benchmark for SecureCAT — our target should exceed 68.75 |

---

## Software Development Model

| Parameter | Value |
|-----------|-------|
| **Methodology** | CRISP-DM (Cross-Industry Standard Process for Data Mining) |
| **Phases described** | Business Understanding, Data Understanding, Data Preparation, Modeling, Evaluation, Deployment |
| **Justification for CRISP-DM** | NOT PROVIDED IN ABSTRACT — appropriate for data-mining/ML projects, but exact rationale must be extracted from full manuscript |
| **How CRISP-DM maps to web system development** | NOT PROVIDED IN ABSTRACT — unclear how CRISP-DM handled the web application (frontend/backend) aspects beyond the ML pipeline |

---

## System Capabilities (What It Does)

1. Web-based system for ICAT score analysis
2. K-Means clustering of archived ICAT percentile scores
3. Elbow method for optimal cluster determination (K=4)
4. Student grouping into aptitude-based clusters
5. Pattern identification in academic performance
6. SUS-evaluated usability

---

## Gaps Identified (What Yukee et al. Does NOT Address)

These gaps represent capabilities that SecureCAT covers but Yukee et al. does not:

| Gap Area | SecureCAT Coverage | Yukee et al. Status |
|----------|--------------------|---------------------|
| **Live test administration pipeline** | Full lifecycle from registration → scheduling → proctoring → scoring → results | Static analysis of archived data only |
| **Automated scoring / OMR** | Computer vision OMR scanning with auto-grading | No scoring mechanism — uses pre-existing scores |
| **Role-Based Access Control (RBAC)** | Multi-role system (Applicant, Proctor, Guidance, Registrar, Super Admin) with policy enforcement | NOT MENTIONED — unclear if any access control exists |
| **Cryptographic score integrity (HMAC)** | SHA-256 HMAC signature locks on scores | No score integrity mechanism |
| **Data privacy compliance** | Alignment with Philippine Data Privacy Act, multi-tenant isolation | NOT MENTIONED |
| **Offline resilience (PWA)** | Service worker caching, IndexedDB sync for proctor portal | NOT MENTIONED — assumes internet connectivity |
| **AI-assisted guidance** | RAG-powered AI companion with course recommendations, AI-assisted scheduling | No AI guidance — clustering provides grouping but no individualized recommendations |
| **Audit trail / immutable logging** | Write-only audit ledger with before/after states | NOT MENTIONED |
| **Document generation** | Admission slips, result sheets (PDF/DOCX), consultation summaries | NOT MENTIONED |
| **Multi-tenant architecture** | Database segregation for multi-campus expansion | NOT MENTIONED |
| **Applicant-facing portal** | Full applicant dashboard with status tracking, AI companion | No applicant-facing interface mentioned |
| **Session/scheduling management** | AI-assisted scheduling with constraint optimization | NOT MENTIONED |
| **Proctor management** | Proctor assignment, roster management, attendance tracking | NOT MENTIONED |
| **Course recommendation logic** | AI-driven course recommendations based on aptitude + institutional data | Clustering groups students but does not generate individual course recommendations |

---

## Integration Opportunities for SecureCAT Triage Module

The Yukee et al. clustering approach offers a potential integration path for SecureCAT's applicant triage/guidance features:

### 1. Aptitude-Based Applicant Clustering
- **What:** Apply K-Means clustering to ICAT scores within SecureCAT after results are processed
- **Benefit:** Automatically categorize applicants into aptitude profiles (Language-Dominant, Math-Dominant, Spatial, Mixed)
- **Implementation:** SecureCAT already has the aptitude score data — clustering can run as a post-scoring analysis step

### 2. Cluster-Aware Course Recommendations
- **What:** Feed cluster membership into the AI Companion's recommendation engine
- **Benefit:** Applicants in the "Math-Dominant" cluster could be steered toward STEM programs; "Mixed/Gaps" applicants could receive exploratory guidance
- **Implementation:** Cluster assignment becomes an additional feature in the RAG context for the AI Companion

### 3. Elbow Method for Dynamic Cluster Tuning
- **What:** Re-run the Elbow method when ICAT data changes (new academic year) to verify K=4 remains optimal
- **Benefit:** Prevents stale cluster assignments as applicant populations shift
- **Implementation:** Automated pipeline triggered when a new academic year's data reaches a threshold

### 4. Guidance Counselor Dashboard Integration
- **What:** Display cluster membership and aptitude profile on the consultation summary interface
- **Benefit:** Counselors see at a glance whether an applicant is Language-Dominant, Math-Dominant, etc., aiding consultation conversations
- **Implementation:** Add cluster label to applicant result view in Guidance portal

### 5. Comparative SUS Benchmark
- **What:** Use Yukee et al.'s SUS score of 68.75 as a direct comparison point in SecureCAT evaluation
- **Benefit:** Provides an ISPSC-internal baseline — if SecureCAT scores higher, it demonstrates improved usability over the prior system
- **Implementation:** Cite in Chapter 3 (Results) and Chapter 4 (Discussion) as a comparative reference

---

## Parameters Requiring Full Manuscript Extraction (June 9 Library Visit)

The following parameters could NOT be extracted from the available abstract/summary and must be obtained from the full manuscript:

- [ ] Exact ICAT aptitude component names and count
- [ ] K-Means initialization method (k-means++ vs random)
- [ ] Distance metric used
- [ ] Number of iterations to convergence
- [ ] Elbow method SSE/distortion values at each K
- [ ] Exact cluster centroid coordinates
- [ ] Cluster sizes (number of students per cluster)
- [ ] Percentile score thresholds (if any) for aptitude classification
- [ ] SUS respondent count and demographic breakdown
- [ ] CRISP-DM phase descriptions and how they map to system development
- [ ] Panel member names (for reference list)
- [ ] Programming language / framework used for the web system
- [ ] Database and deployment details
- [ ] Whether any validation beyond Elbow method was used (silhouette, gap statistic)
- [ ] Cluster naming conventions (verify "Language-Dominant" etc. are the study's terms)
- [ ] Any discussion of limitations or future work
- [ ] Sample size (number of ICAT records clustered)

---

## Full Reference (APA 7th Format)

Yukee, A. J. M., Bonifacio, C. L., Salvador, J. M. A., & Macabitas, A. P. (2025). A clustering student ICAT score using machine learning algorithm [Unpublished capstone project]. Ilocos Sur Polytechnic State College, Tagudin Campus.

> **NOTE:** This reference format assumes the work is an unpublished capstone project. If it has been published or presented differently, the format must be adjusted. Verify publication status during the library visit.

---

## Extraction Notes

- **Date extracted:** 2026-06-09
- **Source:** Task description summary (not full manuscript)
- **Confidence level:** MEDIUM — key structural facts (K=4, K-Means, Elbow method, SUS 68.75, CRISP-DM, archived ICAT data) are reliable. Cluster profile names and aptitude component details need manuscript verification.
- **All items marked "NOT PROVIDED IN ABSTRACT" or "INFERENCE" must be verified against the full manuscript.**
