# Literature Search Term Cheat Sheet — SecureCAT-v2
## Optimized Queries for Each Literature Review Section

**Purpose:** Use these exact search phrases in the databases listed. Each section provides primary and fallback queries, ordered from most specific to broadest.

**General Rules:**
- All sources must be **2022-2026** (filter by date in Google Scholar / IEEE / ERIC)
- Prefer **peer-reviewed journal articles** and **conference proceedings** over blog posts or reports
- Government circulars and official CHED publications are acceptable for Philippine context
- When Google Scholar results are thin, try IEEE Xplore, ACM Digital Library, ERIC, or ResearchGate

---

## C2-01: RBAC + Zero-Trust + Data Integrity (David)
**Target: 5+ APA citations | Difficulty: 🟢 Easy**

### Primary Queries (Google Scholar)
```
"zero trust architecture" education 2023 OR 2024 OR 2025
"role-based access control" "assessment system" security
"HMAC" "data integrity" education OR "information system"
"zero trust" "higher education" security
"immutable audit log" "information system"
"data integrity verification" "scoring system" OR "assessment"
```

### Fallback Queries
```
"zero trust" institutional system 2024
"role-based access" "data governance" education
"cryptographic integrity" "web application" education
"audit trail" "score tampering" OR "data manipulation" education
```

### Specific Sources to Look For
- NIST Zero Trust Architecture (SP 800-207) — cite 2022+ updates or studies referencing it
- IEEE papers on RBAC in educational platforms
- Studies on assessment data integrity in e-learning or exam management systems

---

## C2-02: Automated Scoring & OMR Technologies (Christine)
**Target: 5+ APA citations | Difficulty: 🟡 Moderate — use broad search terms**

### Primary Queries (Google Scholar)
```
"automated scoring" education 2023 OR 2024 OR 2025
"computer-aided assessment" "developing countries" OR "developing regions"
"optical mark recognition" mobile OR smartphone 2023
"image-based" "answer sheet" grading OR scoring
"digital examination" scoring system education
```

### Fallback Queries (IEEE Xplore + ERIC)
```
"automated test grading" "higher education"
"OMR" "low-cost" OR "low-resource" education
"computer vision" "exam" OR "test" scoring
"bubble sheet" detection OR recognition
"assessment automation" "state university" OR "public university"
```

### Broader Category Queries (if OMR-specific results are thin)
```
"e-assessment" "automated" education 2024
"paperless examination" developing countries
"digital transformation" "examination process" higher education
"test scoring technology" education 2023
```

### Specific Sources to Look For
- IEEE conference papers on mobile OMR implementations
- Studies from Indian, Southeast Asian, or African institutions (similar resource constraints)
- ERIC papers on computer-aided assessment in public universities

---

## C2-03: AI/RAG in Education (David)
**Target: 5+ APA citations | Difficulty: 🟢 Easy — abundant post-2023 literature**

### Primary Queries (Google Scholar)
```
"retrieval augmented generation" education 2024 OR 2025
"AI chatbot" "higher education" admission OR enrollment
"conversational AI" "student guidance" OR "student advising"
"RAG" "educational" OR "academic" 2024
"AI assistant" "university admission" OR "enrollment"
```

### Fallback Queries
```
"large language model" education guidance 2024
"intelligent tutoring" "admission" OR "enrollment" system
"natural language processing" "student support" higher education
"AI-powered" "course recommendation" university
"chatbot" "frequently asked questions" university
```

### Specific Sources to Look For
- Studies on ChatGPT/LLM integration in university student services
- RAG-based educational assistants (there are many 2024-2025 papers)
- AI-driven course recommendation systems in HEIs

---

## C2-04: PWA & Offline Resilience (David)
**Target: 5+ APA citations | Difficulty: 🟡 Moderate — use the seasonal-user framing**

### Primary Queries (Google Scholar)
```
"progressive web application" education 2023 OR 2024
"offline-capable" "web application" education OR institutional
"service worker" "offline" education OR "developing countries"
"PWA" "mobile access" "higher education" OR university
"native app" vs "web app" institutional OR education
```

### Reframed Queries (seasonal-user + lightweight access)
```
"lightweight mobile access" education "developing countries"
"offline-first" application education OR institutional
"connectivity-resilient" system education OR "rural area"
"intermittent connectivity" "web application" 2024
"mobile web" vs "native application" "user adoption" education
```

### Infrastructure-Focused Queries
```
"internet connectivity" "rural campus" OR "rural university"
"mobile device constraints" education "developing countries"
"low-bandwidth" "web application" education
"background synchronization" "offline data" education
```

### Specific Sources to Look For
- Studies on PWA adoption in educational institutions (especially 2023-2025)
- Papers comparing native vs. web for infrequent-use institutional services
- Connectivity studies in Philippine or Southeast Asian educational settings
- IndexedDB/Service Worker architecture papers in education contexts

---

## C2-05: Scalable Data Architecture & Data Governance (David)
**Target: 5+ APA citations | Difficulty: 🟢 Available — use the data silo framing**

