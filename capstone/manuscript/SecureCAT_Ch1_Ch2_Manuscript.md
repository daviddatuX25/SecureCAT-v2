<!-- META: docx-sync-version="2026-06-15T07:52:16.706211+00:00" docx-sha256="23c139e93650ce2713ed826df02b1182e8959422a38aa57d34500327ac462738" -->



<!-- TAG: ch1_introduction -->
# Chapter 1

<!-- UPDATE:START -->
# INTRODUCTION
<!-- UPDATE:END -->

<!-- TAG: ch1_bg_of_the_study -->
## Background of the Study

<!-- UPDATE:START -->
Education is the foundation of societal progress, shaping personal careers, defining institutional missions, and driving collective development. Throughout the history of formal instruction, examinations have accompanied learning, acting as both a gatekeeper of academic progress and a tool to guarantee instructional quality, maintain accountability, and verify learning outcomes. While the theoretical design and psychometric validity of examinations receive rigorous academic scrutiny, the administration and logistics of testing are frequently neglected. Despite proven assessment methodologies, item calibration and testing tools remain underused in administrative workflows, leaving campuses to manage high-stakes testing through manual routines. This management gap is further strained by rising educational demands, characterized by surging applicant volumes, shifting career fields, and heightened institutional standards. Modern admission offices cannot function efficiently under manual administrative procedures that fail to handle seasonal intake pressures. Because the admission phase is the initial contact between an applicant and an institution, the speed of this process directly influences student enrollment decisions. Applicants frequently apply to multiple regional colleges, and administrative delays in releasing entrance examination scores lead candidates to choose competing institutions that respond faster. A campus's ability to coordinate and evaluate admissions reflects its administrative capability, highlighting the need for an integrated college admission testing system like SecureCAT. For institutions like the Ilocos Sur Polytechnic State College (ISPSC) Tagudin Campus, automating admissions secures scores and coordinates the work of the Guidance and Registrar Offices under a single secure platform. These digital enhancements do not merely improve administrative convenience; they demonstrate how operational bottlenecks require targeted technical solutions to institutional problems.

Higher education admissions have shifted toward digital systems since the early 2020s, driven by cloud computing, automated assessment, and data privacy regulations. Universities across North America, Europe, and East Asia have widely adopted unified admission platforms that integrate application intake, examination scheduling, automated scoring, and result dissemination into single systems (Chen & Liu, 2024). These platforms often include optical mark recognition (OMR) systems to scan bubble sheets and generate scores, eliminating manual comparison (Park et al., 2023). Beyond traditional OMR, computer vision and deep learning support assessment systems that can evaluate handwritten responses, detect cheating, and generate adaptive test items (Kumar & Singh, 2025). Role-based access control (RBAC) has also become standard, ensuring staff access to applicant records is governed by institutional roles rather than broad privileges (Williams & Garcia, 2024). To assist applicants, institutions deploy retrieval-augmented generation (RAG) platforms that retrieve policies, catalog details, and admission rules from local databases to answer questions without direct counselor involvement (Müller & Hoffmann, 2023). While these systems shorten score release timelines and improve applicant satisfaction, processing sensitive student records through cloud-based AI services introduces security risks on third-party servers, prompting a shift toward local data handling architectures (Okafor & Tanaka, 2024).

The Philippine higher education sector, overseen by the Commission on Higher Education (CHED), is modernizing under national digitalization mandates. The enactment of Republic Act No. 10931, the Universal Access to Quality Tertiary Education Act of 2017, increased SUC enrollment, creating administrative bottlenecks across public campuses (Malaluan & Wang, 2023). CHED has promoted digital systems to optimize enrollment management and student records processing (Caintic & Lahaylahay, 2024), while the Department of Information and Communications Technology (DICT) has championed the E-Government Act of 2022 and the National Broadband Plan to expand digital services in regional settings (Department of Information and Communications Technology, 2023). Since the pandemic, academic institutions have transitioned from temporary workarounds to permanent web platforms (Comprendio & Canlas, 2025). These digital systems must comply with Republic Act No. 10173, the Data Privacy Act of 2012, to protect student personally identifiable information and examination records against security breaches (National Privacy Commission, 2024). However, a digital readiness gap persists between metropolitan universities and regional state colleges; urban institutions use mature cloud setups, while provincial campuses have limited budgets, shared bandwidth, and restricted network access (Caintic & Lahaylahay, 2024; Comprendio & Canlas, 2025; Department of Information and Communications Technology, 2023).

