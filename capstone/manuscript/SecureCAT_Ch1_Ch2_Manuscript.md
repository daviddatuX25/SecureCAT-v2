<!-- META: docx-sync-version="2026-06-13T19:11:10.052385+00:00" docx-sha256="428ab08f2ca0911e6b9c771fea448ccfdb7d8ea96618ca24e7222eac96ba227a" -->



<!-- TAG: ch1_introduction -->
# Chapter 1

<!-- UPDATE:START -->
# INTRODUCTION
<!-- UPDATE:END -->

<!-- TAG: ch1_bg_of_the_study -->
## Background of the Study

<!-- UPDATE:START -->
Education serves as the foundational cornerstone of societal progress, shaping personal careers, defining institutional missions, and driving collective development. Throughout the history of formal instruction, examination has stood as its inseparable companion, serving not merely as a gatekeeper of academic progress but as a partner in guaranteeing instructional quality, maintaining accountability, and verifying the alignment of learning outcomes. While the theoretical design and psychometric validity of examinations receive rigorous academic scrutiny, the operational management and logistical orchestration of these testing processes are frequently neglected. Despite the availability of proven assessment methodologies, item calibration and testing tools remain underutilized in active administrative workflows, leaving campuses to manage high-stakes testing through fragmented manual routines. This management gap is further strained by the accelerating pace of educational demands, characterized by surging applicant volumes, rapidly shifting career landscapes, and heightened institutional standards. Modern admission gates can no longer function efficiently under legacy administrative patterns that fail to scale with seasonal intake pressures. Because the admission phase represents the initial touchpoint between an aspirant and an institution, the speed and responsiveness of this process directly influence the final enrollment decisions of students. Applicants frequently apply to multiple regional colleges, and administrative delays in releasing entrance examination scores prompt candidates to select competing institutions that provide faster feedback. A campus's ability to coordinate and evaluate admissions serves as a direct reflection of its administrative capability, pointing toward the need for an integrated college admission testing system like SecureCAT. For institutions like the Ilocos Sur Polytechnic State College (ISPSC) Tagudin Campus, automating this gateway not only secures scores but also unifies the workflows of the Guidance and Registrar Offices under a single secure platform. The pursuit of these digital enhancements does not merely serve to elevate administrative convenience; it demonstrates how genuine operational bottlenecks compel the engineering of targeted technical solutions to real institutional problems.

The field of higher education admissions has undergone significant digital transformation since the early 2020s, driven by the convergence of cloud computing, automated assessment, and data privacy regulations. Universities across North America, Europe, and East Asia have widely adopted unified admission platforms that integrate application intake, examination scheduling, automated scoring, and result dissemination into single digital ecosystems (Chen & Liu, 2024). A central component of this transformation is the adoption of optical mark recognition (OMR) systems that scan bubble sheets to produce instant scores, eliminating labor-intensive manual comparison (Park et al., 2023). Beyond traditional OMR, computer vision and deep learning enable AI-based assessment systems that can evaluate handwritten responses, detect cheating, and generate adaptive test items (Kumar & Singh, 2025). Furthermore, the principle of role-based access control (RBAC) has become standard, ensuring staff access to applicant records is governed by institutional roles rather than broad privileges (Williams & Garcia, 2024). To assist applicants, institutions deploy retrieval-augmented generation (RAG) platforms that retrieve policies, catalog details, and admission rules from local databases to provide contextually accurate answers without direct counselor involvement (Müller & Hoffmann, 2023). While these systems compress score release timelines and improve applicant satisfaction, processing sensitive student records through cloud-based AI services introduces security risks on third-party servers, motivating a focus on local data handling architectures (Okafor & Tanaka, 2024).

The Philippine higher education sector, overseen by the Commission on Higher Education (CHED), is undergoing a systemic modernization driven by national digitalization mandates. The enactment of Republic Act No. 10931, the Universal Access to Quality Tertiary Education Act of 2017, dramatically increased SUC enrollment, creating administrative bottlenecks across public campuses (Malaluan & Wang, 2023). CHED has promoted digital transformation to optimize enrollment management and student records processing (Caintic & Lahaylahay, 2024), while the Department of Information and Communications Technology (DICT) has championed the E-Government Act of 2022 and the National Broadband Plan to expand digital services in regional settings (Department of Information and Communications Technology, 2023). Post-pandemic, academic institutions have transitioned from temporary workarounds to permanent, resilient web platforms (Comprendio & Canlas, 2025). These digital systems must strictly comply with Republic Act No. 10173, the Data Privacy Act of 2012, to protect student personally identifiable information and examination records against security breaches (National Privacy Commission, 2024). However, a digital readiness gap persists between metropolitan universities and regional state colleges: urban institutions benefit from mature cloud setups, whereas provincial campuses struggle with limited budget, shared bandwidth, and restricted network access (Caintic & Lahaylahay, 2024; Comprendio & Canlas, 2025; Department of Information and Communications Technology, 2023).

