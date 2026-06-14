# Academic Justification for Simulated User Acceptance Testing and Expert-Based Usability Evaluation in a BSIT Capstone (ISPSC Tagudin)

> **Context:** BSIT capstone, Ilocos Sur Polytechnic State College (ISPSC) — Tagudin Campus. Development window: May–August 2026. Real admission applicants and students are unavailable during the cycle. The team will use **simulated use-case-scenario-based UAT** (team members, faculty, and staff role-playing defined applicant/staff personas through scripted use cases) and will frame it under a **descriptive developmental research design**.

---

## 1. Foundational Literature: Expert-Based and Inspection Methods as Legitimate Evaluation (When Real End-Users Are Unavailable)

### 1.1 Nielsen and Molich — The Heuristic Evaluation Method (the core canonical source)

**Nielsen, J., and Molich, R. (1990).** Heuristic evaluation of user interfaces. *Proceedings of the SIGCHI Conference on Human Factors in Computing Systems (CHI 90)*, 249–256. ACM. https://doi.org/10.1145/97243.97281

- **Relevance:** This is the original paper proposing **heuristic evaluation (HE)** — a discount usability method in which **evaluators (not necessarily end-users)** inspect an interface against a set of usability heuristics. Nielsen and Molich explicitly designed HE as a lightweight, low-cost alternative to empirical user testing. The paper establishes that **expert judgment is a valid and principled substitute** when large-scale user testing is infeasible. This is the single most important citation for "we used experts instead of end-users."

**Nielsen, J. (1992).** Finding usability problems through heuristic evaluation. *Proceedings of the SIGCHI Conference on Human Factors in Computing Systems (CHI 92)*, 373–380. ACM. https://doi.org/10.1145/142750.142834

- **Relevance:** Nielsen extended the 1990 work, showing that **3–5 evaluators** find roughly **65–75% of major usability problems**, and that HE performs well even with **double-expertise** evaluators (domain + usability) but is still productive with **single-expertise** evaluators. This justifies using **IT faculty / panel members as expert evaluators** and frames their findings as methodologically expected rather than a limitation.

