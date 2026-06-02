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