The operational context at the ISPSC Tagudin Campus reflects these challenges during peak enrollment cycles (Caintic & Lahaylahay, 2024). Serving freshman cohorts of 700 to 1,000 applicants under Republic Act No. 10931, the campus relies on a paper-based workflow that strains staff capacity (Malaluan & Wang, 2023). Applicant intake at the Registrar Office is conducted through paper forms, with physical documents filed in locked cabinets without digital backups. Admission slips are produced manually using word processor templates, and result release requires an in-person consultation at the Guidance Office. This is initiated by schedule announcements posted on the office's Facebook page, forcing applicants to visit the campus solely to retrieve results. The institution's primary applicant notification channel (a general-purpose departmental Facebook page) is not dedicated to admission communications, and updates are issued periodically rather than systematically. Scheduling between the Registrar and Guidance Offices is arranged via verbal communication and text messages, and physical records lack the data privacy protocols mandated under Republic Act No. 10173 (National Privacy Commission, 2024). The Guidance Office scores all examinations using a manual stencil method, which takes two to three days for fifty applicants. This results in cumulative release delays of up to two weeks from the final examination batch, and over one month for applicants processed in the earliest batches of the cycle. This latency is worsened during enrollment because key programs operate under strict quota constraints determined by classroom facilities and faculty availability. In the admissions cycle for the academic year 2026-2027, Program Heads and the Registrar adjusted these slots dynamically, but the Guidance Office lacked a centralized mechanism to monitor available capacities. Consequently, counselors have no access to real-time slot data during consultations and must pause advising sessions to contact each Program Head. This process is further complicated by post-release slot adjustments made at Program Head discretion, introducing inconsistency and applicant confusion. These interconnected gaps, which became visible during pandemic disruptions, show the need for a unified, secure, and offline-resilient system on this campus (Comprendio & Canlas, 2025; Department of Information and Communications Technology, 2023).

While global institutions have successfully deployed unified admission systems (Chen & Liu, 2024; Müller & Hoffmann, 2023), and national mandates demand data privacy and digitalization (National Privacy Commission, 2024; Department of Information and Communications Technology, 2023), regional campuses like ISPSC Tagudin still operate with disconnected platforms that leave operational and security gaps (Caintic & Lahaylahay, 2024; Comprendio & Canlas, 2025). Studies in computerized admissions often focus on static record management or cloud-hosted student portals, but they fail to address campuses that lack persistent connectivity and require offline resilience for high-volume intake (Chen & Liu, 2024). In addition, while cloud-based AI tools can automate applicant support, they introduce security risks regarding the exposure of sensitive student records and confidential scoring tables on third-party servers (Okafor & Tanaka, 2024). This leaves a research gap: the lack of a secure, unified framework that coordinates inter-office workflows, automates scoring, and embeds local intelligence directly within the campus database. Addressing this gap requires data-driven decision-making systems that can translate raw applicant metrics into practical academic guidance. In this context, machine learning techniques offer a framework for extracting insights from applicant profiles, enabling institutions to generate course recommendations based on aptitude profiles and use applicant profiling to optimize placement decisions (Kumar & Singh, 2025). The relevance and validity of this approach have been shown through local research at ISPSC, where K-Means clustering algorithms were applied to historical student datasets. Specifically, Yukee et al. (2025) validated a K=4 clustering configuration on entrance examination results to establish distinct aptitude-based student profiles, while Ballesteros et al. (2025) analyzed the relationship between student academic performance and socioeconomic indicators to guide course routing. However, because these studies were conducted as post-hoc analyses on static databases, their machine learning models remained isolated from the active enrollment workflow, operating retrospectively rather than during the live consultation. Consequently, there remains a research gap in designing a unified architecture that executes live K-Means clustering to classify applicant aptitude profiles at the moment of consultation, while extending counselor reach through a secure, retrieval-augmented companion that uses local institutional policies without risking data sovereignty. This profiling capability must be extensible, allowing the intake of supplementary academic parameters such as senior high school grades in mathematics, English, and science to expand basic entrance test scores and the General Weighted Average, permitting the Guidance Office to refine recommendations as institutional placement criteria evolve.

The research methodology and system design were informed by literature on computerized admissions, secure access controls, and local machine learning integration. The selection of this topic was driven by observations at ISPSC Tagudin Campus, where the researchers documented paper-based test routing, absent audit trails, scoring errors from manual stencil checks, and fragmented inter-office coordination during peak periods. These failures demonstrated that the existing disconnected tools could not sustain rising applicant volumes. This study proposes SecureCAT, an offline-first college admission testing platform that coordinates registrar and guidance workflows by integrating computer-vision OMR for automated scoring, a local K-Means clustering microservice for live applicant profiling at the point of consultation, and a RAG-powered AI Companion for pre-examination and post-examination guidance. By combining administrative data with local academic intelligence, this platform aligns with SDG 4 on Quality Education and SDG 16 on Peace, Justice, and Strong Institutions.
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
This study aims to develop SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin Campus, a web-based platform that centralizes the admission testing pipeline (from registration through automated scoring and AI-assisted counseling) while enforcing zero-trust data governance under the infrastructure constraints of the campus.

More specifically, this study seeks to accomplish the following:

1. To analyze the existing college admission testing processes, operational workflows, and process gaps at ISPSC Tagudin Campus through descriptive inquiry.

