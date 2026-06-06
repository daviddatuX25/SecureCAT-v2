# June 8 Interview Guide — SecureCAT Capstone Research

**Date:** June 8, 2026
**Location:** ISPSC Tagudin Campus
**Team:** David (Lead Interviewer), Christine (Audio + Observer), Assigned Member (Roamer / Process Mapper)
**Duration:** 8AM–12PM (4 blocks)

---

## General Interview Instructions

- **Anonymity of Prototype:** Do not disclose that a system has been developed. You are researchers studying the admission process, not demonstrating a product.
- **Terminology:** Use "SecureCAT" only — never say "V2", "Version 2", or "the system we built."
- **Research Framing:** Maintain a neutral researcher perspective at all times. Ask open-ended questions. Let them talk. If they pause, wait — don't fill the silence.
- **Goal:** Use responses to refine the capstone manuscript draft and define responsibility boundaries. Every confirmed or denied claim feeds directly into STEER markers in the draft chapters.
- **Don't say "I already know."** Even if familiar with the topic, the interview must feel like genuine discovery. Say *"That's helpful — can you tell me more about that?"*
- **Between offices:** Quick 30-sec sync — "What did we get? Anything to follow up?" before moving to the next block.

---

## Pre-Interview Checklist

- [ ] Signed letter to conduct research obtained (physical copy printed)
- [ ] Audio recorder app tested — record 10 sec, play back, confirm clear audio
- [ ] Backup recording device ready (second phone or teammate's phone)
- [ ] Phones fully charged + portable charger packed
- [ ] Consent script reviewed by all team members
- [ ] This guide reviewed at least once by each interviewer
- [ ] Campus contacts confirmed: Director availability, Registrar and Guidance willingness
- [ ] Pack: portable charger, pens, water

---

## Verbal Consent Script

*Read this aloud at the start of every formal interview. Begin recording BEFORE reading so their verbal "yes" is captured.*

> "Good morning/afternoon. Thank you for agreeing to speak with us. Before we begin, I need to inform you of the following:
>
> **One:** This interview is part of our capstone research study for BS Information Technology at ISPSC. The study is about the college admission testing process at ISPSC Tagudin.
>
> **Two:** Your participation is completely voluntary. You may choose not to answer any question, and you may stop the interview at any time without any consequences.
>
> **Three:** Your responses will be kept confidential. We will not use your real name in our manuscript. Your identity will be anonymized.
>
> **Four:** We are recording this conversation for accuracy in our notes. The recording will only be used by our research team and will not be shared publicly.
>
> Do you understand and agree to proceed with these conditions?"

*Wait for them to say "Yes" or "Oo" on the recording. If they agree → proceed. If they decline → thank them and end.*

### Shorter Version — For Enrolee/Applicant Intercepts

> "Hi, we're IT students conducting research about the admission process. Can we ask you 5–6 quick questions? It's anonymous — no names. We'll record for our notes. Is that okay?"

*Wait for verbal "yes" before asking questions.*

---

## Block A — Registrar Office (~40-50 min)

**Assigned RQs:** RQ1 (Process), RQ2 (Pain points), RQ3 (Requirements)

### Mandatory Before Starting

1. Present the official letter — show the signed letter to interviewee(s)
2. Read the verbal consent script aloud — ensure "yes" is recorded
3. Confirm audio recording is running
4. Ask for their exact official job title

### Phase 1: Claim Verification (~10 min)

Read each claim. Ask them to confirm ✅ / partially ⚠️ / deny ❌:

1. Application intake uses Google Forms + manual spreadsheet tracking
2. Registrar manually generates admission slips using Word templates
3. There is no automated status notification to applicants
4. Exam scheduling is coordinated verbally with Guidance
5. Applicant documents stored in physical folders with no digital backup

### Phase 2: Process Walk-Through (~15 min)

**Main prompt:**
> "Walk me through what happens from when an applicant submits their application to when they receive their admission result — step by step. Who does what? Where are the handoffs between Registrar and Guidance?"

**Probe questions:**

| Probe | RQ Mapping |
|-------|------------|
| "At which step does Registrar's responsibility end and Guidance's begin?" | RQ1, scope |
| "How many applicants per cycle? Per day at peak?" | RQ2, C2-05 |
| "What forms/documents do you generate? Can I see blank copies?" | Artifact collection |
| "What happens when documents are incomplete?" | RQ2 |

### Phase 3: Pain Points (~10 min)

**Main prompt:**
> "What are the top 3 things that slow you down or frustrate you in the current admission process?"

**Probe questions:**

| Probe | RQ Mapping |
|-------|------------|
| "What do you do manually that you wish a system handled?" | RQ2, RQ3 |
| "Do you use any part of a digital system? Which parts?" | RQ1, RQ2 |
| "Have you ever lost data, had duplicates, or campus mix-ups?" | RQ2, RQ3 |
| "How is applicant data protected? Who can access the spreadsheets?" | RQ3, RA 10173 |

### Phase 4: System Integration (~10 min)

**Main prompt:**
> "If a unified digital system could auto-generate admission slips from applicant-filled web forms, how would that change your workload?"

**Probe questions:**

| Probe | RQ Mapping |
|-------|------------|
| "Would you be open to applicants filling their own data online?" | RQ3 |
| "Do you need to work offline? How often does internet go down?" | RQ3, offline |
| "What reports does the Director ask for? How long does that take?" | RQ2, RQ3 |

---

## Block B — Guidance Office (~40 min)

**Assigned RQs:** RQ1 (Process), RQ2 (Pain points), RQ3 (Requirements)

**Note:** Friendly visit exception — you may visit the Guidance Office casually to explain the letter is still under process. However, the formal interview below requires the signed letter.

### Mandatory Before Starting

1. Present the official letter — show the signed letter to interviewee(s)
2. Read the verbal consent script aloud — ensure "yes" is recorded
3. Confirm audio recording is running
4. Ask for their exact official job title

### Phase 1: Claim Verification (~10 min)

Read each claim. Confirm ✅ / partially ⚠️ / deny ❌:

1. Guidance counselors manually score exams using OMR overlay templates
2. Guidance staff handle all proctoring rather than delegating to other faculty
3. Attendance during exams is tracked on paper sign-in sheets
4. Test results computed in Excel and manually transferred to Registrar
5. No system-level audit trail for who accessed or modified scores

### Phase 2: Testing Workflow (~15 min)

**Main prompt:**
> "Walk me through exam day — from room setup to score release. Who proctors? How is attendance taken? How are papers scored?"

**Probe questions:**

| Probe | RQ Mapping |
|-------|------------|
| "If a system allowed other staff to proctor securely, would that help?" | RQ3, RBAC |
| "How do you coordinate exam schedule/rooms with Registrar?" | RQ1 |
| "What if a staff member is absent on a heavy testing day?" | RQ2 |
| "How long from last exam to result release?" | RQ2 |
| "Have there been scoring disputes or errors?" | RQ2, RQ3 |

### Phase 3: Pain Points + Security (~10 min)

**Main prompt:**
> "What's the most stressful part of the admission testing season?"

**Probe questions:**

| Probe | RQ Mapping |
|-------|------------|
| "Unauthorized score access or tampering incidents?" | RQ3 |
| "What happens when Wi-Fi fails during testing?" | RQ3 |
| "How do you handle special accommodations?" | RQ2 |
| "How do you manage course recommendations with quotas?" | RQ1 |

### Phase 4: Requirements + Advanced (~5 min)

**Main prompt:**
> "If you could change one thing about testing and scoring, what would it be?"

**Probe questions:**

| Probe | RQ Mapping |
|-------|------------|
| "Would automated OMR scanning be a priority?" | RQ3 |
| "Would both auto-computation AND your conversion tables future-proof scoring?" | RQ3 |
| "If an AI companion were available, how could it assist you?" | RQ3 |

---

## Block C — Campus Director (~20 min)

**Timing depends on morning arrangement.** CD may have been interviewed at 8AM during the Letter and Inquiry Task, or scheduled for later.

### Mandatory Before Starting

1. Present the official letter — show the signed letter to the Director
2. Read the verbal consent script aloud — ensure the Director says "yes" on recording
3. Confirm audio recording is running

### 6 Strategic Questions (20 min max)

1. **Digital vision:** "How fully digital do you envision the Tagudin admission experience becoming?"
2. **Infrastructure:** "Would you prefer local MIS servers or cloud hosting for uptime, given seasonal usage?"
3. **Multi-campus:** "How do you feel about a unified system allowing seamless applicant transfers across ISPSC campuses?"
4. **Dashboard:** "Would you prefer a live admin dashboard to monitor admission progress, or generated reports from staff?"
5. **Proctor delegation:** "Would you support delegating proctoring to non-Guidance staff with role-based access ensuring test security?"
6. **Data privacy:** "What are your expectations regarding RA 10173 compliance for applicant records?"

---

## Enrolee/Applicant Intercepts (~5-7 min each)

Use the shorter verbal consent. No names recorded.

### Micro-Interview Script (6 questions)

1. **Discovery:** "How did you find out about the admission process? Where did you submit?"
2. **Wait time:** "How long have you been waiting for results? Do you know your current status?"
3. **Pain point:** "What was the most confusing or frustrating part of applying?"
4. **Format:** "Did you use any online form or system, or was everything on paper?"
5. **Interest:** "If there was an app/website to track your admission status, would you use it? Phone or computer?"
6. **Rating:** "On a scale of 1–5, how would you rate the overall admission experience?"

---

## STEER Marker Cross-Reference

Each interview question maps to specific STEER markers (unverified claims) in the draft manuscripts. Use this table to know which questions resolve which markers.

| STEER Marker | Location | What to Get | Interview Source | Fallback |
|---|---|---|---|---|
| Annual applicant volume `[N]` | C1-04 Background P4, C1-01 Background P1 | Exact annual applicant count | Block A Phase 2: "How many applicants per cycle?" | Leave `[N]` blank |
| Registrar staff headcount `[N]` | C1-04 Background P4 | Exact number of Registrar staff | Block A Phase 1: Claim verification | Blank table row |
| Guidance staff headcount `[N]` | C2-05 Population | Exact number of Guidance staff | Block B Phase 1: Claim verification | Blank table row |
| Active proctor count `[N]` | C2-05 Population | Exact number of proctors | Block B Phase 1: Claim verification | Blank table row |
| Applicant intercept count `[N]` | C2-05 Population | Total intercept surveys completed | Enrolee intercepts tally | Blank table row |
| Total respondent count `[N]` | C2-05 Population | Sum of all respondents | Post-interview calculation | Blank table row |
| Processing time estimates | C1-12 Significance | Actual time claims (manual steps) | Block A Phase 3: manual work probes | Existing estimate |
| Workload reduction targets | C1-12 Significance | Which tasks take most time | Block B Phase 3: stress probes | Existing estimate |
| Queue wait time data | C1-12 Significance | How long applicants wait | Enrolee intercept Q2 + Q6 | Existing estimate |
| Taxonomy validation | C2-06 Research Instruments | Confirm respondent groups match actual | Cross-ref Block A + B Phase 1 claims | Keep as-is |

---

## Evidence Tagging Quick Reference

Use these tags in all notes and post-interview write-ups:

| Tag | Meaning |
|---|---|
| `[INT-REG-XX]` | Interview, Registrar, quote #XX |
| `[INT-GUID-XX]` | Interview, Guidance, quote #XX |
| `[INT-DIR-XX]` | Interview, Director, quote #XX |
| `[INT-APP-XX]` | Applicant intercept #XX |
| `[OBS-XX]` | Direct observation |
| `[DOC-FORM-XX]` | Document artifact collected |
| `[DEV-KNOW-XX]` | Developer knowledge (must cross-ref) |

---

## Post-Interview Checklist

Do this **the same day** (June 8 evening) while memory is fresh.

- [ ] Team debrief — each shares top 3 surprises, top 3 confirmations, top 3 gaps
- [ ] Label all audio files: `BLOCK-A-REG`, `BLOCK-B-GUID`, `BLOCK-C-DIR` + timestamps
- [ ] Back up audio + photos to cloud immediately
- [ ] Photograph all collected artifacts → upload
- [ ] Transfer claim sheet markings to digital ledger
- [ ] Run STEER marker sweep on all drafts — replace `[N]` placeholders with confirmed data
- [ ] Evidence tags assigned to all confirmed claims

---

## Contingency Quick Reference

| Situation | Action |
|---|---|
| CD unavailable | Ask for 15-min call on June 9 |
| Registrar too busy | Cut to 30 min — skip Phase 4 |
| No enrolees in queue | Ask staff for recent applicant contacts |
| Audio fails | Switch to rapid handwritten notes |
| Rain / travel delay | Pack night before. Leave 30 min early |

---

## Role-Specific Notes

**David (Lead Interviewer):** Guide the conversation through the phases. Don't rush — let them finish. If they go off-topic, gently redirect with *"That's interesting — going back to the process..."*

**Christine (Audio + Observer):** Keep the recorder running the entire time. Watch for body language cues. Jot key quotes or contradictions. Flag if audio quality drops.

**Assigned Member (Roamer / Process Mapper):** Map physical workflows — where forms go, which desk handles what. Snap photos of any physical artifacts (with permission). While one block is running (e.g., Registrar interview), you can do enrolee intercepts in the hallway — just coordinate timing.