The local operational context at the ISPSC Tagudin Campus reflects these regional challenges during peak enrollment cycles (Caintic & Lahaylahay, 2024). Serving freshman cohorts of 700 to 1,000 applicants under Republic Act No. 10931, the campus relies on a paper-based workflow that strains staff capacity (Malaluan & Wang, 2023). Applicant intake at the Registrar Office is conducted exclusively through paper forms, with physical documents filed in locked cabinets without digital backups. Admission slips are produced manually using word processor templates, and applicants must call or visit in person to check their status. Scheduling between the Registrar and Guidance Offices is arranged via verbal communication and text messages, while physical records lack formal data privacy protocols mandated under Republic Act No. 10173 (National Privacy Commission, 2024). The Guidance Office scores all examinations using the manual stencil method, taking two to three days for a batch of fifty applicants and resulting in a cumulative one-to-two-week results delay. This latency is exacerbated during the enrollment phase because key programs operate under strict quota constraints determined by classroom facilities and faculty availability. Under the recent admissions cycle for the academic year 2026-2027, Program Heads and the Registrar adjust these program slots dynamically, yet the Guidance Office lacks a centralized mechanism to monitor available capacities. Consequently, counselors cannot verify if slots remain in high-demand courses when advising examinees, leading to overallocation risks and administrative friction. These interconnected gaps, which became especially visible during pandemic-related disruptions, underscore the need for a unified, secure, and offline-resilient system on this campus (Comprendio & Canlas, 2025; Department of Information and Communications Technology, 2023).

While global institutions have successfully deployed unified admission systems (Chen & Liu, 2024; Müller & Hoffmann, 2023), and national mandates demand strict data privacy and digitalization (National Privacy Commission, 2024; Department of Information and Communications Technology, 2023), regional campuses like ISPSC Tagudin continue to operate with disconnected legacy platforms that leave significant operational and security gaps (Caintic & Lahaylahay, 2024; Comprendio & Canlas, 2025). Studies in computerized admissions often focus on static record management or cloud-hosted student portals, yet they fail to address campuses that lack persistent connectivity and require offline resilience for high-volume intake (Chen & Liu, 2024). In addition, while cloud-based AI tools can automate applicant support, they introduce severe security risks regarding the exposure of sensitive student records and confidential scoring tables on third-party servers (Okafor & Tanaka, 2024). This leaves a critical research gap: the lack of a secure, unified framework that coordinates inter-office workflows, automates scoring, and embeds local intelligence directly within the campus database. Addressing this gap requires data-driven decision-making systems that can translate raw applicant metrics into actionable academic guidance. In this context, machine learning techniques offer a robust framework for extracting intelligent insights from applicant profiles, enabling institutions to generate data-driven course recommendations based on multi-dimensional aptitude profiles and utilize applicant profiling to optimize placement decisions (Kumar & Singh, 2025). The relevance and validity of this approach have been demonstrated through local institutional research at ISPSC, where K-Means clustering algorithms were applied to historical student datasets. Specifically, Yukee et al. (2025) validated the use of a K=4 clustering configuration on entrance examination results to establish distinct aptitude-based student profiles, while Ballesteros et al. (2025) analyzed the relationship between student academic performance and socioeconomic indicators to guide course routing. However, because these institutional studies were conducted as post-hoc analyses on static databases, their machine learning models remained entirely isolated from the active enrollment workflow, operating retrospectively rather than during the live consultation. Consequently, there remains a critical research gap in designing a unified architecture that executes live K-Means clustering to classify applicant aptitude profiles at the exact moment of consultation, while extending counselor reach through a secure, retrieval-augmented companion that leverages local institutional policies without risking data sovereignty. This profiling capability must be extensible, allowing the intake of supplementary academic parameters such as senior high school grades in mathematics, English, and science to augment basic entrance test scores and General Weighted Average, thereby permitting the Guidance Office to refine and optimize recommendations as institutional placement criteria evolve.

The research methodology and system design were informed by global and national literature on computerized admissions, secure access controls, and local machine learning integration. The selection of this topic was driven by direct observations at ISPSC Tagudin Campus, where the researchers documented paper-based test routing, absent audit trails, scoring errors from manual stencil checks, and fragmented inter-office coordination during peak periods. These operational failures demonstrated that the existing disconnected tools could not sustain rising applicant volumes. This study proposes SecureCAT, a secure and offline-first college admission testing platform that unifies registrar and guidance workflows by integrating computer-vision OMR for automated scoring, a local K-Means clustering microservice for live applicant profiling at the point of consultation, and a RAG-powered AI Companion for pre-examination and post-examination guidance. By bridging administrative data with local academic intelligence, this platform aligns with SDG 4 on Quality Education and SDG 16 on Peace, Justice, and Strong Institutions.
<!-- UPDATE:END -->

