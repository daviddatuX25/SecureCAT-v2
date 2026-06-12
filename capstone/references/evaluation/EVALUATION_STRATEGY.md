# Evaluation Strategy
## SecureCAT-v2 Usability and Workload Assessment Framework

> [!IMPORTANT]
> **Purpose:** Outline the research methodology for evaluating the SecureCAT-v2 system. This strategy upgrades standard usability assessments by leveraging the system's operational deployment, introducing a dual-instrument approach (SUS + NASA-TLX) and a differentiated respondent taxonomy to establish high ecological validity.

---

## 1. Differentiated Respondent Taxonomy

Unlike theoretical prototypes evaluated only in lab settings, SecureCAT-v2's deployment enables us to measure usability and workload across distinct groups of users with varying levels of system exposure and operational responsibilities.

| Respondent Group | Target Role Mapping | Key Characteristics | Assessment Focus |
|---|---|---|---|
| **Experienced Staff Users** | Guidance Counselors, Registrar Admins | Staff members who have actively used or explored the Phase 1 system in real admissions operations. | **Longitudinal Usability:** Focus on long-term efficiency, memorability, error rates, and actual reduction in mental workload compared to historical manual processes. |
| **New Staff Users** | Proctors, Test Administrators, Registrar Staff | Staff members who are introduced to the system for the first time during the Phase 2 evaluation phase. | **Learnability & Initial Exposure:** Focus on how quickly a new user can learn the portal, manage attendance (offline/online), and perform basic administration tasks. |
| **Applicants** | Examinees / Applicants | Candidates who interact with the system solely through the public portal for registration, status tracking, and AI guidance. | **Consumer Usability:** Focus on visual clarity, responsiveness, intuitive navigation, and satisfaction with AI-assisted support. |

---

## 2. Dual-Instrument Measurement Approach

To evaluate the system comprehensively, two separate, standard research instruments will be administered: the **System Usability Scale (SUS)** and the **NASA Task Load Index (NASA-TLX)**.

### 2.1 System Usability Scale (SUS)
*   **Target Dimension:** General System Usability.
*   **Format:** 10-item Likert scale questionnaire (1 = Strongly Disagree, 5 = Strongly Agree).
*   **Execution:** Administered to all three respondent groups after they complete their tasks.
*   **Metric:** Raw scores converted to a scale of 0 to 100. A score above 68 is considered acceptable (above average), with scores above 80 indicating excellent usability.

### 2.2 NASA Task Load Index (NASA-TLX)
*   **Target Dimension:** Perceived Task Workload.
*   **Format:** 6-item scale measuring:
    1.  *Mental Demand* (cognitive effort required)
    2.  *Physical Demand* (physical effort required)
    3.  *Temporal Demand* (time pressure experienced)
    4.  *Performance* (success in accomplishing tasks)
    5.  *Effort* (overall exertion)
    6.  *Frustration Level* (stress, irritation vs. satisfaction)
*   **Execution:** Administered exclusively to **Experienced** and **New** staff users who execute administrative workflows.
*   **Metric:** Scores rated from 0 (Low) to 100 (High) for each dimension, creating a multi-dimensional workload profile.

---

## 3. Data Analysis & Hypotheses

By separating respondents into Experienced and New staff groups, the research team can conduct deeper statistical analysis than a simple average:

1.  **Workload Reduction Analysis:**
    *   Compare the NASA-TLX scores of **Experienced Staff** (rating the digital workflow) against their retroactively recalled workload under the **Historical Manual Baseline**. This directly tests whether the system reduces administrative workload.
2.  **Learnability vs. Familiarity Comparison:**
    *   Compare SUS scores between **Experienced Staff** and **New Staff**.
    *   *Hypothesis:* If Experienced Staff score higher on the SUS, it indicates the system becomes more usable with familiarity. If both score similarly, it proves high initial learnability.
3.  **Cross-Group ANOVA / t-Tests:**
    *   Run independent t-tests on SUS scores across the staff groups to determine if differences are statistically significant, providing a graduate-level analysis for a BSIT capstone.
