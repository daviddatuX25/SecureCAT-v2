# Scaling Readiness — Task Tracker

> Updated: 2026-05-27

| # | Plan File | Phase | Status |
|---|-----------|-------|--------|
| P1-T1 | [phase1-route-cleanup](./2026-05-27-scaling-readiness-phase1-route-cleanup.md) | Remove `/proctor` dead stub route | ⬜ TODO |
| P1-T2 | [phase1-route-cleanup](./2026-05-27-scaling-readiness-phase1-route-cleanup.md) | Remove `exam-scheduling/schedule-assistant` GET redirect | ⬜ TODO |
| P1-T3 | [phase1-route-cleanup](./2026-05-27-scaling-readiness-phase1-route-cleanup.md) | Clarify grading import POST-guard redirect comments | ⬜ TODO |
| P1-T4 | [phase1-route-cleanup](./2026-05-27-scaling-readiness-phase1-route-cleanup.md) | Clarify knowledge-documents redirect comment | ⬜ TODO |
| P1-T5 | [phase1-route-cleanup](./2026-05-27-scaling-readiness-phase1-route-cleanup.md) | Delete orphaned `AdmissionSlipTemplateController` | ⬜ TODO |
| P1-T6 | [phase1-route-cleanup](./2026-05-27-scaling-readiness-phase1-route-cleanup.md) | Merge duplicate `setup+reports` route groups | ⬜ TODO |
| P2-T1 | [phase2-application-controller-split](./2026-05-27-scaling-readiness-phase2-application-controller-split.md) | Extract `PublicApplicationController` | ⬜ TODO |
| P2-T2 | [phase2-application-controller-split](./2026-05-27-scaling-readiness-phase2-application-controller-split.md) | Extract `PortalApplicationController` | ⬜ TODO |
| P2-T3 | [phase2-application-controller-split](./2026-05-27-scaling-readiness-phase2-application-controller-split.md) | Extract `BulkApplicationController` | ⬜ TODO |
| P2-T4 | [phase2-application-controller-split](./2026-05-27-scaling-readiness-phase2-application-controller-split.md) | Extract `AdmissionSlipController` | ⬜ TODO |
| P2-T5 | [phase2-application-controller-split](./2026-05-27-scaling-readiness-phase2-application-controller-split.md) | Extract `AdminApplicationController` + delete old controller | ⬜ TODO |
| P3-T1 | [phase3-exam-session-controller-split](./2026-05-27-scaling-readiness-phase3-exam-session-controller-split.md) | Extract `ExamSessionWorkflowController` (7 transition methods) | ⬜ TODO |
| P3-T2 | [phase3-exam-session-controller-split](./2026-05-27-scaling-readiness-phase3-exam-session-controller-split.md) | Extract `ExamSessionRosterController` (assign/remove applicants) | ⬜ TODO |
| P3-T3 | [phase3-exam-session-controller-split](./2026-05-27-scaling-readiness-phase3-exam-session-controller-split.md) | Extract `ExamSessionMonitoringController` (monitoring + test-admin) | ⬜ TODO |
| P4-A1 | [phase4-service-decomposition](./2026-05-27-scaling-readiness-phase4-service-decomposition.md) | Define `ResultSheetRenderer` interface + `RenderResult` VO | ⬜ TODO |
| P4-A2 | [phase4-service-decomposition](./2026-05-27-scaling-readiness-phase4-service-decomposition.md) | Extract `DocxRenderer` | ⬜ TODO |
| P4-A3 | [phase4-service-decomposition](./2026-05-27-scaling-readiness-phase4-service-decomposition.md) | Extract `OdtRenderer` | ⬜ TODO |
| P4-A4 | [phase4-service-decomposition](./2026-05-27-scaling-readiness-phase4-service-decomposition.md) | Extract `PdfRenderer` | ⬜ TODO |
| P4-A5 | [phase4-service-decomposition](./2026-05-27-scaling-readiness-phase4-service-decomposition.md) | Update `ResultSheetTemplateService` to delegate to renderers | ⬜ TODO |
| P4-B1 | [phase4-service-decomposition](./2026-05-27-scaling-readiness-phase4-service-decomposition.md) | Extract `ReportExportService` pipe classes (Pipeline pattern) | ⬜ TODO |
| P4-C1 | [phase4-service-decomposition](./2026-05-27-scaling-readiness-phase4-service-decomposition.md) | Extract `DashboardAnalyticsService` query objects | ⬜ TODO |
| CAP-1 | [capstone-dashboard-design](./2026-06-02-capstone-dashboard-design.md) | Update `capstone/README.md` to reflect new structure | ✅ DONE |
| CAP-2 | [capstone-dashboard-design](./2026-06-02-capstone-dashboard-design.md) | Create `capstone/index.html` with interactive portal and Gantt visualization | ✅ DONE |
| CAP-3 | [capstone-dashboard-design](./2026-06-02-capstone-dashboard-design.md) | Remove old `capstone/team_meta/PROJECT_GANTT_CHART.html` | ✅ DONE |
| CEP-1 | [capstone-elevation-plan](../../capstone/research/CONTEXT_UPDATE_June4.md) | Create `FEATURE_VERIFICATION_PROTOCOL.md` (data gathering script) | ✅ DONE |
| CEP-2 | [capstone-elevation-plan](../../capstone/research/CONTEXT_UPDATE_June4.md) | Fix direct contradictions in `RESEARCH_ARGUMENT_BANK.md` | ✅ DONE |
| CEP-3 | [capstone-elevation-plan](../../capstone/research/CONTEXT_UPDATE_June4.md) | Update `C1-11_David_Scope_Delimitations.md` | ✅ DONE |
| CEP-4 | [capstone-elevation-plan](../../capstone/research/CONTEXT_UPDATE_June4.md) | Update `C1-01_David_Background_P1.md` | ✅ DONE |
| CEP-5 | [capstone-elevation-plan](../../capstone/research/CONTEXT_UPDATE_June4.md) | Expand `C1-09_Jaypee_Objectives.md` | ✅ DONE |
| CEP-6 | [capstone-elevation-plan](../../capstone/research/CONTEXT_UPDATE_June4.md) | Update `C2-01_David_Research_Design.md` (two-phase intervention) | ✅ DONE |
| CEP-7 | [capstone-elevation-plan](../../capstone/research/CONTEXT_UPDATE_June4.md) | Update `RESEARCH_ARGUMENT_BANK.md` supporting entries | ✅ DONE |
| CEP-8 | [capstone-elevation-plan](../../capstone/research/CONTEXT_UPDATE_June4.md) | Update `SYSTEM_FEATURES.md` and feature descriptions | ✅ DONE |
| CEP-9 | [capstone-elevation-plan](../../capstone/research/CONTEXT_UPDATE_June4.md) | Create `DEFENSE_EVIDENCE_PLAYBOOK.md` | ✅ DONE |
| CEP-10 | [capstone-elevation-plan](../../capstone/research/CONTEXT_UPDATE_June4.md) | Create `EVALUATION_STRATEGY.md` and `ROLE_MODEL_ANALYSIS.md` | ✅ DONE |
| J5-D1 | [june5_10_execution_plan_v3](../../.gemini/antigravity-cli/brain/7c6dbee5-5894-4622-a4a1-649cc0d110a6/june5_10_execution_plan_v3.md) | David: C2-02 Software Model (AIDLC) — full draft | ✅ DONE |
| J5-D2 | [june5_10_execution_plan_v3](../../.gemini/antigravity-cli/brain/7c6dbee5-5894-4622-a4a1-649cc0d110a6/june5_10_execution_plan_v3.md) | David: Add STEER markers to C1-01 | ✅ DONE |
| J5-D3 | [june5_10_execution_plan_v3](../../.gemini/antigravity-cli/brain/7c6dbee5-5894-4622-a4a1-649cc0d110a6/june5_10_execution_plan_v3.md) | David: Start C1-02 research (pull 5 sources) | ✅ DONE |
| J5-J1 | [june5_10_execution_plan_v3](../../.gemini/antigravity-cli/brain/7c6dbee5-5894-4622-a4a1-649cc0d110a6/june5_10_execution_plan_v3.md) | Jaypee: C1-12 Significance — full draft | ✅ DONE |
| J5-J2 | [june5_10_execution_plan_v3](../../.gemini/antigravity-cli/brain/7c6dbee5-5894-4622-a4a1-649cc0d110a6/june5_10_execution_plan_v3.md) | Jaypee: C1-04 Background P4 Local — draft | ✅ DONE |
| J5-C1 | [june5_10_execution_plan_v3](../../.gemini/antigravity-cli/brain/7c6dbee5-5894-4622-a4a1-649cc0d110a6/june5_10_execution_plan_v3.md) | Christine: C2-04 Project Assignment — full draft | ✅ DONE |
| J5-C2 | [june5_10_execution_plan_v3](../../.gemini/antigravity-cli/brain/7c6dbee5-5894-4622-a4a1-649cc0d110a6/june5_10_execution_plan_v3.md) | Christine: C2-06 Research Instruments — draft | ✅ DONE |
| J5-C3 | [june5_10_execution_plan_v3](../../.gemini/antigravity-cli/brain/7c6dbee5-5894-4622-a4a1-649cc0d110a6/june5_10_execution_plan_v3.md) | Christine: C2-05 Population & Locale — skeleton | ✅ DONE |