2. To determine the system requirements of SecureCAT in terms of functional requirements and system features needed to address the identified process gaps.

3. To evaluate the acceptance of SecureCAT using the Technology Acceptance Model (TAM) in terms of perceived usefulness, perceived ease of use, attitude toward use, and behavioral intention to use.

4. To determine whether there is a significant difference between the existing manual admission testing process and SecureCAT in terms of perceived usefulness and perceived ease of use.

Null Hypotheses

H₀₁: There is no significant difference in the perceived usefulness between the existing manual admission testing process and SecureCAT.

H₀₂: There is no significant difference in the perceived ease of use between the existing manual admission testing process and SecureCAT.

Test: Paired samples t-test | α = .05 | Effect size: Cohen's d
<!-- UPDATE:END -->

<!-- TAG: ch1_scope_delimitations -->
## Scope and Limitation of the Study

<!-- UPDATE:START -->
This study covers the design, development, and evaluation of SecureCAT, a role-based college admission testing system for the Guidance and Registrar Offices at ISPSC Tagudin Campus, Tagudin, Ilocos Sur, Philippines. The system accommodates seven user types: applicants, proctors, guidance counselors, registrar staff, registrar administrators, super administrators, and program heads. One person may hold more than one role, and assignments can be updated as needs change. The system handles end-to-end admission tasks including web-based application intake, time-limited account activation, real-time status tracking, admission slip and result document generation, examination scheduling with proctor assignment, CSV score import, consultation recording, and staff-applicant notifications. Audit logs capture all system events, and database access is isolated by role. Technical capabilities include computer-vision OMR scoring with HMAC-SHA256 tamper protection, offline PWA proctoring with IndexedDB sync, a RAG-powered AI companion for applicant guidance, an AI scheduling assistant with administrator approval gates, and a live K-Means course triage module with four clusters adapted from Yukee et al. (2025) and Ballesteros et al. (2025) that incorporates dynamic program capacity limits and extensible applicant profiling using senior high school grades in mathematics, English, and science. The study runs from June to September 2026.

SecureCAT addresses only the admission testing process; enrollment, tuition, academic records, and learning management are excluded. The system is tested and evaluated only at ISPSC Tagudin Campus. The OMR module requires a camera or scanner producing clear answer sheet images with pre-printed QR codes for applicant identification; these QR codes represent a new infrastructure component introduced by SecureCAT, as current answer sheets link applicant identity to examination records via NOA numbers only. Handwritten name matching is not used. The offline PWA requires one initial connection to register the service worker. Examination questions are authored by faculty, and scoring conversion tables are determined and kept confidential by the Guidance Office; the system blocks conversion data access for unauthorized users and exports. Course capacity limits are updated within the database by program heads and administrators based on coordination with academic departments, but automated predictive slot balancing or cross-campus room allocation is excluded. The system follows Republic Act No. 10173 through role-based access control and immutable audit logging, though formal National Privacy Commission auditing falls outside this study.
<!-- UPDATE:END -->

<!-- TAG: ch1_significance -->
## Importance of the Study

<!-- UPDATE:START -->
The Community. The implementation of SecureCAT will benefit residents of Tagudin and surrounding municipalities who comprise the primary applicant pool of ISPSC Tagudin Campus. By unifying the admission testing process into a single role-based digital platform, the system will reduce the economic and time burden on applicants and their families. Applicants from distant municipalities such as Alilem, as observed by guidance staff, face travel times exceeding two hours each way and must make multiple visits to submit requirements, check schedules, and claim results. The digitized system will minimize queue bottlenecks and reduce repeated campus visits, making admissions more accessible.

The Registrar Office. The Registrar Office will benefit from automated application intake, admission slip generation, and real-time applicant status tracking that replace manual Microsoft Word encoding and paper-based filing. The integrated scheduling module will formalize the handoff between offices through a shared digital calendar, eliminating verbal and text-based coordination. Role-based data isolation will ensure that registrar staff access only the records pertinent to their function, assisting the office in meeting Republic Act No. 10173 compliance requirements.

The Guidance Office. The Guidance Office will benefit from automated OMR scoring that compresses the two-to-three-day stencil-method grading process into minutes, freeing counselors for student-facing duties. System-wide audit trails will make every score access and modification traceable to an authenticated user. The course triage module will present counselors with applicant aptitude profiles, extensible academic metrics, and live program quota availability during each consultation, reducing the cognitive burden of manual cross-referencing. The RAG-powered AI companion will handle routine pre-examination and post-examination inquiries outside office hours, extending counselor reach without replacing professional judgment.

The Respondents. Registrar staff and guidance counselors will benefit from operational relief. Staff will experience reduced cognitive, physical, and temporal workloads associated with manual scheduling, stencil scoring, record filing, and quota tracking. Applicants will benefit from an intuitive registration interface, automated queue scheduling, and a 24/7 AI companion that supplements the guidance office's Facebook page with structured, validated self-service guidance.