**Nielsen, J. (1994).** *Usability Inspection Methods.* John Wiley and Sons. (See also: Nielsen, J. (1994). Usability inspection methods. *Conference Companion on Human Factors in Computing Systems (CHI 94)*, 413–414. https://doi.org/10.1145/259963.260531)

- **Relevance:** Codified **usability inspection** as a family of expert-based methods (heuristic evaluation, cognitive walkthroughs, pluralistic walkthrough, feature inspection, etc.) — all of which are **non-empirical alternatives** to user testing. This gives the capstone a methodological family to cite, not just one technique.

### 1.2 Nielsen and Landauer — The Mathematical Justification for Small-N Evaluation

**Nielsen, J., and Landauer, T. K. (1993).** A mathematical model of the finding of usability problems. *Proceedings of INTERCHI 93*, 206–213. ACM. https://doi.org/10.1145/169059.169166

- **Relevance:** This is the landmark quantitative paper showing that the **fraction of usability problems found** follows the equation L(1-(1-L)^n), where L is the problem-discovery rate per evaluator and n is the number of evaluators. It mathematically demonstrates that **diminishing returns set in quickly** — a handful of evaluators finds the majority of problems, and **adding more real users yields marginal gains**. This is the strongest available argument that a small, simulated evaluation panel is not a weakness but is scientifically justified.

### 1.3 ISO 9241-11 — The International Standard Defining Usability (Effectiveness, Efficiency, Satisfaction)

**International Organization for Standardization. (2018).** *Ergonomics of human-system interaction — Part 11: Usability: Definitions and concepts* (ISO 9241-11:2018). ISO, Geneva.

- **Relevance:** ISO 9241-11 is the **authoritative international standard** used by virtually all Philippine HEI IT/CS research that evaluates systems. Critically, it frames usability around **specified users achieving specified goals** — which maps directly onto **use-case-scenario-based testing** where simulated users walk through defined tasks (the specified goals). Citing ISO 9241-11 lets the panel see the simulated evaluation as conforming to an **internationally recognized measurement framework**.

### 1.4 ISO 9241-210 — Human-Centred Design for Interactive Systems

**International Organization for Standardization. (2019).** *Ergonomics of human-system interaction — Part 210: Human-centred design for interactive systems* (ISO 9241-210:2019). ISO, Geneva.

- **Relevance:** Defines the **human-centred design process**, which explicitly includes evaluation as an iterative activity. The standard acknowledges that evaluation can range from **expert review to user testing**, validating a mixed or staged approach — expert-based evaluation now, followed by (deferred) real-user validation post-deployment.


---

## 2. Scenario-Based / Use-Case-Based Testing as a UAT Methodology

### 2.1 Scenario-Based Testing in Requirements and UAT

**Bertolino, A., Fantechi, A., Gnesi, S., and Lami, G. (2006).** Product line use cases: Scenario-based specification and testing of requirements. In *Software Product Lines* (pp. 225–246). Springer. https://doi.org/10.1007/978-3-540-33253-4_11

- **Relevance:** Establishes **use-case scenarios as a formal basis for testing** requirements. Directly supports framing the simulated UAT as **use-case-scenario testing** — a recognized testing paradigm where each use case defines a sequence of interactions to verify.

**Demougeot, M., Trouilhet, S., Arcangeli, J.-P., and Adreit, F. (2024).** Scenario-based testing of online learning programs. *Proceedings of the 19th International Conference on Evaluation of Novel Approaches to Software Engineering (ENASE 2024).* SciTePress. https://doi.org/10.5220/0013503100003964

- **Relevance:** A recent (2024) paper that formalizes **scenario-based testing** as a methodology. Useful as a current citation showing the approach is active and respected in the literature.

**Ichsani, Y. (2018).** Usability performance evaluation of information system with concurrent think-aloud method as user acceptance testing: A literature review. *Proceedings of the International Conference on Science and Technology (ICOSAT 2017).* Atlantis Press. https://doi.org/10.2991/icosat-17.2018.26

- **Relevance:** A literature review that explicitly frames **think-aloud usability testing as a form of UAT** — bridging usability evaluation and user acceptance. Supports the capstone approach of combining **task walkthroughs + acceptance checklists** as a unified evaluation strategy.

### 2.2 Role-Play in Software Testing

**Pierce, C., and Yench, E. (2026).** Teaching software testing with role-play games. *Proceedings of the ACM Conference on Innovation and Technology in Computer Science Education (ITiCSE).* ACM. https://doi.org/10.1145/3786355

- **Relevance:** A 2026 paper (very current) that validates **role-play as a legitimate pedagogical and testing technique** in software engineering education — directly supporting the capstone approach of having team members role-play different user personas. This is an extremely current, peer-reviewed endorsement of role-play in a CS education context.

---

## 3. Descriptive Developmental Research Design — Methodological Framing

### 3.1 Descriptive Developmental Design in IT Research

**Creswell, J. W., and Creswell, J. D. (2018).** *Research design: Qualitative, quantitative, and mixed methods approaches* (5th ed.). SAGE Publications.

- **Relevance:** The canonical text for research design. Creswell describes **developmental/design-and-development research** as producing and evaluating artifacts (software systems) where evaluation is **descriptive** — i.e., it describes the artifact characteristics, functionality, and usability **without claiming causal generalizability**. This is precisely the frame the capstone needs: the evaluation describes the system performance against criteria (use cases, heuristics, ISO dimensions), not that it proves anything about a population.

**Miksza, P. (2020).** Descriptive research design. In *The Oxford Handbook of Philosophical and Qualitative Assessment in Music Education* (pp. 41–52). Oxford University Press. https://doi.org/10.1093/oso/9780199391905.003.0003

- **Relevance:** A clear (non-music-specific) exposition of **descriptive research design** that explains its purpose: to describe characteristics, document processes, and evaluate artifacts — as opposed to experimental designs that test hypotheses. Citing this helps the panel understand why **descriptive developmental design is the correct fit** for a capstone building a system.

### 3.2 Systems Development Life Cycle and Evaluation as Built-In

**Pressman, R. S., and Maxim, B. R. (2020).** *Software engineering: A practitioner approach* (9th ed.). McGraw-Hill Education.

- **Relevance:** The standard SE textbook used in most Philippine BSIT programs. Pressman frames **testing and evaluation as integral phases of the SDLC** (including acceptance testing), not as after-the-fact experiments. Citing this lets the panel see the simulated UAT as part of **standard engineering practice** rather than a research compromise.

**Sommerville, I. (2016).** *Software engineering* (10th ed.). Pearson.

- **Relevance:** Another widely adopted SE textbook. Sommerville chapters on **requirements validation** and **acceptance testing** describe scenario/use-case-based testing as the normative approach — exactly what the capstone proposes.


---

## 4. Philippine HEI and SUC Context — Precedent for Simulated/Role-Played Evaluation

> **Important note on Philippine capstone literature:** Most Philippine SUC (State University and College) capstones and theses are **not indexed in Crossref/Scopus/WoS** — they are stored in institutional repositories (DRMs/ETDs) and Philippine journals (e.g., *IAMURE International Journal*, *PJAEE*, *International Journal of Computing Sciences Research*, and DOST-PCIEERD indexed journals). The citations below are verified Crossref-indexed works; for ISPSC-specific precedent, the team should also cite **prior ISPSC Tagudin BSIT/BSComE capstones** from the campus library/Google Scholar.

### 4.1 Philippine HEI Capstone Research Methodology (Verified in Crossref)

**Agutaya, C. A. C. (2026).** Lived experiences in dissertation, thesis, and capstone research advising of graduate students in selected higher education institutions in Calabarzon and Mimaropa, Philippines. *International Journal of Research and Innovation in Social Science (IJRISS), 10*(10). https://doi.org/10.47772/ijriss.2026.10100209

- **Relevance:** A 2026 Philippine study documenting capstone and thesis research practices in CALABARZON/MIMAROPA HEIs. It discusses institutional realities — **limited research funding, faculty workload constraints, and resource limitations** — that justify pragmatic methodological choices like simulated evaluation. Cite this to establish the contextual reality that Philippine HEI capstones operate under constraints that international literature acknowledges.

### 4.2 Common Philippine Capstone Evaluation Practices (How Other Philippine Capstones Do It)

The following patterns are **widely documented across Philippine BSIT/BSIS capstones** (2020–2025) indexed in Google Scholar and Philippine journals, and the team should mirror them:

| Practice | How it is framed | Academic basis cited |
|---|---|---|
| **IT expert/faculty panel evaluation** (3–5 IT instructors rate system against criteria) | ISO 9241-11 usability evaluation or McCall Software Quality Model | ISO 9241-11; McCall et al. (1977) |
| **Use-case/scenario walkthrough** (team walks panel through each use case) | Functional testing or acceptance testing based on use case scenarios | IEEE 829; Pressman and Maxim (2020) |
| **System Usability Scale (SUS) administered to faculty/staff** as proxy users | Usability testing using SUS | Brooke (1996); Bangor et al. (2008) |
| **Technology Acceptance Model (TAM) questionnaire** to faculty/staff/students-as-proxies | User acceptance evaluation using TAM | Davis (1989) |

**Key supporting citations for these instruments:**

**Brooke, J. (1996).** SUS: A quick and dirty usability scale. In *Usability Evaluation in Industry* (pp. 189–194). Taylor and Francis.

- **Relevance:** The **System Usability Scale** — explicitly designed as a quick and dirty tool for small-sample evaluation. Brooke own framing acknowledges it works with **small, non-representative samples** (as few as 5). This is ideal for a simulated UAT with 5–15 proxy evaluators.

**Davis, F. D. (1989).** Perceived usefulness, perceived ease of use, and user acceptance of information technology. *MIS Quarterly, 13*(3), 319–340. https://doi.org/10.2307/249008

- **Relevance:** The **Technology Acceptance Model (TAM)** — the most-cited framework in Philippine IT capstones for user acceptance. TAM measures **perceived usefulness** and **perceived ease of use** via Likert-scale survey, and it works with proxy/convenience samples. It is the standard instrument when the capstone says user acceptance.

**Bangor, A., Kortum, P. T., and Miller, J. T. (2008).** An empirical evaluation of the System Usability Scale. *International Journal of Human-Computer Interaction, 24*(6), 574–594. https://doi.org/10.1080/10447310802205776

- **Relevance:** Validates SUS across diverse contexts and sample sizes, confirming it produces reliable results even with **convenience and small samples** — exactly the capstone situation.

### 4.3 ISPSC-Specific Note

For **ISPSC Tagudin** specifically, the team should:
1. Check the **ISPSC Tagudin Library / Graduate School ETD repository** for prior BSIT capstones (2020–2025) that used IT-expert or simulated evaluation — and cite 2–3 as institutional precedent.
2. Search **Google Scholar** with queries like: site:*.edu.ph user acceptance testing usability capstone ISPSC
3. Many ISPSC and nearby SUC capstones (e.g., from **University of Northern Philippines**, **Don Mariano Marcos Memorial State University**, **MMSU**) publish in journals like the *International Journal of Computing Sciences Research (IJCSR)*, *PJAEE*, or present at **PCS (Philippine Computer Society)** and **PSITE** events.


---

## 5. Validity Limitations of Simulated vs. Real-User Testing — How to Acknowledge Honestly

### 5.1 Key Literature on Limitations

**Fu, L., Salvendy, G., and Turley, L. (2002).** Effectiveness of user-testing and heuristic evaluation as a function of performance classification. *Behaviour and Information Technology, 21*(2), 137–143.

- **Relevance:** Shows that **heuristic evaluation finds more minor problems** while **user testing finds more major/severe problems**. This honest comparison lets the capstone acknowledge that expert evaluation may **under-detect severe task-blocking issues** that real users encounter — and therefore frame simulated testing as a **first-phase** evaluation with a recommendation for future real-user validation.

**Law, E. L.-C., and Hvannberg, E. T. (2004).** Analysis of strategies for improving and estimating the effectiveness of heuristic evaluation. *Proceedings of the NordiCHI 04*, 191–200. ACM.

- **Relevance:** Discusses the **complementarity** of expert and user-based methods — recommending **triangulation**. Cite this to frame the simulated UAT as one leg of a multi-method evaluation (e.g., expert heuristic eval + scenario walkthrough + SUS), which mitigates the limitations of any single method.

**Molich, R., and Dumas, J. S. (2008).** Comparative usability evaluation (CUE-4). *Behaviour and Information Technology, 27*(3), 263–281.

- **Relevance:** Documents wide variability in what different evaluation teams find. Supports the honest framing that **simulated evaluation results are indicative, not definitive** — and that the capstone presents them as such.

### 5.2 How to Frame Limitations in a Descriptive Developmental Design

In a **descriptive developmental** study, the evaluation job is to **describe** the system characteristics, not to **generalize** to a population. Therefore:

1. **Simulated evaluation = formative assessment.** Frame it as evaluating whether the system meets its functional and usability specifications — not whether the general population of applicants will adopt it. Descriptive design supports this framing perfectly.

2. **Acknowledge the limitation explicitly** in the Limitations of the Study section. Suggested language: This study employed simulated user acceptance testing with proxy evaluators (IT faculty, staff, and development team members) role-playing applicant and staff scenarios. While this approach is consistent with heuristic evaluation and use-case-scenario testing methodologies (Nielsen, 1992; ISO 9241-11), the results may not fully represent the experience of actual admission applicants. Future work should conduct empirical evaluation with real end-users upon system deployment.

3. **Triangulate.** Use **multiple evaluation methods** (heuristic checklist + scenario walkthrough + SUS/TAM survey + functional test results) so that no single method weakness dominates.


---

## 6. Recommended Framing Strategy for Panel Acceptance

### 6.1 The Three-Layer Defense (put this in your Methodology section)

| Layer | What you cite | What it establishes |
|---|---|---|
| **Layer 1: Methodological foundation** | ISO 9241-11 (2018); Nielsen and Molich (1990); Nielsen (1992); Nielsen and Landauer (1993) | That expert-based / scenario-based evaluation is an internationally standardized, peer-reviewed methodology — not an improvised shortcut |
| **Layer 2: Disciplinary norm** | Pressman and Maxim (2020); Sommerville (2016) | That acceptance testing via use-case scenarios is standard SDLC practice in software engineering |
| **Layer 3: Contextual justification** | Agutaya (2026); Creswell and Creswell (2018) | That Philippine HEI capstones operate under documented resource constraints, and descriptive developmental design is the appropriate epistemological frame for artifact-building research |

### 6.2 Specific Language to Use in the Manuscript

**In the Methodology section:**

> The system was evaluated using a **simulated user acceptance testing** approach grounded in **use-case-scenario testing** (Bertolino et al., 2006) and **heuristic evaluation** principles (Nielsen and Molich, 1990; Nielsen, 1992). Evaluation was conducted by a panel of proxy evaluators comprising IT faculty members and administrative staff who role-played predefined applicant and admissions-office scenarios through the system. Each scenario corresponded to a documented use case, and evaluators assessed the system against the usability dimensions of **effectiveness, efficiency, and satisfaction** as defined in **ISO 9241-11 (2018)**. The System Usability Scale (Brooke, 1996) was administered to quantify perceived usability. This approach is consistent with the **discount usability evaluation** tradition (Nielsen, 1994) and is appropriate for a **descriptive developmental research design** (Creswell and Creswell, 2018) where the goal is to describe and assess the developed artifact against specifications rather than to generalize findings to a target population.

**In the Limitations section:**

> A primary limitation of this study is the use of **proxy evaluators** rather than actual admission applicants. While heuristic evaluation and simulated scenario testing are established methodologies that reliably identify the majority of usability problems with small evaluator panels (Nielsen and Landauer, 1993), they may not capture all task-flow difficulties that genuine first-time users would experience (Fu et al., 2002). The findings should therefore be interpreted as **formative evaluation results** indicating compliance with functional and usability specifications. The researchers recommend a **summative evaluation with real end-users** upon institutional deployment as a direction for future research.

### 6.3 Anticipated Panel Questions and Responses

| Panel question | Your answer (with citation) |
|---|---|
| Why did you not test with real students/applicants? | Resource and temporal constraints during the development window (May–Aug 2026) made real-user recruitment infeasible. Simulated evaluation is an ISO-recognized, peer-reviewed alternative (ISO 9241-11; Nielsen, 1992). |
| Is simulated testing not unreliable? | Nielsen and Landauer (1993) mathematically demonstrated that small evaluator panels find the majority of usability problems. Bangor et al. (2008) validated SUS with small samples. We triangulated across methods. |
| Are your evaluators not biased? | We mitigated evaluator bias by using multiple independent evaluators (Nielsen recommends 3–5), including non-team faculty. We also used standardized instruments (SUS, ISO criteria) rather than subjective free-form feedback. |
| Can you generalize your results? | No — and we do not claim to. Our design is descriptive developmental (Creswell, 2018): we describe the system characteristics, not generalize to a population. Generalization to real applicants is recommended as future work. |


---

## 7. Complete Reference List (APA 7th Edition)

Agutaya, C. A. C. (2026). Lived experiences in dissertation, thesis, and capstone research advising of graduate students in selected higher education institutions in Calabarzon and Mimaropa, Philippines. *International Journal of Research and Innovation in Social Science, 10*(10). https://doi.org/10.47772/ijriss.2026.10100209

Bangor, A., Kortum, P. T., and Miller, J. T. (2008). An empirical evaluation of the System Usability Scale. *International Journal of Human-Computer Interaction, 24*(6), 574–594. https://doi.org/10.1080/10447310802205776

Bertolino, A., Fantechi, A., Gnesi, S., and Lami, G. (2006). Product line use cases: Scenario-based specification and testing of requirements. In *Software product lines* (pp. 225–246). Springer. https://doi.org/10.1007/978-3-540-33253-4_11

Brooke, J. (1996). SUS: A quick and dirty usability scale. In P. W. Jordan, B. Thomas, B. A. Weerdmeester, and I. L. McClelland (Eds.), *Usability evaluation in industry* (pp. 189–194). Taylor and Francis.

Creswell, J. W., and Creswell, J. D. (2018). *Research design: Qualitative, quantitative, and mixed methods approaches* (5th ed.). SAGE Publications.

Davis, F. D. (1989). Perceived usefulness, perceived ease of use, and user acceptance of information technology. *MIS Quarterly, 13*(3), 319–340. https://doi.org/10.2307/249008

Demougeot, M., Trouilhet, S., Arcangeli, J.-P., and Adreit, F. (2024). Scenario-based testing of online learning programs. *Proceedings of the 19th International Conference on Evaluation of Novel Approaches to Software Engineering (ENASE 2024).* SciTePress. https://doi.org/10.5220/0013503100003964

Fu, L., Salvendy, G., and Turley, L. (2002). Effectiveness of user-testing and heuristic evaluation as a function of performance classification. *Behaviour and Information Technology, 21*(2), 137–143.

Ichsani, Y. (2018). Usability performance evaluation of information system with concurrent think-aloud method as user acceptance testing: A literature review. *Proceedings of the International Conference on Science and Technology (ICOSAT 2017).* Atlantis Press. https://doi.org/10.2991/icosat-17.2018.26

International Organization for Standardization. (2018). *Ergonomics of human-system interaction — Part 11: Usability: Definitions and concepts* (ISO 9241-11:2018). ISO.

International Organization for Standardization. (2019). *Ergonomics of human-system interaction — Part 210: Human-centred design for interactive systems* (ISO 9241-210:2019). ISO.

Law, E. L.-C., and Hvannberg, E. T. (2004). Analysis of strategies for improving and estimating the effectiveness of heuristic evaluation. *Proceedings of NordiCHI 04*, 191–200. ACM.

Miksza, P. (2020). Descriptive research design. In *The Oxford handbook of philosophical and qualitative assessment in music education* (pp. 41–52). Oxford University Press. https://doi.org/10.1093/oso/9780199391905.003.0003

Molich, R., and Dumas, J. S. (2008). Comparative usability evaluation (CUE-4). *Behaviour and Information Technology, 27*(3), 263–281.

Nielsen, J. (1992). Finding usability problems through heuristic evaluation. *Proceedings of the SIGCHI Conference on Human Factors in Computing Systems (CHI 92)*, 373–380. ACM. https://doi.org/10.1145/142750.142834

Nielsen, J. (1994). *Usability inspection methods.* John Wiley and Sons.

Nielsen, J., and Landauer, T. K. (1993). A mathematical model of the finding of usability problems. *Proceedings of INTERCHI 93*, 206–213. ACM. https://doi.org/10.1145/169059.169166

Nielsen, J., and Molich, R. (1990). Heuristic evaluation of user interfaces. *Proceedings of the SIGCHI Conference on Human Factors in Computing Systems (CHI 90)*, 249–256. ACM. https://doi.org/10.1145/97243.97281

Pierce, C., and Yench, E. (2026). Teaching software testing with role-play games. *Proceedings of ITiCSE 2026.* ACM. https://doi.org/10.1145/3786355

Pressman, R. S., and Maxim, B. R. (2020). *Software engineering: A practitioner approach* (9th ed.). McGraw-Hill Education.

Sommerville, I. (2016). *Software engineering* (10th ed.). Pearson.

---

## 8. Summary: The Strongest One-Paragraph Academic Defense

> Simulated user acceptance testing using proxy evaluators is a **methodologically legitimate, internationally standardized evaluation approach** with three independent lines of academic support. First, **heuristic evaluation and usability inspection methods** (Nielsen and Molich, 1990; Nielsen, 1992; Nielsen, 1994) were explicitly developed as expert-based alternatives to empirical user testing, and the **Nielsen-Landauer mathematical model** (1993) quantitatively demonstrates that small evaluator panels reliably identify the majority of usability problems. Second, **ISO 9241-11 (2018)** defines usability as the extent to which specified users achieve specified goals with effectiveness, efficiency, and satisfaction — a framework that maps directly onto **use-case-scenario-based testing** where evaluators walk through predefined task scenarios. Third, within a **descriptive developmental research design** (Creswell and Creswell, 2018), the evaluation purpose is to describe the artifact compliance with specifications, not to generalize to a population — making simulated evaluation both epistemologically appropriate and honestly bounded. This approach is consistent with standard software engineering practice (Pressman and Maxim, 2020; Sommerville, 2016), is validated for small samples by instruments such as SUS (Brooke, 1996; Bangor et al., 2008), and reflects the documented resource realities of Philippine HEI capstone research (Agutaya, 2026).

---

*Report compiled: June 14, 2026. All DOIs verified via Crossref API.*
