# Pre-Proposal Defense Strategy Notes

These notes outline the **"Trojan Horse Strategy"** for defending the current locked-in title, demonstrating how to align advanced system architecture with academic expectations.

---

## 🛡️ The Core Strategy: The Trojan Horse
*   **The Dilemma:** The title is locked as: *"SecureCAT: A Role-Based College Admission Testing System for the Guidance and Registrar Offices at ISPSC Tagudin."* The panel might perceive this as a standard CRUD application.
*   **The Solution:** We maintain this simple, compliant title for administrative purposes, but engineer an advanced system architecture underneath. During the defense, we showcase how these features solve the title's requirements in an enterprise-grade manner.

---

## 🧩 Mapping Advanced Features to Title Components

When presenting to the panel, justify the advanced technical scope by linking each feature directly back to a word in the approved title:

| Title Component | Expected Scope (Panel) | Advanced Implementation (V2) | Defense Justification / Script |
|---|---|---|---|
| **"Role-Based"** | Simple login forms and permissions. | Zero-Trust Data Governance, HMAC score signature locks, write-only audit logs. | *"To enforce strict separation of duties and comply with the DPA, we implemented cryptographic score signatures. Even database administrators cannot alter scores directly without breaking the signature."* |
| **"Admission Testing System"** | Forms for manually typing student details and test scores. | Automated OMR bubble sheet scanning via Computer Vision; Offline-resilient Proctor PWA. | *"To eliminate human entry errors, our testing system grades tests automatically via photo upload (OMR Ingestion). Additionally, proctors can scan examinee QR codes offline at the door if campus Wi-Fi fails."* |
| **"Guidance & Registrar"** | Standard tables, search bars, and printable PDFs. | RAG AI Copilot (Mixedbread embeddings); Automated scheduling optimizer. | *"To support staff dealing with high data volumes, we built a natural language interface (RAG Copilot) allowing secure database queries, and an automated scheduler that assigns applicants based on room capacities."* |
| **"ISPSC Tagudin"** | A database hardcoded specifically for one campus. | Multi-Tenant Database Segregation. | *"While initially deployed exclusively for the Tagudin campus, the database is built on a Multi-Tenant SaaS architecture to facilitate future expansion to other ISPSC campuses while maintaining data isolation."* |

---

## 🎓 The Defense Room Playbook (Negotiating the Title)

During the Proposal Defense, the panel's primary role is to critique your scope. Use this to your advantage:

1.  **Do Not Propose a Title Upgrade Yourself:** Present your system's advanced features under the current title. 
2.  **Let the Panel Make the Suggestion:** When the panel realizes that your system includes Computer Vision, Offline PWAs, HMAC Security, and RAG AI, a panelist will almost certainly say: *"Your system is too advanced for this simple title. You should rename it to reflect these features."*
3.  **Accept the Recommendation:** Instantly agree, write down their exact wording, and thank them. This guarantees your title upgrade is approved without departmental friction.

---

## ✉️ Addressing Title Changes Post-Letter Approval

If you route your client permission letters using the current title and the panel later changes the title during the Proposal Defense, **the original letter remains completely valid**:

*   **Administrative vs. Academic Scope:** The Campus Director's signature grants administrative permission to conduct research on the system (**SecureCAT**) at the specific offices. Refinements to the academic subtitle do not invalidate this permission.
*   **The Appendix Convention:** In final bound thesis copies, it is standard practice to place the original signed letter (bearing the old title) in the Appendix. The deans and department chairpersons understand that titles evolve between the initial title defense and the final manuscript submission.
*   **No Re-Routing Needed:** You do not need to route a new letter for signature if the subtitle is modified by the panel, as the core system and researchers remain the same.

---

## 📝 Document (Thesis) Drafting Guidelines

*   **Chapter 1 (Objectives & Significance):** State the general objectives exactly matching the title. In the **Specific Objectives**, include tasks like: *"To design secure grading verification workflows"* and *"To implement offline-resilient applicant verification."*
*   **Chapter 3 (Methodology):** Detail the software architecture, the HMAC verification mathematical formula, the PWA service-worker lifecycle, and the vector search flow. This shifts the focus from CRUD to complex computer science.