<!-- TAG: ch1_conceptual_framework -->
## Conceptual Framework of the Study

<!-- UPDATE:START -->
Input-Process-Output (IPO) Diagram

Figure 1. Conceptual Framework Diagram (Input-Process-Output)
<!-- UPDATE:END -->

<!-- TAG: ch1_objectives -->
## Objectives of the Study

<!-- UPDATE:START -->
This study aims to develop SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin Campus, a web-based platform that centralizes the admission testing pipeline — from registration through automated scoring and AI-assisted counseling — while enforcing zero-trust data governance under the infrastructure constraints of the campus.

More specifically, this study seeks to accomplish the following:

1. Identify the existing admission testing processes, operational workflows, and process gaps at ISPSC Tagudin Campus through descriptive inquiry.

2. Develop SecureCAT incorporating role-based access control, HMAC-SHA256 cryptographic score integrity, computer-vision OMR scoring, dynamic course quota tracking, live K-Means course triage with extensible applicant profiling (including secondary grades) adapted from prior ISPSC studies (Yukee et al., 2025; Ballesteros et al., 2025), offline-resilient proctoring via PWA, AI-assisted scheduling, and role-based data isolation aligned with Republic Act No. 10173.

3. Evaluate the usability and user acceptance of the developed system using the System Usability Scale (SUS) and the Technology Acceptance Model (TAM) questionnaire through simulated user acceptance testing with proxy evaluators at ISPSC Tagudin Campus.
<!-- UPDATE:END -->

<!-- TAG: ch1_scope_delimitations -->
## Scope and Limitation of the Study

<!-- UPDATE:START -->
This study covers the design, development, and evaluation of SecureCAT, a role-based college admission testing system for the Guidance and Registrar Offices at ISPSC Tagudin Campus, Tagudin, Ilocos Sur, Philippines. The system accommodates six user types: applicants, proctors, guidance counselors, registrar staff, registrar administrators, and super administrators. One person may hold more than one role, and assignments can be updated as needs change. The system handles end-to-end admission tasks including web-based application intake, time-limited account activation, real-time status tracking, admission slip and result document generation, examination scheduling with proctor assignment, CSV score import, consultation recording, and staff-applicant notifications. Audit logs capture all system events, and database access is isolated by role. Technical capabilities include computer-vision OMR scoring with HMAC-SHA256 tamper protection, offline PWA proctoring with IndexedDB sync, a RAG-powered AI companion for applicant guidance, an AI scheduling assistant with administrator approval gates, and a live K-Means course triage module with four clusters adapted from Yukee et al. (2025) and Ballesteros et al. (2025) that incorporates dynamic program capacity limits and extensible applicant profiling utilizing senior high school grades in mathematics, English, and science. The study runs from May to August 2026.

SecureCAT addresses only the admission testing process; enrollment, tuition, academic records, and learning management are excluded. The system is tested and evaluated only at ISPSC Tagudin Campus. The OMR module requires a camera or scanner producing clear answer sheet images with pre-printed QR codes for applicant identification; handwritten name matching is not used. The offline PWA requires one initial connection to register the service worker. Examination questions are authored by faculty, and scoring conversion tables are determined and kept confidential by the Guidance Office; the system blocks conversion data access for unauthorized users and exports. Course capacity limits are updated within the database by administrators based on coordination with Program Heads, but automated predictive slot balancing or cross-campus room allocation is excluded. The system follows Republic Act No. 10173 through role-based access control and immutable audit logging, though formal National Privacy Commission auditing falls outside this study.
<!-- UPDATE:END -->

<!-- TAG: ch1_significance -->
## Importance of the Study

<!-- UPDATE:START -->
The Community. The implementation of SecureCAT will benefit residents of Tagudin and surrounding municipalities who comprise the primary applicant pool of ISPSC Tagudin Campus. By unifying the admission testing process into a single role-based digital platform, the system will reduce the economic and time burden on applicants and their families. Applicants from remote municipalities currently travel more than two hours each way and must make multiple visits to submit requirements, check schedules, and claim results. The digitized system will minimize queue bottlenecks and reduce repeated campus visits, making admissions more accessible.

