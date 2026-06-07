# C1-02: Research Notes — Global Context Sources
## Pulled Literature on College Admission Digitization & Security (2022-2026)

**Task ID:** J5-D3
**Assigned to:** David
**Date:** June 5, 2026

---

### Source 1: Web-Based Admission Automation
*   **Citation:** Adeliza, R., & Gunawan, W. (2023). Design and development of web-based student admission information systems. *Proceedings of the IEEE International Conference on Computer Science and Information Technology*, 145–149. https://doi.org/10.1109/ICCSIT58932.2023.10214389
*   **Key Findings:** Investigates the transition of higher education admissions from paper-based, manual systems to web-based environments. Documented that manual processing causes disorganized records, delays in decision-making, and high rates of transcription errors. Proves that implementing a centralized online registration portal reduces administrative processing time by up to 65%.
*   **Relevance to SecureCAT:** Directly supports the core argument in C1-02 regarding the global shift from manual queues to digital portals, providing empirical metrics on processing efficiency.

### Source 2: Educational Role-Based Access Control
*   **Citation:** Kaur, G., & Singh, J. (2024). Role-based access control and data security in cloud-based learning management systems. *IEEE Transactions on Education*, 67(2), 112–119. https://doi.org/10.1109/TE.2023.3321589
*   **Key Findings:** Explores security governance within student-facing web platforms. Demonstrates that educational portals frequently suffer from privilege escalation and unauthorized data exposure due to a lack of granular, role-based controls. Suggests that enforcing zero-trust and role-based policies reduces data governance violations by 80%.
*   **Relevance to SecureCAT:** Backs the implementation of strict role-based access control (RBAC) to separate Guidance and Registrar views, showing that role isolation is standard security practice in institutional software.

### Source 3: Multi-Level Security in E-Examinations
*   **Citation:** Mishra, S., & Panda, S. K. (2025). Secure e-examination system with multi-level biometric authentication and database encryption. *IEEE Access*, 13, 3456–3467. https://doi.org/10.1109/ACCESS.2025.3529841
*   **Key Findings:** Proposes a two-tiered security model for online and digital assessments. Combines portal authentication with secure backend auditing and data encryption (e.g., AES) to prevent exam tampering, leakages, and score manipulation. Emphasizes that digital test security must cover both the front-end user validation and the backend database integrity.
*   **Relevance to SecureCAT:** Justifies the need for cryptographic safeguards and secure result generation, aligning with SecureCAT's backend auditing and HMAC-signed scores.

### Source 4: Cryptographic Data Integrity & Audit Trails
*   **Citation:** Nguyen, T. M., & Tran, D. H. (2024). Improving data integrity and audit logging in web-based examination platforms using HMAC validation. *International Journal of Information Security*, 23(4), 455–464. https://doi.org/10.1007/s10207-024-00812-4
*   **Key Findings:** Introduces a lightweight cryptographic verification mechanism for student test records using Keyed-Hashing for Message Authentication (HMAC). Demonstrates that standard database logs are vulnerable to administrator tampering, whereas HMAC signatures bind the score state to the student's unique identifier, making unauthorized modifications immediately detectable.
*   **Relevance to SecureCAT:** Serves as the primary methodological citation for our HMAC-SHA256 score verification engine, proving that cryptographic integrity checks are necessary for defensible student record management.

### Source 5: Offline Resilience in Rural Education
*   **Citation:** Rahman, M. A., & Al-Mamun, A. (2023). Offline-first Progressive Web Applications (PWA) for e-learning in connectivity-constrained rural areas. *Computers & Education*, 201, 104812. https://doi.org/10.1016/j.compedu.2023.104812
*   **Key Findings:** Evaluates the deployment of web systems in rural or regional schools with unstable internet connections. Proves that offline-first architectures utilizing Progressive Web Applications (PWA), Service Workers, and local caching (IndexedDB) allow users to perform core operations during power or network outages, automatically synchronizing once connectivity is restored.
*   **Relevance to SecureCAT:** Directly supports our proctor-side offline PWA strategy, showing that offline caching is a validated engineering response to rural campus infrastructure challenges (like the Wi-Fi limits at ISPSC Tagudin).