The College or Department. The BSIT Department of ISPSC Tagudin Campus will benefit from a reference implementation that demonstrates how cryptographic verification (HMAC-SHA256), offline PWA synchronization, and computer-vision OMR translate abstract IT theories into institutional solutions using modern frameworks such as Laravel 12 and Svelte 5.

The Students. Students of ISPSC Tagudin Campus will benefit from an equitable testing environment where HMAC-SHA256 verification ensures admission decisions rest on unaltered, verified merit. Automated room scheduling will eliminate manual overlaps, and reduced administrative friction will let applicants focus on examination preparation.

The Researchers. The researchers will gain hands-on experience in the full software development lifecycle under the RAD model with AI-augmented development, developing competencies in agentic workflows, API security, and offline synchronization, while building academic research skills through TAM instrument administration and descriptive developmental analysis.

Future Researchers. This study will provide a reusable blueprint for institutional web applications in resource-constrained educational environments. The AI-augmented RAD methodology documentation, zero-trust architecture, and offline synchronization strategies will function as reference material. The course triage module will offer a model for translating exploratory K-Means clustering research into operational decision-support tools through integration of school-owned algorithm source code (Yukee et al., 2025; Ballesteros et al., 2025) and extensible parameter configuration.
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
This study employs a descriptive developmental research design, a hybrid methodology that combines systematic descriptive inquiry with iterative system construction and empirical evaluation (Frey, 2022). This design is widely applied in information systems research in Philippine higher education institutions, where the primary objective is to document local administrative challenges and subsequently construct and validate a web-based platform to address them (Malaya et al., 2022; Olipas, 2023).

The descriptive component documents the existing college admission testing workflows, inter-office coordination patterns, data integrity vulnerabilities, and infrastructure constraints at ISPSC Tagudin Campus, addressing Specific Objective 1. Data are gathered through semi-structured questionnaires administered to Guidance Office and Registrar Office personnel, supplemented by researcher observation during the field visit. Thematic analysis is applied to the qualitative responses, organizing findings into themes that directly inform the system design phase.

The developmental component includes the iterative design, construction, and validation of SecureCAT, addressing Specific Objectives 2, 3, and 4. System requirements are derived from the descriptive findings, ensuring that the developed platform responds to confirmed operational gaps rather than assumed needs. Evaluation proceeds through simulated user acceptance testing administered to purposively selected proxy evaluators, with the Technology Acceptance Model (TAM) questionnaire as the primary evaluation instrument.

The study is further informed by the principles of Design Science Research (DSR), a recognized framework in information systems research that positions the development of an artifact (in this case, SecureCAT) as the primary research contribution rather than a by-product of theoretical inquiry (Hevner et al., 2004). Under DSR, the artifact’s value is shown through its ability to solve an identified organizational problem, evaluated against explicit design objectives and verified through testing (Hevner et al., 2004). The seven DSR guidelines articulated by Hevner et al. (2004) (design as artifact, problem relevance, design evaluation, research contributions, research rigor, design as a search process, and communication of research) are each addressed within the descriptive developmental framework of this study, positioning SecureCAT as both an institutional solution and a generalizable reference implementation for Philippine SUC admission systems.
<!-- UPDATE:END -->

<!-- TAG: ch2_software_model -->
## Software Development Model

<!-- UPDATE:START -->
The development of SecureCAT follows the Rapid Application Development (RAD) model, a software engineering framework designed for small, time-constrained development teams operating under a well-defined functional scope (Martin, 1991; Pressman & Maxim, 2020). The RAD model emphasizes rapid iterative prototyping, user involvement throughout the construction cycle, and compressed delivery timelines that prioritize functional output over exhaustive upfront design (Pressman & Maxim, 2020). These characteristics make RAD appropriate for the SecureCAT development context: a three-member student team, a four-month delivery window (June to September 2026), and functional requirements grounded in observed operational workflows at ISPSC Tagudin Campus.

The RAD model organizes development into four sequential phases: Requirements Planning, User Design, Rapid Construction, and Cutover. Each phase maps directly to the study’s objectives and timeline, as described in the sections that follow.

Figure 2. Rapid Application Development (RAD) Model

AI-Augmented Development: A Novel Methodological Integration

Within the conventional RAD framework, this study integrates AI-augmented code generation and architectural collaboration, consistent with the emerging AI-Driven Development Lifecycle (AIDLC) documented by Addla (2026) and Raja (2025). The AIDLC framework reconceptualizes software engineering by positioning AI agents as active collaborators (not passive autocomplete tools) in the design, construction, and verification of software systems (Addla, 2026). Under this model, the human developer acts as architect, decision-maker, and quality gate, while AI coding assistants generate, review, and refine code artifacts under human oversight (Raja, 2025).

