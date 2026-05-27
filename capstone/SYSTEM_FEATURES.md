# SecureCAT System Features Baseline & Advanced Scope

This document defines the baseline of features already built in **SecureCAT-v2** alongside the advanced "Trojan Horse" architecture proposed to elevate this project to top-tier capstone complexity.

---

## 🛠️ Baseline Features (Already Built in V2)

### 1. Applicant/Examinee Portal
*   **Account Activation:** Secure password configuration utilizing time-limited setup tokens.
*   **Dashboard Status Tracker:** Real-time visual tracking of the applicant's lifecycle status:
    `Application Submitted` ➔ `Admitted/Accepted` ➔ `Scheduled for Exam` ➔ `Attendance Confirmed` ➔ `Scores Processed` ➔ `Results Released`.
*   **Admission Slip Generation:** Direct rendering and PDF download of custom, print-ready Admission Slips.
*   **Direct Assessment:** Direct-entry option allowing proctors/admins to grade walk-in applicants immediately.
*   **AI Companion Widget:** Basic conversational assistant inside the applicant portal to guide examinees.

### 2. Registrar Portal (Staff & Registrar Admin)
*   **Application Pipeline:** Automated application submission review, data validation, and approval/rejection pipeline.
*   **Room & Course Management:** Admin controls to map campus rooms, seat capacities, exam buildings, and academic courses.
*   **Interactive Scheduling Assistant:** Algorithmic helper assisting Registrar Admins in assigning exam sessions.

### 3. Guidance Portal (Proctors & Test Admins)
*   **Aptitude Areas & Rating Scales:** Management of specific test components (e.g., Verbal, Math, Abstract Reasoning) and rating scales.
*   **Session Roster Management:** Real-time roster management, proctor assignment, and digital student attendance tracking.
*   **OMR Score Import:** Machine-readable score ingestion via standardized CSV files.
*   **Consultation Summaries:** Interface for counselors to write comments and recommend specific college courses based on applicant results.

---

## 🦄 Advanced Capstone Scope (The "Trojan Horse" Strategy)

To ensure the system meets high software engineering and research standards under the locked-in title, the following advanced capabilities are mapped directly to the title components:

### 1. "Role-Based" ➔ Zero-Trust Data Governance
*   **Cryptographic Score Integrity (HMAC Signature Locks):**
    *   *Mechanism:* When a test score is finalized, the system creates a SHA-256 HMAC signature using a server-side secret key combined with `Applicant UUID + Test Score + Proctor UUID`.
    *   *Tamper Detection:* If an unauthorized user modifies the database directly (e.g., changing scores via raw SQL), the HMAC signature will mismatch, immediately flagging the score as tampered in the dashboard.
*   **Immutable Write-Only Audit Logs:**
    *   Every security-sensitive action (score changes, proctor assignments, application approvals) is recorded in a ledger containing timestamp, actor IP, browser user agent, and before/after database states.
*   **Laravel Policy Route Gating:**
    *   Strict backend policy classes verify that no API routes or controller actions can be bypassed, even if HTML components are manually altered on the client side.

### 2. "Admission Testing System" ➔ Computer Vision & Offline Resilience
*   **Automated OMR Answer Sheet Ingestion:**
    *   *Mechanism:* Test Administrators scan or snap a photo of a shaded physical OMR answer sheet. A computer-vision service parses the image, detects shaded bubbles, grades the test automatically, and imports the score directly into the DB.
*   **Offline-Resilient Proctor Portal (Edge PWA):**
    *   *Mechanism:* A Progressive Web App (PWA) with Service Workers. If the campus Wi-Fi drops, proctors can continue scanning applicant QR codes at the exam room door offline. The scanned records are cached in IndexedDB and synchronized in the background once internet connectivity is restored.

### 3. "Guidance & Registrar Offices" ➔ AI-Powered Office Operations
*   **In-Office RAG Copilot:**
    *   *Mechanism:* A secure, local AI chatbot for guidance counselors and registrar staff. Powered by local vector embeddings, it allows staff to query database statistics using natural language (e.g., *"Show me the count of accepted applicants who failed the English component in Room 101"*).
*   **Automated Scheduling Agent:**
    *   *Mechanism:* An optimization algorithm that automatically schedules examinees to available exam batches based on constraints (room capacity, proctor availability, and time slots).

### 4. "ISPSC Tagudin" ➔ Multi-Tenant SaaS Architecture
*   **Isolated Database Segregation:**
    *   The backend is architected using multi-tenancy principles. While initially deployed only for ISPSC Tagudin, the system segregates tenant data to conform to the Philippine Data Privacy Act (DPA), allowing seamless expansion to other ISPSC campuses in the future.
