# Defense Evidence Playbook
## SecureCAT-v2 Proposal & Final Defense Strategy

> [!IMPORTANT]
> **Purpose:** To equip the capstone defense team with a tactical framework that leverages the system's operational deployment to establish credibility, preempt common panel criticisms, and frame the study's research contributions.
>
> This playbook works in tandem with the [pre_proposal_defense.md](file:///home/user/Projects/SecureCAT-v2/capstone/strategy/pre_proposal_defense.md) "Trojan Horse" strategy.

---

## 1. The Deployment Narrative: Start from Strength

Most BSIT capstone defenses start with a promise: *"We will build this system if you approve it."*
**SecureCAT-v2 starts with a fact:** *"This system is built, deployed, and currently in use by the Guidance Office."*

This shift in narrative changes the dynamic of the defense. Instead of arguing whether a system *should* exist or *can* be built, the defense focuses on how the system's *empirical usage data* informs the research and how *advanced features* address verified gaps.

### The 30-Second Opening Statement Hook
> *"Members of the panel, unlike typical capstone proposals that present conceptual prototypes, the foundational architecture of SecureCAT has already been deployed at the ISPSC Tagudin Guidance Office. It is actively accessible to the staff under a shared administrator account to explore intake, scheduling, and report printing operations. This proposal is not about whether we can build an admission system; it is about how we formally validate this active deployment, identify its operational vulnerabilities, and architecturally advance it with enterprise-grade cryptographic security, offline proctoring, computer vision, and multi-tenant isolation."*

---

## 2. Before / After Workflow Comparison

Use this table as a visual slide during the defense to show the progression from the manual baseline, through the Phase 1 deployment, to the proposed Phase 2 (SecureCAT-v2) advancements.

| Admission Stage | Prior Manual Baseline | Phase 1 (Deployed Baseline) | Phase 2 (SecureCAT-v2 Upgrades) | Research Justification |
|---|---|---|---|---|
| **Registration / Intake** | Physical forms; hand-encoding | Digital portal registration | Multi-tenant tenant-isolated portals | **Data Isolation:** Prevents cross-campus data leakage (RA 10173 compliance). |
| **Exam Scheduling** | Manual ledger assignment | Session builder interface | Human-in-the-loop AI-assisted optimizer | **Cognitive Load:** Reduces scheduling conflicts without losing admin control. |
| **Exam Proctoring** | Paper rosters; manual check-in | Live digital check-in | Offline PWA QR attendance + sync | **Infrastructure Resilience:** System remains operational during campus network drops. |
| **Grading / Ingestion** | Hand-grading or Excel keying | CSV import tool | Computer-Vision (CV) OMR scanning | **Human Error:** Eliminates transcription errors from paper sheets. |
| **Score Security** | Plain text spreadsheets | Plain text database records | HMAC-SHA256 score signature locks | **Score Integrity:** Detects database tampering or score manipulation. |
| **System Auditing** | None | Basic mutable log exports | Immutable write-only logs | **Traceability:** Prevents deletion of security logs by users. |

---

## 3. Preempting Panel Questions (Deployment Specific)

### Q1: "If the system is already deployed, what is left to research/develop in this capstone?"
*   **Response:** *"The deployed system represents Phase 1: the functional baseline. However, deployment under real operational conditions exposed critical security and infrastructure challenges that standard CRUD systems cannot solve. The capstone research addresses these specific challenges by implementing and testing four advanced modules: (1) Cryptographic Score Integrity via HMAC signatures to prevent score tampering, (2) Edge PWA architecture to allow offline proctoring during network dropouts, (3) Computer-Vision OMR for automated exam grading, and (4) Multi-Tenant Database Isolation to support scalability to other ISPSC campuses. These are complex computer science/systems engineering problems that extend far beyond a basic web portal."*

### Q2: "Did AI write all the code for this system?"
*   **Response:** *"No. We utilize the AI-Driven Development Lifecycle (AIDLC). In this paradigm, AI acts as an accelerator for boilerplate code generation, but all software engineering tasks—architectural design, Laravel route gating, schema definition, policy writing, database optimization, and test-driven development—are explicitly executed and verified by the research team. Furthermore, our system is backed by a suite of automated unit and feature tests that programmatically prove the security and correctness of our code, ensuring that the final output is robust, reliable, and human-verified."*

### Q3: "How do you verify that the Guidance Office actually needs or uses these features?"
*   **Response:** *"This is the purpose of Specific Objective 1 (Identify). As a pre-requisite, we have established a Feature Verification Protocol to conduct structured interviews with the Guidance staff and run SQL/Artisan audits on the deployed system. This allows us to quantify the exact volume of applicants processed, identify which features were actively used, and document operational pain points. This empirical data feeds directly into our Chapter 4 findings and validates our requirements for the Phase 2 upgrades."*

### Q4: "Why did the Guidance Office use a Super Admin account instead of separate roles?"
*   **Response:** *"During the initial Phase 1 rollout, a shared Super Admin account was provided to the Guidance Office to allow the staff to fully explore all modules and suggest improvements without restricting their access. However, this exploration proved that sharing an administrator account violates basic data governance and RA 10173 guidelines. This observation justifies why the Zero-Trust Role-Based Access Control (RBAC) and immutable write-only audit logs in Phase 2 are critical—they prevent unauthorized access and enforce segregation of duties in production."*

---

## 4. Key Defense Presentation Slides & Visual Assets

To maximize the impact of the deployment narrative, the presentation slides must include:

1.  **Slide 1: Operational Footprint:**
    *   Show a screenshot of the active system portal with a overlay stating: **"STATUS: Operational | Location: Guidance Office | Target: ISPSC Tagudin Admissions."**
2.  **Slide 2: The Two-Phase Intervention Model:**
    *   A diagram illustrating the timeline:
        `Pre-Capstone (Phase 1 Baseline Deployed) ➔ Capstone Descriptive Inquiry (Verify Usage & Gaps) ➔ Capstone Development (SecureCAT-v2 Advanced Modules) ➔ Post-Development Evaluation (SUS + NASA-TLX).`
3.  **Slide 3: Infrastructure Resilience Simulation:**
    *   Illustrate the PWA offline scenario: Proctor device loses Wi-Fi connection -> continues scanning applicant QR codes -> caches in IndexedDB -> resumes connection -> automatically syncs to Laravel backend.
4.  **Slide 4: Score Tamper Detection Demo:**
    *   A brief visual explanation of the HMAC lock:
        `Applicant Score + Secret Key = HMAC. Score altered via database directly = HMAC Mismatch (Security Alert Triggered).`