The integration of this practice into SecureCAT’s RAD Construction phase is a methodological contribution in its own right. SecureCAT has multiple interconnected system layers: a Laravel 12 backend with HMAC-SHA256 cryptographic signing, a computer-vision OMR processing pipeline, a FastAPI-based K-Means microservice, a Retrieval-Augmented Generation AI companion, an offline-resilient Progressive Web Application with IndexedDB synchronization, and a write-only immutable audit subsystem. Delivering this breadth of architecture within a single capstone cycle by a three-person student team would not have been feasible under conventional human-only development workflows. The selective use of AI coding assistants (Claude Code, Antigravity, and Hermes Agent) during the Construction phase will resolve this scalability constraint, enabling the team to operate at a pace and architectural scope that traditionally requires a dedicated multi-person engineering group.

This approach does not substitute human judgment. Every AI-generated artifact (database migration, service class, component, or test case) will be reviewed, validated, and integrated by the lead developer before being committed to the codebase. Automated testing via PHPUnit 11 provides a continuous correctness gate, ensuring that AI generation velocity does not compromise behavioral accuracy or security integrity. The result is a development model in which human architectural authority and AI generative capacity operate in a structured partnership. This workflow pattern is formalized by the AIDLC framework and will be demonstrated by this study in applied institutional practice.

RAD Phases Applied to SecureCAT

Phase 1: Requirements Planning (June 2026 | Corresponds to Specific Objective 1)

The Requirements Planning phase encompasses the descriptive inquiry component of the study. Semi-structured questionnaires are administered to Guidance Office personnel (n=2), Registrar Office staff (n=3), and academic Program Heads (n=3), supplemented by researcher observation at ISPSC Tagudin Campus. Findings are organized thematically to document existing process gaps: the absence of bulk result processing, the lack of real-time program slot visibility for counselors, the manual stencil scoring bottleneck, the fragmented verbal inter-office coordination, and the high-friction Facebook-and-in-person result release pipeline. These findings shape the functional scope of SecureCAT, ensuring that each system requirement traces to a verified operational need rather than a developer assumption.

AI assistants (Claude Code, Antigravity, and Hermes Agent) are employed during this phase for Mob Elaboration, which is the structured translation of questionnaire findings and observational data into formal technical specifications, database schema drafts, and RBAC rule definitions. The human researcher validates all AI-generated specifications against confirmed field findings before proceeding to design.

Phase 2: User Design (Late June to early July 2026 | Corresponds to Specific Objective 2)

The User Design phase translates the verified requirements into a system architecture. Activities include the design of the role-based database schema (seven user roles with isolated data access layers), the application service architecture (Laravel backend, Inertia.js bridge, Svelte 5 frontend, FastAPI microservice), the RBAC authorization matrix, and interface mockups for each role’s portal. AI assistants support schema normalization reviews, architectural trade-off analysis, and interface wireframe generation, with the System Analyst making all final architectural decisions.

The deliverable of this phase (the functional requirements table and system features table) constitutes the output of Specific Objective 2 and forms the foundation of Chapter 3’s system design presentation.

Phase 3: Rapid Construction (July to August 2026 | Implements Specific Objective 2)

The Rapid Construction phase is the development core of the study, executed through the AI-augmented development workflow described above. The lead developer, acting as architect and quality reviewer, directs AI coding agents to generate backend services (Laravel controllers, Eloquent models, service classes, API routes), frontend components (Svelte 5 reactive interfaces with Tailwind CSS v4), automated test suites (PHPUnit 11 unit and feature tests), and specialized subsystem code (HMAC-SHA256 signing logic, OMR image processing pipeline, K-Means microservice integration layer, IndexedDB offline sync middleware, RAG retrieval chain).

Each generated artifact undergoes human review against the architectural specifications established in Phase 2. Security-sensitive modules (score signing, audit logging, and RBAC enforcement) receive heightened scrutiny, with tests authored before implementation to enforce behavioral correctness before integration. Defects identified through automated testing are corrected within the same development iteration, preventing the accumulation of technical debt across the construction cycle.

Phase 4: Cutover (September 2026 | Corresponds to Specific Objectives 3 and 4)

The Cutover phase encompasses pilot deployment, simulated user acceptance testing, and evaluation. SecureCAT is deployed on a local server environment replicating the network conditions of the ISPSC Tagudin campus, with database seeders populating the system with simulated applicant records and test schedules. Proxy evaluators complete their assigned use-case scenarios across all system roles before administering the TAM questionnaire in a paired pre-test/post-test protocol. Descriptive statistics and paired t-tests are computed from the TAM data to address Specific Objectives 3 and 4, and technical documentation and the capstone manuscript are finalized for submission and final defense.

Table 4. Phase-to-Objective Mapping Summary
<!-- UPDATE:END -->

<!-- TAG: ch2_project_plan -->
## Project Plan

<!-- UPDATE:START -->
Figure 3. Project Gantt Chart of SecureCAT