The Registrar Office. The Registrar Office will benefit from automated application intake, admission slip generation, and real-time applicant status tracking that replace manual Microsoft Word encoding and paper-based filing. The integrated scheduling module will formalize the handoff between offices through a shared digital calendar, eliminating verbal and text-based coordination. Role-based data isolation will ensure that registrar staff access only the records pertinent to their function, assisting the office in meeting Republic Act No. 10173 compliance requirements.

The Guidance Office. The Guidance Office will benefit from automated OMR scoring that compresses the two-to-three-day stencil-method grading process into minutes, freeing counselors for student-facing duties. System-wide audit trails will make every score access and modification traceable to an authenticated user. The course triage module will present counselors with applicant aptitude profiles, extensible academic metrics, and live program quota availability during each consultation, reducing the cognitive burden of manual cross-referencing. The RAG-powered AI companion will handle routine pre-examination and post-examination inquiries outside office hours, extending counselor reach without replacing professional judgment.

The Respondents. Registrar staff and guidance counselors will benefit from operational relief. Staff will experience reduced cognitive, physical, and temporal workloads associated with manual scheduling, stencil scoring, record filing, and quota tracking. Applicants will benefit from an intuitive registration interface, automated queue scheduling, and a 24/7 AI companion that supplements the guidance office's Facebook page with structured, validated self-service guidance.

The College or Department. The BSIT Department of ISPSC Tagudin Campus will benefit from a reference implementation that demonstrates how cryptographic verification (HMAC-SHA256), offline PWA synchronization, and computer-vision OMR translate abstract IT theories into institutional solutions using modern frameworks such as Laravel 12 and Svelte 5.

The Students. Students of ISPSC Tagudin Campus will benefit from an equitable testing environment where HMAC-SHA256 verification ensures admission decisions rest on unaltered, verified merit. Automated room scheduling will eliminate manual overlaps, and reduced administrative friction will let applicants focus on examination preparation.

The Researchers. The researchers will gain hands-on experience in the full software development lifecycle under the AIDLC framework, developing competencies in agentic workflows, API security, and offline synchronization, while building academic research skills through SUS and TAM instrument administration and descriptive developmental analysis.

Future Researchers. This study will provide a reusable blueprint for institutional web applications in resource-constrained educational environments. The AIDLC model documentation, zero-trust architecture, and offline synchronization strategies will serve as reference material. The course triage module will offer a concrete model for translating exploratory K-Means clustering research into operational decision-support tools through adopt-and-tweak integration of school-owned algorithm source code (Yukee et al., 2025; Ballesteros et al., 2025) and extensible parameter configuration.
<!-- UPDATE:END -->

<!-- TAG: ch2_methodology -->
# Chapter 2

<!-- UPDATE:START -->
# METHODOLOGY

This chapter presents the procedures for data collection and analysis, covering the research design, software model, project plan, project assignments, population and locale, research instruments, and data analysis.
<!-- UPDATE:END -->

<!-- TAG: ch2_research_design -->
## Research Design

<!-- UPDATE:START -->
This study employs a descriptive developmental research design, a hybrid methodology that combines descriptive inquiry with iterative system construction and validation (Frey, 2022). This model is used in system-development research in Philippine educational institutions, where the objective is to analyze local administrative challenges and subsequently construct and evaluate a web-based platform to address them (Malaya et al., 2022; Olipas, 2023).

The descriptive component will document the operational workflows, coordination gaps, and infrastructure constraints at ISPSC Tagudin Campus, addressing the first specific objective. Data will be gathered through semi-structured interviews with Registrar and Guidance staff. The developmental component will involve the iterative design, construction, and validation of SecureCAT using the AIDLC software model (Addla, 2026; Raja, 2025), addressing the second specific objective. The evaluation of system usability and user acceptance through the System Usability Scale (SUS) and the Technology Acceptance Model (TAM) questionnaire, administered via simulated user acceptance testing, will constitute the descriptive-assessment phase, addressing the third specific objective.
<!-- UPDATE:END -->

<!-- TAG: ch2_software_model -->
## Software Development Model

<!-- UPDATE:START -->
The development of SecureCAT follows the AI-Driven Development Lifecycle (AIDLC), a software engineering framework that replaces traditional human-only development models with structured AI-human collaboration workflows (Addla, 2026; Raja, 2025). Traditional lifecycles, such as the Rapid Application Development (RAD) model, were formulated for human-only team dynamics and do not account for the code generation and testing patterns characteristic of current software development (Raja, 2025). In contrast, the AIDLC framework uses artificial intelligence agents as partners in the design, construction, and verification of software systems, enabling a single developer to cover roles traditionally distributed across entire teams. By adopting this model, the researcher provides an accurate representation of the development process of SecureCAT, which will be constructed using AI assistants (Gemini, Claude, and GitHub Copilot) for code generation, architectural planning, and test automation under human oversight (Addla, 2026). The AIDLC model has three linear phases: Inception, Construction, and Operations.