### Primary Queries (Google Scholar)
```
"data silo" "higher education" OR "university" 2023 OR 2024
"multi-campus" "student information system" OR "enrollment system"
"data governance" "educational institution" 2024
"centralized" vs "decentralized" "student information" university
"scalable architecture" "education system" multi-campus
```

### Philippine-Specific Queries
```
"RA 10173" "data privacy" education OR "higher education"
"Data Privacy Act" Philippines "information system" education
"Philippine" "state university" "information system" digital
CHED "data governance" OR "data management" university
"multi-campus" "state university" Philippines
```

### Multi-Tenancy Queries (as engineering solution)
```
"multi-tenant" "SaaS" education OR "educational institution"
"tenant isolation" "data privacy" education
"shared infrastructure" "multi-site" university OR institution
"database partitioning" "institutional" OR "educational" system
"scalable" "information system" "future expansion" education
```

### Specific Sources to Look For
- CHED circulars on data management and information systems
- NPC (National Privacy Commission) advisories for educational institutions
- Studies on multi-campus student information systems (US state university systems, Indian IIT network, etc.)
- Papers on data interoperability in educational networks

---

## C2-06: Related Systems (Jaypee)
**Target: 5+ APA citations | Difficulty: 🟢 Easy — comparative analysis**

### Queries for Finding Comparable Systems
```
"admission system" "higher education" OR university architecture
"enrollment management system" college OR university
"online admission" "developing countries" OR Philippines
"college admission" "information system" 2023 OR 2024
"automated admission" "state university" OR "public university"
```

### Queries for Specific System Types
```
"OMR scoring system" education OR exam
"UCAS" OR "Common App" admission system architecture
"student enrollment" "web-based" OR "online" system Philippines
"exam management system" university
"applicant tracking" "higher education" system
```

### Philippine-Specific System Queries
```
"Philippine" university "admission system" OR "enrollment system"
"SUC" OR "state university" Philippines "information system"
ISPSC OR "Ilocos Sur" education technology
"Region I" Philippines education "information system"
```

### Specific Systems to Research (Direct Product Research)
1. **UCAS** (UK) — Universities and Colleges Admissions Service
2. **Common App** (US) — Common Application
3. **OMRChecker** — Open-source OMR tool
4. **Scantron** — Commercial OMR/assessment
5. Philippine HEI systems — search for "admission system" on university websites of UP, PUP, TUP, Bulacan State University, Benguet State University, etc.

---

## Chapter 1 Background Queries (C1-02, C1-03, C1-04)

### C1-02: Global Context
```
"digital transformation" "university admission" global OR international
"online admission" "efficiency" OR "effectiveness" higher education
"secure assessment" "data integrity" OR "data governance" education
"AI" "admission" OR "enrollment" "higher education" 2024
"mobile access" "institutional services" OR "university services"
```

### C1-03: National Context (Philippines)
```
CHED "digitization" OR "digital transformation" "higher education" Philippines
"RA 10173" compliance "higher education" OR university
"Philippine" "state university" "information system" challenges
CMO "enrollment" OR "admission" Philippines 2023 OR 2024
"Philippine higher education" "digital readiness" OR "IT adoption"
"COVID-19" "digital transformation" "Philippine" education
```

### C1-04: Local Context (ISPSC / Region I)
```
ISPSC "admission" OR "enrollment" OR "Ilocos Sur"
"Region I" Philippines education technology OR "information system"
"provincial campus" OR "rural campus" Philippines challenges
"state university" "Ilocos" OR "Region I" digital OR technology
"multi-campus" "state university" Philippines challenges
```

---

## Databases to Use

| Database | Best for | URL |
|----------|---------|-----|
| **Google Scholar** | Everything — start here | scholar.google.com |
| **IEEE Xplore** | Technical papers (RBAC, PWA, OMR, architecture) | ieeexplore.ieee.org |
| **ACM Digital Library** | Computer science papers | dl.acm.org |
| **ERIC** | Education-specific research | eric.ed.gov |
| **ResearchGate** | Finding full-text PDFs of papers found elsewhere | researchgate.net |
| **CHED Website** | Philippine HEI policies, CMOs | ched.gov.ph |
| **NPC Website** | RA 10173 advisories | privacy.gov.ph |
| **Philippine E-Journals** | Local research context | ejournals.ph |
| **Semantic Scholar** | AI-assisted discovery | semanticscholar.org |

---

## Search Tips

1. **Use date filters aggressively** — set "Since 2022" in Google Scholar
2. **Use OR for synonyms** — `"admission" OR "enrollment"` catches both
3. **Use quotes for exact phrases** — `"zero trust architecture"` not `zero trust architecture`
4. **Try the "Cited by" chain** — find one good paper, then check what cites it
5. **Check paper reference lists** — one good 2024 survey paper can give you 5+ valid sources
6. **Don't ignore conference proceedings** — IEEE conferences often have niche topics (OMR, PWA) that journals skip
7. **For Philippine sources** — search Google Scholar with `site:edu.ph` or search ejournals.ph directly