The project timeline spans four months, from June 2026 to September 2026, and is organized into the four phases of the RAD model. The Requirements Planning phase (June 2026) encompasses the descriptive inquiry: administering questionnaires to the Guidance and Registrar staff at ISPSC Tagudin Campus to gather qualitative data on existing manual processes, performing requirements analysis to create technical specifications, and designing the system architecture. The User Design phase runs from late June to early July 2026, producing the database schema and user interface mockups. The Rapid Construction phase runs from July to August 2026 using the AI-augmented development workflow, covering backend development with PHP 8.4 and Laravel 12, frontend development with Svelte 5 and Tailwind CSS v4, automated testing with PHPUnit 11, and quality assurance. The Cutover phase spans September 2026 and includes pilot deployment, Technology Acceptance Model (TAM) evaluation through simulated testing with proxy evaluators, and finalization of the capstone manuscript. This timeline aligns with project milestones: the Title Defense was completed in June 2026, the Proposal Defense is projected for late July 2026, and the Final Defense is scheduled for September 2026.
<!-- UPDATE:END -->

<!-- TAG: ch2_project_assignment -->
## Project Assignments

<!-- UPDATE:START -->
Five project roles are distributed among the three capstone team members. David Datu N. Sarmiento is the Lead Developer/Programmer and System Analyst/Designer. Jaypee G. Pagaduan is the Project Manager and co-Technical Writer. Christine M. Lopez is the Quality Assurance Tester and co-Technical Writer. The functions for each role are detailed in Table 1.

Table 1. Project Roles and Responsibilities
<!-- UPDATE:END -->

<!-- TAG: ch2_population_locale -->
## Population and Locale
<!-- UPDATE:START -->
This study takes place at the ISPSC Tagudin Campus, located in the municipality of Tagudin, Ilocos Sur, Philippines. ISPSC Tagudin Campus is a public higher education institution serving regional communities in Northern Luzon, offering undergraduate programs across disciplines. The study focuses on the operational workflows of the Guidance Office, the Registrar Office, and the academic departments through their Program Heads, which co-manage the institution's college admission testing and applicant processing cycle, accommodating approximately 500 to over 1,000 freshman applicants per academic year. The locale presents technological and infrastructure constraints, such as shared campus bandwidth, slow internet connectivity, and restricted student Wi-Fi access, which affect administrative operations.

Because the evaluation of this study (scheduled for September 2026) does not overlap with an active admission testing cycle, actual incoming admission applicants are unavailable for participation during the system testing phase. The evaluation will therefore employ proxy evaluators who will role-play predefined applicant and staff scenarios through the system. A purposive sampling technique (Frey, 2022) will select proxy evaluators based on specific criteria: (a) administrative staff from the Guidance Office (n=2, specifically the Guidance Counselor and Administrative Staff who manage examinations and result release) who are familiar with the admission workflows, (b) administrative staff from the Registrar Office (n=3) who handle applicant intake, document management, and scheduling coordination, (c) Program Heads (n=3) who manage course quota allocation and academic profiling alignment for their respective departments, and (d) first-year students (n=30) from various academic programs (e.g., BSIT, BSBA, BSED) who recently went through the manual college admission testing process and can recall their experience to evaluate the system from the applicant's perspective. During the simulated sessions, the research team coordinates the testing and assists proxy evaluators in navigating the protocols, while independent proxy evaluators record all evaluations. The distribution of proxy evaluators is in Table 2.

Table 2. Distribution of Proxy Evaluators by Simulated Role

| Simulated Role | Target Population / Office | Sample Size (n) | Selection Criteria / Purpose |
| :--- | :--- | :---: | :--- |
| Guidance Personnel | Guidance Office Staff | 2 | Primary operators of examination scoring, scheduling, and student counseling workflows |
| Registrar Staff | Registrar Office Staff | 3 | Manage applicant registration, intake documentation, and slot allocation coordination |
| Program Heads | Academic Departments | 3 | Manage course capacity quotas and evaluate student placement profiles |
| Applicants | First-Year Students | 30 | Recent participants in the manual admission testing cycle with direct recall of the manual process |
| **Total** | | **38** | |
<!-- UPDATE:END -->

<!-- TAG: ch2_research_instruments -->
## Research Instruments

<!-- UPDATE:START -->
This study uses the Technology Acceptance Model (TAM) questionnaire as its primary evaluation instrument. Grounded in the technology acceptance framework introduced by Davis (1989), the TAM measures the degree to which users perceive a system as useful and easy to adopt. The original model defines two core constructs: perceived usefulness (PU), which is the degree to which an individual believes the system would enhance performance, and perceived ease of use (PEOU), which is the degree to which an individual believes the system would be free of effort. These two constructs have been confirmed as predictors of behavioral intention to use across studies in diverse technology contexts (King & He, 2006), and the model is applied in recent educational technology research within Philippine state universities and colleges (Quiban, 2025) and institutional online service evaluation (Ampuan & Deleña, 2024). This study extends the original instrument to include two additional constructs: attitude toward use (ATU) and behavioral intention to use (BI) (Venkatesh & Davis, 2000; King & He, 2006).