Figure 2. AI-Driven Development Lifecycle (AIDLC)

Inception. In this phase, the development requirements and software specifications will be conceptualized through Mob Elaboration, wherein the researcher will collaborate with AI assistants to translate observed operational challenges at ISPSC Tagudin Campus into structured technical and architectural requirements. These challenges will be identified through interview data gathering with guidance and registrar office staff at ISPSC Tagudin, which will confirm critical process gaps: manual scoring of answer sheets using paper-based keys, informal scheduling of entrance examinations without centralized coordination, and the absence of audit trails for score modifications and examinee records. During this phase, the researcher will use conversational and code-generation models to design the role-based database schema, outline the system's role-based access control (RBAC) rules, plan the application's service architecture, and define the technical interfaces for the proctor, guidance staff, and registrar roles. The deliverables of the Inception phase will include the conceptual data models, API endpoints, mockups of the public and admin portals, and the feature specifications for the offline-resilient Progressive Web Application (PWA), cryptographic score verification, and AI-assisted scheduling modules.

Construction. In this phase, the system modules will be developed and tested using a Mob Construction workflow, where AI coding agents will generate backend and frontend code while the researcher will act as the architect, reviewer, and quality gate. The backend will be constructed using PHP 8.4 and the Laravel 12 framework, using Inertia.js v2 to bridge server-side routing with the Svelte 5 frontend component library, with styling powered by Tailwind CSS v4. One capability to be built during this phase is the Optical Mark Recognition (OMR) scanning module, which will be designed and implemented from scratch as a software-based image processing feature that will enable the system to capture and score examinee answer sheets directly from scanned images — this is a newly developed capability within SecureCAT and does not replace any pre-existing OMR system at ISPSC. Additionally, the ML-assisted course triage module will be constructed by adopting school-owned K-Means algorithm source code from prior ISPSC institutional studies (Yukee et al., 2025; Ballesteros et al., 2025) and wrapping it as a Python microservice (FastAPI) that will communicate with the Laravel backend via internal API — this architectural decision keeps the ML engine in its native language (scikit-learn) while enabling bidirectional data streaming between the admission platform and the clustering service. During coding, the AI agents will generate the core database migrations, Eloquent models, controllers, and services — specifically implementing the HMAC-SHA256 score signing algorithm, the write-only immutable audit logging system, the OMR image processing pipeline, the ML triage microservice integration layer, and the offline IndexedDB synchronization middleware. Automated unit and feature testing will be integrated into the construction loop; tests will run using PHPUnit 11 to validate the behavioral correctness of the AI-generated code, so code quality defects or security vulnerabilities will be flagged and corrected before code integration.

Operations. In this phase, the system will undergo pilot deployment, automated monitoring, and empirical evaluation. The SecureCAT application will be deployed on a local server environment to replicate the network conditions of the ISPSC Tagudin campus, and database seeders will be executed to populate the system with simulated applicant records and test schedules. The researcher will use automated scripts and AI diagnostic assistants to monitor system resource consumption, audit log performance, and OMR image scanning accuracy — validating the newly built OMR scanning capability under realistic conditions — confirming that the platform will operate reliably under simulated peak load.
<!-- UPDATE:END -->

<!-- TAG: ch2_project_plan -->
## Project Plan

<!-- UPDATE:START -->
Figure 3. Project Gantt Chart of SecureCAT

The project timeline spans four months, from May 2026 to August 2026, and is organized into the three sequential phases of the AI-Driven Development Lifecycle (AIDLC) — adopted from Addla (2026) and Raja (2025) as the software model for this study. The Inception phase, covering May to early June 2026, encompasses the Mob Elaboration activities: conducting semi-structured interviews with the Guidance and Registrar office staff at ISPSC Tagudin Campus to gather qualitative data on existing manual processes, performing requirements analysis to translate those findings into structured technical specifications, and completing the system architecture design, including the role-based database schema, role-based access control rules, and the application service blueprint. The Construction phase runs from mid-June to mid-July 2026 and follows the Mob Construction workflow described in the Software Model section, covering backend development using PHP 8.4 and Laravel 12, frontend development using Inertia.js v2 with Svelte 5 and Tailwind CSS v4, automated unit and feature testing with PHPUnit 11, and integration and security quality assurance — all produced through AI-assisted code generation under human architectural oversight. The Operations phase spans mid-July to August 2026 and includes pilot deployment of SecureCAT on a local server environment replicating ISPSC Tagudin network conditions, administration of the System Usability Scale (SUS) and Technology Acceptance Model (TAM) questionnaire through simulated user acceptance testing with proxy evaluators, and the finalization of technical documentation and the capstone manuscript. This timeline aligns with the projected milestones outlined in the project ROADMAP.md, where the Title Defense was completed in May 2026, the Proposal Defense is projected for late June 2026 following the Chapter 1 and Chapter 2 submission deadline of June 10, 2026, and the Final Defense is projected for August 2026 after system evaluation and documentation are concluded.
<!-- UPDATE:END -->

