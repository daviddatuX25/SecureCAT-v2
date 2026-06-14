# Evaluation Strategy
## SecureCAT-v2 Usability and Acceptance Assessment Framework

> [!IMPORTANT]
> **Purpose:** Outline the research methodology for evaluating the SecureCAT-v2 system. The study uses a dual-instrument approach (SUS + TAM) administered through simulated user acceptance testing with proxy evaluators. This approach was selected over NASA-TLX because (1) the development window does not overlap with an active admission cycle, making real applicant recruitment infeasible, and (2) SUS + TAM are more appropriate for evaluating a developmental system under descriptive developmental research design.

---

## 1. Why SUS + TAM Instead of NASA-TLX

| Dimension | NASA-TLX (rejected) | SUS + TAM (adopted) |
|---|---|---|
| What it measures | Cognitive workload (6 subscales) | Perceived usability + technology acceptance |
| Requires | Real users performing operational tasks under load | Proxy evaluators completing use-case scenarios |
| Developmental fit | Experimental — presupposes deployed operational use | Descriptive — assesses artifact against criteria |
| Sample requirements | Larger, task-loaded samples | Works with small samples (SUS: 5+) |
| Our constraint | No admission cycle overlaps with development window | Fits simulated UAT perfectly |

---

## 2. Proxy Evaluator Taxonomy

Because actual admission applicants are unavailable during the May-August 2026 development window, the evaluation employs proxy evaluators who role-play predefined applicant and staff scenarios through the system.

| Evaluator Group | Target | Role Simulated | Assessment Focus |
|---|---|---|---|
| **IT Faculty** | 3-5 IT instructors | Expert-based evaluation across all roles | System quality, architecture, usability heuristics |
| **Administrative Staff** | Registrar + Guidance staff familiar with actual workflows | Staff roles (registrar, counselor, proctor) | Workflow fidelity, feature completeness vs. actual needs |
| **Development Team** | Capstone team members | Applicant-side use cases (registration, status tracking, AI companion) | Consumer usability, task flow completion |

### Justification
- Nielsen & Molich (1990) established heuristic evaluation as a valid expert-based alternative to empirical user testing
- Nielsen & Landauer (1993) mathematically proved small panels (3-5) find majority of usability problems
- ISO 9241-11 (2018) defines usability as "specified users achieving specified goals" — maps directly to use-case-scenario testing
- Pressman & Maxim (2020) frame scenario-based acceptance testing as standard SDLC practice

---

## 3. Dual-Instrument Measurement Approach

### 3.1 System Usability Scale (SUS)
- **Target Dimension:** Perceived Usability
- **Format:** 10-item Likert scale (1 = Strongly Disagree, 5 = Strongly Agree). Alternating positive/negative items.
- **Administration:** All evaluator groups, after completing assigned use-case scenarios.
- **Scoring:** Convert each item to 0-4, sum, multiply by 2.5. Composite 0-100.
- **Benchmark:** >68 = acceptable (above average), >80 = excellent (Brooke, 1996; Bangor et al., 2008; Sauro & Lewis, 2016).
- **Reliability:** Cronbach's alpha = 0.91 (Bangor et al., 2008).

### 3.2 Technology Acceptance Model (TAM) Questionnaire
- **Target Dimension:** Technology Acceptance
- **Constructs:** Perceived Usefulness (PU), Perceived Ease of Use (PEOU)
- **Format:** Adapted items from Davis (1989), rated on 7-point Likert (1 = Strongly Disagree, 7 = Strongly Agree).
- **Administration:** All evaluator groups, administered with SUS in single session.
- **Scoring:** Mean score per construct. Mean >4.0 indicates positive perception.
- **Validation:** Meta-analysis of 88 studies confirms robustness (King & He, 2006).

---

## 4. Data Analysis

1. **SUS Score Computation:**
   - Odd items: subtract 1 from response
   - Even items: subtract response from 5
   - Sum all converted values, multiply by 2.5
   - Report mean and SD across evaluators

2. **TAM Score Computation:**
   - Compute mean for PU items and PEOU items separately
   - Report mean and SD per construct
   - Mean >4.0 (midpoint of 7-point) indicates positive perception

3. **Comparative Analysis:**
   - Cross-reference SUS scores with TAM scores
   - Compare PU vs PEOU to identify acceptance drivers
   - Triangulate with qualitative feedback from use-case walkthroughs

---

## 5. Limitations Acknowledgment

> Because the development window does not overlap with an active admission testing cycle, the evaluation employs proxy evaluators rather than actual admission applicants. While this approach is consistent with heuristic evaluation and scenario-based acceptance testing methodologies (Nielsen, 1992; Pressman & Maxim, 2020), the results may not fully represent the experience of actual applicants. The findings should be interpreted as formative evaluation results. Summative evaluation with real end-users upon institutional deployment is recommended as future research.

---

## References (Core Set)

Bangor, A., Kortum, P. T., & Miller, J. T. (2008). An empirical evaluation of the System Usability Scale. *International Journal of Human-Computer Interaction, 24*(6), 574–594. https://doi.org/10.1080/10447310802205776

Bangor, A., Kortum, P., & Miller, J. (2009). Determining what individual SUS scores mean: Adding an adjective rating scale. *Journal of Usability Studies, 4*(3), 114–123.

Brooke, J. (1996). SUS: A "quick and dirty" usability scale. In P. W. Jordan, B. Thomas, B. A. Weerdmeester, & I. L. McClelland (Eds.), *Usability evaluation in industry* (pp. 189–194). Taylor & Francis.

Davis, F. D. (1989). Perceived usefulness, perceived ease of use, and user acceptance of information technology. *MIS Quarterly, 13*(3), 319–340. https://doi.org/10.2307/249008

King, W. R., & He, J. (2006). A meta-analysis of the technology acceptance model. *Information & Management, 43*(6), 740–755. https://doi.org/10.1016/j.im.2006.05.007

Nielsen, J. (1992). Finding usability problems through heuristic evaluation. *Proceedings of CHI 92*, 373–380. https://doi.org/10.1145/142750.142834

Nielsen, J., & Landauer, T. K. (1993). A mathematical model of the finding of usability problems. *Proceedings of INTERCHI 93*, 206–213. https://doi.org/10.1145/169059.169166

Nielsen, J., & Molich, R. (1990). Heuristic evaluation of user interfaces. *Proceedings of CHI 90*, 249–256. https://doi.org/10.1145/97243.97281

Pressman, R. S., & Maxim, B. R. (2020). *Software engineering: A practitioner's approach* (9th ed.). McGraw-Hill Education.

Sauro, J., & Lewis, J. R. (2016). *Quantifying the user experience: Practical statistics for user research* (2nd ed.). Morgan Kaufmann.

---

*Full research source reports:*
- `capstone/references/research/SUS_TAM_Sources_Report.md`
- `capstone/references/research/simulated_uat_academic_justification.md`
- `capstone/research/METHODOLOGY_SHIFT_SUS_TAM.md`