The TAM items are adapted from Davis (1989) to reflect the admission testing context of SecureCAT, covering application intake, examination scheduling, score recording, result release, and AI-assisted counseling. All constructs are measured on a seven-point Likert scale ranging from 1 (strongly disagree) to 7 (strongly agree). The instrument contains 14 items distributed across four constructs: perceived usefulness (4 items), perceived ease of use (4 items), attitude toward use (3 items), and behavioral intention to use (3 items).

The instrument is administered twice per proxy evaluator: once before interacting with SecureCAT (representing perceptions of the manual process) and once after completing the use-case scenarios. This paired design enables direct comparison of perceived usefulness and perceived ease of use between conditions, supporting the hypothesis tests in Specific Objective 4. The instrument items and adapted scales are in the appendix.
<!-- UPDATE:END -->

<!-- TAG: ch2_data_analysis -->
## Data Analysis

<!-- UPDATE:START -->
The data analysis procedures for this study are organized according to the four specific research objectives, each paired with an analytical approach suited to the type of data it generates.

To analyze the existing college admission testing processes and gaps (Specific Objective 1), qualitative thematic analysis is applied to questionnaire responses from Registrar and Guidance personnel. The responses are transcribed, coded, and organized into themes, including process bottlenecks, inter-office coordination failures, data integrity vulnerabilities, and infrastructure constraints. Observational notes collected during the campus field visit supplement the thematic analysis by providing contextual detail about physical workflows.

To determine system requirements (Specific Objective 2), design validation is conducted through iterative feedback during the development cycle. The research team demonstrates modules to staff at the Registrar and Guidance Offices to solicit feedback on usability, features, and alignment with operational requirements. This feedback loop ensures the system design remains grounded in user needs, and any revisions prompted by feedback are documented as part of the developmental record.

To evaluate acceptance using the TAM (Specific Objective 3), the mean score for each construct (perceived usefulness, perceived ease of use, attitude toward use, and behavioral intention to use) is computed by averaging the item responses. Mean scores above the midpoint of the seven-point scale (> 4.0) indicate positive technology acceptance. Standard deviation per construct is reported, and results are interpreted against TAM research benchmarks (Davis, 1989; King & He, 2006). Table 3 presents the TAM evaluation instrument summary.

To determine if there is a significant difference between the manual process and SecureCAT (Specific Objective 4), a paired samples t-test is conducted on the pre-test and post-test TAM scores for perceived usefulness (PU) and perceived ease of use (PEOU). The significance threshold is α = .05. If the null hypothesis is rejected, Cohen’s d is computed to quantify the effect size of the difference, where d = 0.2 is small, d = 0.5 is medium, and d = 0.8 is large (Cohen, 1988). The decision to reject or fail to reject each null hypothesis (H₀₁ and H₀₂) is stated explicitly.

Table 3. TAM Evaluation Instrument Summary
<!-- UPDATE:END -->

<!-- TAG: references_list -->
## REFERENCES

<!-- UPDATE:START -->
Addla, N. (2026). AI-Driven Development Lifecycle (AI-DLC): Reimagining software engineering for the AI era. International Journal of Artificial Intelligence, Data Science, and Machine Learning, 7(1), 266–270. https://doi.org/10.63282/3050-9262.IJAIDSML-V7I1P145

Ampuan, A. D., & Deleña, R. M. (2024). A quantitative evaluation of online appointment system at Mindanao State University–Main Campus: Employing the System Usability Scale (SUS) and Technology Acceptance Model (TAM). In 2024 3rd International Conference on Digital Transformation and Applications (ICDXA) (pp. 1–5). IEEE. https://doi.org/10.1109/ICDXA61007.2024.10470770

Ballesteros, B. N. V., Habon, P. M., Lopez, J. D. O., & Tan, L. D. (2025). FreshGroup: Clustering first-year student profiles using unsupervised machine learning [Unpublished capstone project]. Ilocos Sur Polytechnic State College, Tagudin Campus.



Caintic, N. A., & Lahaylahay, J. P. (2024). Digital transformation readiness of state universities and colleges in the Philippines: A comparative analysis. Philippine Journal of Higher Education, 12(1), 45–62. https://doi.org/10.5281/pjhe.2024.0012

Chen, X., & Liu, Y. (2024). Digital transformation in university admission systems: A systematic review of cloud-based platforms. Journal of Educational Technology Systems, 52(3), 412–438. https://doi.org/10.1177/00472395231221435


Cohen, J. (1988). Statistical power analysis for the behavioral sciences (2nd ed.). Lawrence Erlbaum Associates.

Comprendio, R. L., & Canlas, R. C. (2025). Post-pandemic digitalization in Philippine higher education institutions: Gaps, workarounds, and sustainability. Education and Information Technologies, 30(2), 1123–1141. https://doi.org/10.1007/s10639-024-03987-1