<!-- TAG: ch2_project_assignment -->
## Project Assignments

<!-- UPDATE:START -->
Five project roles are distributed among the three capstone team members. David serves as Lead Developer/Programmer and System Analyst/Designer. Jaypee serves as Project Manager and co-Technical Writer. Christine serves as Quality Assurance Tester and co-Technical Writer. The specific functions for each role are detailed in Table 1.

Table 1. Project Roles and Responsibilities
<!-- UPDATE:END -->

<!-- TAG: ch2_population_locale -->
## Population and Locale
<!-- UPDATE:START -->
This study takes place at the ISPSC Tagudin Campus, located in the municipality of Tagudin, Ilocos Sur, Philippines. ISPSC Tagudin Campus is a public higher education institution serving regional communities in Northern Luzon, offering undergraduate programs across disciplines. The study focuses on the operational workflows of the Guidance Office and the Registrar Office, which co-manage the institution's college admission testing and applicant processing cycle, accommodating approximately 500 to over 1,000 freshman applicants per academic year. The locale presents technological and infrastructure constraints — shared campus bandwidth, internet that is sometimes slow but not frequently down, and Wi-Fi primarily reserved for campus staff and faculty rather than student access — that affect administrative operations.

Because the development window of this study (May to August 2026) does not overlap with an active admission testing cycle, actual admission applicants are unavailable for participation. The evaluation will therefore employ proxy evaluators who will role-play predefined applicant and staff scenarios through the system. A purposive sampling technique (Frey, 2022) will select proxy evaluators based on specific criteria: (a) IT faculty members who can assess the system against professional software quality standards, (b) administrative staff from the Registrar and Guidance Offices who have direct familiarity with the actual admission workflows being simulated, and (c) development team members who will role-play applicant-side use cases such as registration, status tracking, and AI companion interaction. Each evaluator will be assigned use-case scenarios corresponding to the user role being simulated, consistent with the scenario-based acceptance testing methodology used in software engineering practice (Pressman & Maxim, 2020). The distribution of proxy evaluators will be presented in Table 2.

Table 2. Distribution of Proxy Evaluators by Simulated Role
<!-- UPDATE:END -->

<!-- TAG: ch2_research_instruments -->
## Research Instruments

<!-- UPDATE:START -->
This study will use two standardized instruments to evaluate the developed system: the System Usability Scale (SUS) and a Technology Acceptance Model (TAM) questionnaire. These instruments will be administered to proxy evaluators after they complete their assigned use-case scenarios in the simulated user acceptance testing. Together, the SUS and TAM provide complementary measures of perceived usability and technology acceptance, which are the two dimensions most relevant to evaluating a newly developed system that has not yet undergone full operational deployment (Brooke, 1996; Davis, 1989).

The System Usability Scale is a ten-item questionnaire that measures perceived usability on a five-point Likert scale ranging from 1 (strongly disagree) to 5 (strongly agree). The items alternate between positively and negatively worded statements to control for acquiescence bias. Originally developed by Brooke (1996) as a "quick and dirty" usability instrument for industrial evaluation, the SUS has been validated across thousands of studies with a reported Cronbach's alpha of 0.91, establishing it as a highly reliable measure of perceived usability (Bangor, Kortum, & Miller, 2008). Scoring follows the standard conversion procedure: each item contribution is converted to a 0–4 range, the converted values are summed, and the total is multiplied by 2.5 to yield a composite score from 0 to 100. A score above 68 is considered above average, indicating acceptable usability, while scores above 80 indicate excellent usability (Bangor, Kortum, & Miller, 2009; Sauro & Lewis, 2016).

The Technology Acceptance Model questionnaire measures the degree to which proxy evaluators perceive the system as useful and easy to use. Grounded in the technology acceptance framework introduced by Davis (1989), the questionnaire captures two core constructs: perceived usefulness (PU), defined as the degree to which an individual believes the system would enhance their performance, and perceived ease of use (PEOU), defined as the degree to which an individual believes the system would be free of effort. Each construct is measured through adapted items rated on a seven-point Likert scale ranging from 1 (strongly disagree) to 7 (strongly agree). The instrument items will be adapted from the original Davis (1989) scales to reflect the admission testing context of SecureCAT, covering tasks such as application intake, examination scheduling, score recording, result release, and AI-assisted counseling. The PU and PEOU constructs have been confirmed as robust predictors of behavioral intention to use across hundreds of studies in educational and administrative technology contexts (King & He, 2006).
<!-- UPDATE:END -->

