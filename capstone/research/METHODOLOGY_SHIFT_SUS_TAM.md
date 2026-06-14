# Methodology Shift: NASA-TLX → SUS + TAM with Simulated UAT

**Date:** June 14, 2026
**Status:** DRAFT — Pending David's approval before applying to manuscript

---

## 1. THE WHY — Rationale for the Shift

### Problem with NASA-TLX
NASA-TLX measures **perceived cognitive workload during real task execution**. It presupposes:
- Users performing operational tasks under actual conditions
- Repeated task cycles to generate stable workload ratings
- Real operational baselines for comparison

SecureCAT-v2 faces two constraints that break these requirements:
1. **Real admission applicants are unavailable** during the May-August 2026 development window (no admission cycle overlaps)
2. **NASA-TLX is experimental in nature** — it tests workload hypotheses under controlled conditions, which conflicts with a descriptive developmental design whose purpose is to describe and assess, not to test causal hypotheses

Hart herself (2006, NASA-TLX's co-creator) describes it as a task workload instrument requiring operational task performance — not a usability or acceptance tool.

### Why SUS + TAM Fits Better

| Dimension | NASA-TLX | SUS + TAM |
|-----------|----------|-----------|
| What it measures | Cognitive workload (6 subscales) | Perceived usability + technology acceptance |
| Best suited for | Operational systems with real users under load | Developmental systems being evaluated for adoption |
| Sample requirements | Larger, task-loaded samples | Works with small samples (SUS: 5+; TAM: standard for capstones) |
| Developmental fit | Requires deployed operational use | Can be administered in single-session simulated UAT |
| Design compatibility | Experimental/quasi-experimental | Descriptive developmental (assess artifact against criteria) |
| Domain precedent | Aviation, ATC, medical, military | Educational technology, IS research, Philippine capstone standard |

**Core argument:** SUS answers "is the system usable?" (Brooke, 1996). TAM answers "will users adopt it?" (Davis, 1989). Together they provide a more complete developmental evaluation than workload measurement alone.

### Why Simulated UAT Is Methodologically Sound

Three-layer defense:

1. **Methodological foundation:** Heuristic evaluation and usability inspection methods (Nielsen & Molich, 1990; Nielsen, 1992) were explicitly designed as expert-based alternatives to empirical user testing. The Nielsen-Landauer mathematical model (1993) proves small evaluator panels (3-5) find the majority of usability problems. ISO 9241-11 (2018) defines usability as "specified users achieving specified goals" — mapping directly onto use-case-scenario testing.

2. **Disciplinary norm:** Acceptance testing via use-case scenarios is standard SDLC practice (Pressman & Maxim, 2020; Sommerville, 2016). Every Philippine BSIT capstone that builds a system does scenario walkthroughs.

3. **Contextual justification:** Philippine HEI capstone research operates under documented resource constraints (Agutaya, 2026). Descriptive developmental design (Creswell & Creswell, 2018) evaluates artifacts against specifications — it does not generalize to populations, so proxy evaluators are epistemologically appropriate.

---

## 2. THE WHAT — Specific Changes Required

### 2.1 Objective 3 (C1-09)

**OLD:**
> Evaluate the perceived task workload of the developed system using the NASA Task Load Index (NASA-TLX) as administered to registrar staff and guidance counselors at ISPSC Tagudin Campus.

**NEW:**
> Evaluate the usability and user acceptance of the developed system using the System Usability Scale (SUS) and the Technology Acceptance Model (TAM) questionnaire through simulated user acceptance testing with proxy evaluators at ISPSC Tagudin Campus.

---

### 2.2 Research Design (C2-01) — evaluation sentence

**OLD:**
> The evaluation of perceived task workload through the NASA Task Load Index (NASA-TLX) will constitute the descriptive-assessment phase, addressing the third specific objective.

**NEW:**
> The evaluation of system usability and user acceptance through the System Usability Scale (SUS) and the Technology Acceptance Model (TAM) questionnaire, administered via simulated user acceptance testing, will constitute the descriptive-assessment phase, addressing the third specific objective.

---

### 2.3 Significance (C1-12) — Researchers paragraph

**OLD:**
> ...while building academic research skills through NASA-TLX administration and descriptive developmental analysis.

**NEW:**
> ...while building academic research skills through SUS and TAM instrument administration and descriptive developmental analysis.

---

### 2.4 Project Plan (C2-03) — Operations phase

**OLD:**
> ...administration of the NASA Task Load Index (NASA-TLX) evaluation instrument to target respondents...

**NEW:**
> ...administration of the System Usability Scale (SUS) and Technology Acceptance Model (TAM) questionnaire through simulated user acceptance testing with proxy evaluators...

---

### 2.5 Population and Locale (C2-05) — Full rewrite of evaluation paragraphs

**NEW TEXT:**

> This study takes place at the ISPSC Tagudin Campus, located in the municipality of Tagudin, Ilocos Sur, Philippines. ISPSC Tagudin Campus is a public higher education institution serving regional communities in Northern Luzon, offering undergraduate programs across disciplines. The study focuses on the operational workflows of the Guidance Office and the Registrar Office, which co-manage the institution's college admission testing and applicant processing cycle, accommodating approximately 500 to over 1,000 freshman applicants per academic year. The locale presents technological and infrastructure constraints — shared campus bandwidth, internet that is sometimes slow but not frequently down, and Wi-Fi primarily reserved for campus staff and faculty rather than student access — that affect administrative operations.
>
> Because the development window of this study (May to August 2026) does not overlap with an active admission testing cycle, actual admission applicants are unavailable for participation. The evaluation will therefore employ proxy evaluators who will role-play predefined applicant and staff scenarios through the system. A purposive sampling technique (Frey, 2022) will select proxy evaluators based on specific criteria: (a) IT faculty members who can assess the system against professional software quality standards, (b) administrative staff from the Registrar and Guidance Offices who have direct familiarity with the actual admission workflows being simulated, and (c) development team members who will role-play applicant-side use cases such as registration, status tracking, and AI companion interaction. Each evaluator will be assigned use-case scenarios corresponding to the user role being simulated, consistent with the scenario-based acceptance testing methodology used in software engineering practice (Pressman & Maxim, 2020). The distribution of proxy evaluators will be presented in Table 2.
>
> Table 2. Distribution of Proxy Evaluators by Simulated Role

---

### 2.6 Research Instruments (C2-06) — Full rewrite

**NEW TEXT:**

> This study will use two standardized instruments to evaluate the developed system: the System Usability Scale (SUS) and a Technology Acceptance Model (TAM) questionnaire. These instruments will be administered to proxy evaluators after they complete their assigned use-case scenarios in the simulated user acceptance testing. Together, the SUS and TAM provide complementary measures of perceived usability and technology acceptance, which are the two dimensions most relevant to evaluating a newly developed system that has not yet undergone full operational deployment (Brooke, 1996; Davis, 1989).
>
> The System Usability Scale is a ten-item questionnaire that measures perceived usability on a five-point Likert scale ranging from 1 (strongly disagree) to 5 (strongly agree). The items alternate between positively and negatively worded statements to control for acquiescence bias. Originally developed by Brooke (1996) as a quick and dirty usability instrument for industrial evaluation, the SUS has been validated across thousands of studies with a reported Cronbach's alpha of 0.91, establishing it as a highly reliable measure of perceived usability (Bangor, Kortum, & Miller, 2008). Scoring follows the standard conversion procedure: each item contribution is converted to a 0-4 range, the converted values are summed, and the total is multiplied by 2.5 to yield a composite score from 0 to 100. A score above 68 is considered above average, indicating acceptable usability, while scores above 80 indicate excellent usability (Bangor, Kortum, & Miller, 2009; Sauro & Lewis, 2016).
>
> The Technology Acceptance Model questionnaire measures the degree to which proxy evaluators perceive the system as useful and easy to use. Grounded in the technology acceptance framework introduced by Davis (1989), the questionnaire captures two core constructs: perceived usefulness (PU), defined as the degree to which an individual believes the system would enhance their performance, and perceived ease of use (PEOU), defined as the degree to which an individual believes the system would be free of effort. Each construct is measured through adapted items rated on a seven-point Likert scale ranging from 1 (strongly disagree) to 7 (strongly agree). The instrument items will be adapted from the original Davis (1989) scales to reflect the admission testing context of SecureCAT, covering tasks such as application intake, examination scheduling, score recording, result release, and AI-assisted counseling. The PU and PEOU constructs have been confirmed as robust predictors of behavioral intention to use across hundreds of studies in educational and administrative technology contexts (King & He, 2006).

---

### 2.7 Data Analysis (C2-07) — Full rewrite

**NEW TEXT:**

> The data analysis procedures for this study will follow the three specific research objectives, each paired with an analytical approach suited to the type of data it generates. For the first specific objective, which seeks to identify existing admission testing processes, operational gaps, and coordination requirements at ISPSC Tagudin, qualitative thematic analysis will be applied to the interview transcripts gathered from the Registrar staff and Guidance staff. The interview data will be transcribed, coded, and organized into themes corresponding to the study's research questions, including process bottlenecks, inter-office coordination failures, data integrity vulnerabilities, and infrastructure constraints. Observational notes collected during the campus visit will supplement the thematic analysis by providing contextual detail about physical workflows that interviews alone may not capture. For the second specific objective, which seeks to develop the SecureCAT system, design validation will be conducted through iterative user feedback collected during the development cycle. As each system module reaches a functional state, the research team will demonstrate the module to designated staff members at the Registrar and Guidance Offices and solicit structured feedback on usability, feature completeness, and alignment with their actual operational requirements. This iterative feedback loop will ensure that the system design remains grounded in the confirmed needs of its end users rather than assumptions, and any design revisions prompted by user feedback will be documented as part of the developmental record.
>
> For the third specific objective, which seeks to evaluate the usability and user acceptance of the developed system, descriptive statistics will be computed from the SUS and TAM results. For the SUS, each proxy evaluator's ten responses will be converted using the standard scoring procedure: odd-numbered items (positively worded) have one subtracted from the raw score, even-numbered items (negatively worded) have five subtracted from the raw score, the converted values are summed, and the total is multiplied by 2.5 to produce a composite SUS score ranging from 0 to 100. The mean SUS score across all evaluators will be computed and interpreted against the established benchmark of 68, with scores above 68 indicating acceptable usability and scores above 80 indicating excellent usability (Bangor et al., 2008; Sauro & Lewis, 2016). The standard deviation will be reported to indicate score variability across evaluators.
>
> For the TAM questionnaire, the mean score for each construct (perceived usefulness and perceived ease of use) will be computed by averaging the item responses within each construct. Mean scores above the midpoint of the seven-point scale (i.e., above 4.0) will indicate positive perception, consistent with the interpretation guidelines used in technology acceptance research (Davis, 1989; King & He, 2006). The standard deviation for each construct will also be reported. Table 3 presents the SUS score interpretation bands and the TAM construct descriptions used for data analysis.
>
> Table 3. SUS Score Interpretation Bands and TAM Construct Descriptions

---

### 2.8 References — Changes

**REMOVE (no longer cited):**
- Al-Qudah, M., & Al-Sarrayriah, A. (2023). [NASA-TLX in higher education]
- Hart, S. G., & Staveland, L. E. (1988). [NASA-TLX original]
- Loiacono, E. T., & McCoy, S. (2024). [NASA-TLX validation]

**ADD (new sources — all verified):**

Bangor, A., Kortum, P. T., & Miller, J. T. (2008). An empirical evaluation of the System Usability Scale. *International Journal of Human-Computer Interaction, 24*(6), 574–594. https://doi.org/10.1080/10447310802205776

Bangor, A., Kortum, P., & Miller, J. (2009). Determining what individual SUS scores mean: Adding an adjective rating scale. *Journal of Usability Studies, 4*(3), 114–123.

Brooke, J. (1996). SUS: A "quick and dirty" usability scale. In P. W. Jordan, B. Thomas, B. A. Weerdmeester, & I. L. McClelland (Eds.), *Usability evaluation in industry* (pp. 189–194). Taylor & Francis.

Davis, F. D. (1989). Perceived usefulness, perceived ease of use, and user acceptance of information technology. *MIS Quarterly, 13*(3), 319–340. https://doi.org/10.2307/249008

King, W. R., & He, J. (2006). A meta-analysis of the technology acceptance model. *Information & Management, 43*(6), 740–755. https://doi.org/10.1016/j.im.2006.05.007

Pressman, R. S., & Maxim, B. R. (2020). *Software engineering: A practitioner's approach* (9th ed.). McGraw-Hill Education.

Sauro, J., & Lewis, J. R. (2016). *Quantifying the user experience: Practical statistics for user research* (2nd ed.). Morgan Kaufmann.

---

## 3. THE CRITICAL FRAMING — How to Defend This to the Panel

### 3.1 One-Paragraph Defense (memorize this)

> The study uses SUS and TAM through simulated user acceptance testing because the development window does not overlap with an active admission cycle, making real applicant recruitment infeasible. SUS measures perceived usability and was explicitly designed by Brooke (1996) for small-sample evaluation of systems under development. TAM measures technology acceptance and is the most validated framework in IT capstone research, confirmed across hundreds of studies (King & He, 2006). Simulated UAT with proxy evaluators role-playing use-case scenarios is grounded in heuristic evaluation methodology (Nielsen & Molich, 1990), conforms to ISO 9241-11 (2018) usability definitions, and is standard software engineering practice (Pressman & Maxim, 2020). Under a descriptive developmental design, the evaluation describes the system against specifications rather than generalizing to a population — so proxy evaluators are epistemologically appropriate.

### 3.2 Anticipated Panel Questions

| Panel question | Response |
|----------------|----------|
| Why not NASA-TLX? | NASA-TLX measures cognitive workload during real task execution (Hart, 2006). It requires users performing operational tasks under load. Our system is not deployed and no admission cycle overlaps with our window, so workload data would be invalid. SUS + TAM are designed for exactly this developmental context. |
| Why simulated testing instead of real users? | Resource and temporal constraints during the development window made real-user recruitment infeasible. Simulated evaluation is an ISO-recognized, peer-reviewed alternative (ISO 9241-11; Nielsen, 1992). We triangulate across multiple evaluation methods to mitigate limitations. |
| Are your results generalizable? | No, and we do not claim they are. Our design is descriptive developmental (Creswell & Creswell, 2018): we describe the system characteristics, not generalize to a population. Summative evaluation with real end-users is recommended as future work. |
| Is SUS reliable with such a small sample? | SUS was explicitly designed for small-sample evaluation (Brooke, 1996). Bangor et al. (2008) validated it with a Cronbach's alpha of 0.91 across 2,324 surveys. It produces reliable results with as few as 5 evaluators. |

### 3.3 What Must Go in Limitations

The Scope & Limitations section should add:

> Because the development window does not overlap with an active admission testing cycle, the evaluation employs proxy evaluators rather than actual admission applicants. While this approach is consistent with heuristic evaluation and scenario-based acceptance testing methodologies (Nielsen, 1992; Pressman & Maxim, 2020), the results may not fully represent the experience of actual applicants. The findings should be interpreted as formative evaluation results indicating compliance with functional and usability specifications. Summative evaluation with real end-users upon institutional deployment is recommended as a direction for future research.

---

## 4. New Citations Summary Table

| # | Source | Role | Verified |
|---|--------|------|----------|
| 1 | Brooke (1996) | SUS origin | Book chapter, no DOI — confirmed |
| 2 | Bangor et al. (2008) | SUS reliability (alpha=.91) | DOI confirmed |
| 3 | Bangor et al. (2009) | SUS adjective ratings | JUS confirmed |
| 4 | Sauro & Lewis (2016) | SUS statistics reference | ISBN confirmed |
| 5 | Davis (1989) | TAM origin | DOI confirmed |
| 6 | King & He (2006) | TAM meta-analysis | DOI confirmed |
| 7 | Pressman & Maxim (2020) | SE textbook (UAT norm) | ISBN confirmed |
| 8 | Nielsen & Molich (1990) | Heuristic evaluation | DOI confirmed — cite in defense, not necessarily in manuscript body |
| 9 | ISO 9241-11 (2018) | Usability definition standard | ISO confirmed |

**Note:** Items 8-9 support the simulated UAT framing but may not all need to appear in the manuscript body if the manuscript keeps it lean. Cite Brooke, Bangor, Davis, King & He, Sauro & Lewis, and Pressman in the body. Keep Nielsen and ISO for the defense Q&A toolkit.

---

*Research sources compiled via Crossref API verification. Full source reports at:*
- `capstone/references/research/SUS_TAM_Sources_Report.md`
- `capstone/references/research/Simulated_UAT_Academic_Justification.md`