Davis, F. D. (1989). Perceived usefulness, perceived ease of use, and user acceptance of information technology. MIS Quarterly, 13(3), 319–340. https://doi.org/10.2307/249008

Department of Information and Communications Technology. (2023). National broadband plan 2023: Expanding connectivity for digital government services. DICT Publications. https://dict.gov.ph/nbp2023

Frey, B. B. (Ed.). (2022). The SAGE encyclopedia of research design (2nd ed.). SAGE Publications, Inc. https://doi.org/10.4135/9781071812082

Hevner, A. R., March, S. T., Park, J., & Ram, S. (2004). Design science in information systems research. MIS Quarterly, 28(1), 75–105. https://doi.org/10.2307/25148625

King, W. R., & He, J. (2006). A meta-analysis of the technology acceptance model. Information & Management, 43(6), 740–755. https://doi.org/10.1016/j.im.2006.05.007

Kumar, R., & Singh, A. (2025). Deep learning applications in automated examination scoring and cheating detection. International Journal of Artificial Intelligence in Education, 35(1), 89–118. https://doi.org/10.1007/s40593-024-00388-w


Malaluan, M. B., & Wang, H. (2023). The impact of free tertiary education policy on enrollment and institutional capacity in Philippine state universities. Journal of Asian Public Policy, 16(3), 287–304. https://doi.org/10.1080/17516234.2023.2214567

Malaya, A. R. N., Munar, E. A., & Cuison, F. P. (2022). Information management system for research of Don Mariano Marcos Memorial State University–South La Union Campus. Indonesian Journal of Electrical Engineering and Computer Science, 28(3), 1668–1675. https://doi.org/10.11591/ijeecs.v28.i3.pp1668-1675

Martin, J. (1991). Rapid application development. Macmillan.

Müller, K., & Hoffmann, B. (2023). AI-powered chatbots in university admissions: Reducing administrative workload through conversational agents. International Journal of Educational Technology in Higher Education, 20(1), Article 42. https://doi.org/10.1186/s41239-023-00411-z

National Privacy Commission. (2024). Data privacy compliance guide for educational institutions (NPC Advisory No. 2024-03). Republic of the Philippines. https://www.privacy.gov.ph/advisory-2024-03

Okafor, N., & Tanaka, Y. (2024). Data privacy compliance in global educational technology: A comparative analysis of GDPR and emerging frameworks. Education and Information Technologies, 29(4), 5187–5212. https://doi.org/10.1007/s10639-023-12245-y

Olipas, C. N. P. (2023). The design and development of student information and violation management system (SIVMS) for a higher educational institution (Zenodo Record 8024683). https://doi.org/10.5281/zenodo.8024683

Park, S., Kim, J., & Lee, H. (2023). Automated assessment technologies in higher education: From OMR to AI-driven evaluation. Computers & Education, 198, Article 104756. https://doi.org/10.1016/j.compedu.2023.104756

Pressman, R. S., & Maxim, B. R. (2020). Software engineering: A practitioner's approach (9th ed.). McGraw-Hill Education.

Quiban, J. (2025). Modeling faculty acceptance of LMS: A PLS-SEM validation of the Technology Acceptance Model in Philippine higher education. Business, Education, Social Sciences, and Technology, 1(1), 103–108. https://doi.org/10.69478/BEST2025v1n1a016

Raja, S. P. (2025, August 12). AI-driven development life cycle: Reimagining software engineering. AWS DevOps Blog. https://aws.amazon.com/blogs/devops/ai-driven-development-life-cycle/


Venkatesh, V., & Davis, F. D. (2000). A theoretical extension of the technology acceptance model: Four longitudinal field studies. Management Science, 46(2), 186–204. https://doi.org/10.1287/mnsc.46.2.186.11926

Williams, T., & Garcia, M. (2024). Role-based access control in educational management systems: Security architectures and compliance frameworks. Information Systems Security, 33(2), 201–225. https://doi.org/10.1080/1065898X.2024.2312015

Yukee, A. J. M., Bonifacio, C. L., Salvador, J. M. A., & Macabitas, A. P. (2025). A clustering student ICAT score using machine learning algorithm [Unpublished capstone project]. Ilocos Sur Polytechnic State College, Tagudin Campus.
<!-- UPDATE:END -->

<!-- TAG: appendices_division -->
## APPENDICES

<!-- UPDATE:START -->
APPENDICES
<!-- UPDATE:END -->

<!-- TAG: appendix_a_use_case -->
## APPENDIX A

<!-- UPDATE:START -->
USE CASE DIAGRAM
<!-- UPDATE:END -->

<!-- TAG: appendix_b_letter_conduct -->
## APPENDIX B

<!-- UPDATE:START -->
SCAN OF SIGNED LETTER TO CONDUCT
<!-- UPDATE:END -->