<!-- TAG: ch2_data_analysis -->
## Data Analysis

<!-- UPDATE:START -->
The data analysis procedures for this study will follow the three specific research objectives, each paired with an analytical approach suited to the type of data it generates. For the first specific objective, which seeks to identify existing admission testing processes, operational gaps, and coordination requirements at ISPSC Tagudin, qualitative thematic analysis will be applied to the interview transcripts gathered from the Registrar staff and Guidance staff. The interview data will be transcribed, coded, and organized into themes corresponding to the study's research questions, including process bottlenecks, inter-office coordination failures, data integrity vulnerabilities, and infrastructure constraints. Observational notes collected during the campus visit will supplement the thematic analysis by providing contextual detail about physical workflows that interviews alone may not capture. For the second specific objective, which seeks to develop the SecureCAT system, design validation will be conducted through iterative user feedback collected during the development cycle. As each system module reaches a functional state, the research team will demonstrate the module to designated staff members at the Registrar and Guidance Offices and solicit structured feedback on usability, feature completeness, and alignment with their actual operational requirements. This iterative feedback loop will ensure that the system design remains grounded in the confirmed needs of its end users rather than assumptions, and any design revisions prompted by user feedback will be documented as part of the developmental record. For the third specific objective, which seeks to evaluate the usability and user acceptance of the developed system, descriptive statistics will be computed from the SUS and TAM results. For the SUS, each proxy evaluator's ten responses will be converted using the standard scoring procedure: odd-numbered items (positively worded) have one subtracted from the raw score, even-numbered items (negatively worded) have five subtracted from the raw score, the converted values are summed, and the total is multiplied by 2.5 to produce a composite SUS score ranging from 0 to 100. The mean SUS score across all evaluators will be computed and interpreted against the established benchmark of 68, with scores above 68 indicating acceptable usability and scores above 80 indicating excellent usability (Bangor et al., 2008; Sauro & Lewis, 2016). The standard deviation will be reported to indicate score variability across evaluators.

For the TAM questionnaire, the mean score for each construct — perceived usefulness (PU) and perceived ease of use (PEOU) — will be computed by averaging the item responses within each construct. Mean scores above the midpoint of the seven-point scale (i.e., above 4.0) will indicate positive perception, consistent with the interpretation guidelines used in technology acceptance research (Davis, 1989; King & He, 2006). The standard deviation for each construct will also be reported. Table 3 presents the SUS score interpretation bands and the TAM construct descriptions used for data analysis.

Table 3. SUS Score Interpretation Bands and TAM Construct Descriptions
<!-- UPDATE:END -->

<!-- TAG: references_list -->
## REFERENCES

<!-- UPDATE:START -->
Addla, N. (2026). AI-Driven Development Lifecycle (AI-DLC): Reimagining software engineering for the AI era. International Journal of Artificial Intelligence, Data Science, and Machine Learning, 7(1), 266–270. https://doi.org/10.63282/3050-9262.IJAIDSML-V7I1P145

Ballesteros, B. N. V., Habon, P. M., Lopez, J. D. O., & Tan, L. D. (2025). FreshGroup: Clustering first-year student profiles using unsupervised machine learning [Unpublished capstone project]. Ilocos Sur Polytechnic State College, Tagudin Campus.

Bangor, A., Kortum, P. T., & Miller, J. T. (2008). An empirical evaluation of the System Usability Scale. International Journal of Human-Computer Interaction, 24(6), 574–594. https://doi.org/10.1080/10447310802205776

Bangor, A., Kortum, P., & Miller, J. (2009). Determining what individual SUS scores mean: Adding an adjective rating scale. Journal of Usability Studies, 4(3), 114–123.

Brooke, J. (1996). SUS: A "quick and dirty" usability scale. In P. W. Jordan, B. Thomas, B. A. Weerdmeester, & I. L. McClelland (Eds.), Usability evaluation in industry (pp. 189–194). Taylor & Francis.

Caintic, N. A., & Lahaylahay, J. P. (2024). Digital transformation readiness of state universities and colleges in the Philippines: A comparative analysis. Philippine Journal of Higher Education, 12(1), 45–62. https://doi.org/10.5281/pjhe.2024.0012

Chen, X., & Liu, Y. (2024). Digital transformation in university admission systems: A systematic review of cloud-based platforms. Journal of Educational Technology Systems, 52(3), 412–438. https://doi.org/10.1177/00472395231221435

Chhor, S., Chea, S., & Srun, S. (2024). Factors influencing student choice in higher education: Evidence from a developing country. Higher Education Quarterly, 78(2), 189–205. https://doi.org/10.1111/hequ.12487

Comprendio, R. L., & Canlas, R. C. (2025). Post-pandemic digitalization in Philippine higher education institutions: Gaps, workarounds, and sustainability. Education and Information Technologies, 30(2), 1123–1141. https://doi.org/10.1007/s10639-024-03987-1

Department of Information and Communications Technology. (2023). National broadband plan 2023: Expanding connectivity for digital government services. DICT Publications. https://dict.gov.ph/nbp2023

Davis, F. D. (1989). Perceived usefulness, perceived ease of use, and user acceptance of information technology. MIS Quarterly, 13(3), 319–340. https://doi.org/10.2307/249008

Frey, B. B. (Ed.). (2022). The SAGE encyclopedia of research design (2nd ed.). SAGE Publications, Inc. https://doi.org/10.4135/9781071812082

King, W. R., & He, J. (2006). A meta-analysis of the technology acceptance model. Information & Management, 43(6), 740–755. https://doi.org/10.1016/j.im.2006.05.007

Kumar, R., & Singh, A. (2025). Deep learning applications in automated examination scoring and cheating detection. International Journal of Artificial Intelligence in Education, 35(1), 89–118. https://doi.org/10.1007/s40593-024-00388-w

Mapalad, M. J. L., Cabrera, A. G., & Javier, R. E. (2025). Factors affecting the enrollment decisions of freshmen students in public higher education institutions in the Philippines. Asia Pacific Journal of Academic Research in Social Sciences, 10(1), 34–45.

Malaluan, M. B., & Wang, H. (2023). The impact of free tertiary education policy on enrollment and institutional capacity in Philippine state universities. Journal of Asian Public Policy, 16(3), 287–304. https://doi.org/10.1080/17516234.2023.2214567

Malaya, A. R. N., Munar, E. A., & Cuison, F. P. (2022). Information management system for research of Don Mariano Marcos Memorial State University–South La Union Campus. Indonesian Journal of Electrical Engineering and Computer Science, 28(3), 1668–1675. https://doi.org/10.11591/ijeecs.v28.i3.pp1668-1675

Müller, K., & Hoffmann, B. (2023). AI-powered chatbots in university admissions: Reducing administrative workload through conversational agents. International Journal of Educational Technology in Higher Education, 20(1), Article 42. https://doi.org/10.1186/s41239-023-00411-z

National Privacy Commission. (2024). Data privacy compliance guide for educational institutions (NPC Advisory No. 2024-03). Republic of the Philippines. https://www.privacy.gov.ph/advisory-2024-03

Okafor, N., & Tanaka, Y. (2024). Data privacy compliance in global educational technology: A comparative analysis of GDPR and emerging frameworks. Education and Information Technologies, 29(4), 5187–5212. https://doi.org/10.1007/s10639-023-12245-y

Olipas, C. N. P. (2023). The design and development of student information and violation management system (SIVMS) for a higher educational institution (Zenodo Record 8024683). https://doi.org/10.5281/zenodo.8024683

Park, S., Kim, J., & Lee, H. (2023). Automated assessment technologies in higher education: From OMR to AI-driven evaluation. Computers & Education, 198, Article 104756. https://doi.org/10.1016/j.compedu.2023.104756

Pressman, R. S., & Maxim, B. R. (2020). Software engineering: A practitioner's approach (9th ed.). McGraw-Hill Education.

Raja, S. P. (2025, August 12). AI-driven development life cycle: Reimagining software engineering. AWS DevOps Blog. https://aws.amazon.com/blogs/devops/ai-driven-development-life-cycle/

Sauro, J., & Lewis, J. R. (2016). Quantifying the user experience: Practical statistics for user research (2nd ed.). Morgan Kaufmann.

Williams, T., & Garcia, M. (2024). Role-based access control in educational management systems: Security architectures and compliance frameworks. Information Systems Security, 33(2), 201–225. https://doi.org/10.1080/1065898X.2024.2312015

Yukee, A. J. M., Bonifacio, C. L., Salvador, J. M. A., & Macabitas, A. P. (2025). A clustering student ICAT score using machine learning algorithm [Unpublished capstone project]. Ilocos Sur Polytechnic State College, Tagudin Campus.

APPENDICES
<!-- UPDATE:END -->

<!-- TAG: appendix_a_use_case -->
## APPENDIX A

SCAN OF SIGNED LETTER TO CONDUCT

<!-- TAG: appendix_b_letter_conduct -->
## APPENDIX B

USE CASE DIAGRAM
