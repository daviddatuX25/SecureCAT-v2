# Design Specification: Live Exam Monitoring Dashboard

**Date**: 2026-05-20  
**Status**: APPROVED  
**Author**: Antigravity  

---

## 1. Objective

Enhance the exam monitoring page (`/admin/test-scheduling/monitoring` / `/admin/exam-monitoring`) to provide actionable, real-time insights during active testing sessions. This system must accurately reflect live student check-ins, testing progress, time remaining, proctor presence, and room capacity.

The design aligns with the premium visual aesthetics of the project, including dynamic KPI cards, glassmorphic accents, progress tracking, and soft-pulsing indicator badges.

---

## 2. Key Insights & Bug Fixes

1. **Live Data Restoration**: Active student counts (`total`, `present`, `submitted`) are currently hardcoded to fallback `0`s in `Monitoring.svelte` because the backend `ExamSessionController@monitoring` did not load them. We will fix this using efficient `withCount()` subqueries.
2. **KPI Stats Panels**: Add reactive summary blocks at the top of the monitoring dashboard representing aggregated stats across all active rooms.
3. **Dual Progress Indicators**: 
   - **Student Completion Progress**: A visual bar showing the percentage of checked-in students who have successfully submitted their exams.
   - **Time Elapsed Progress**: A visual timeline showing duration status. If a room exceeds its allotted/extended testing window, highlight it with a pulsing warning "Overtime" badge.
4. **Proctor and Space Context**: List assigned proctors as small badge tags and track room seat utilization against building/room capacity.

---

## 3. Architecture & Data Model

### 3.1 Backend: Controller Query Enhancement

We will modify `app/Http/Controllers/Admin/ExamSessionController.php@monitoring` to fetch live metrics in a single database round-trip:

```php
$query = ExamSession::query()
    ->with([
        'room:id,name,building,capacity',
        'proctors:id,name'
    ])
    ->withCount([
        'applicants as total',
        'applicants as present' => function ($q) {
            $q->where('exam_session_applicant.attendance_status', 'present');
        },
        'applicants as absent' => function ($q) {
            $q->where('exam_session_applicant.attendance_status', 'absent');
        },
        'applicants as submitted' => function ($q) {
            $q->where('exam_session_applicant.submission_status', 'submitted');
        }
    ])
    ->whereIn('status', [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS])
    ->orderBy('date')
    ->orderBy('start_time');
```

### 3.2 Frontend: Svelte 5 Reactive State (`Monitoring.svelte`)

Using Svelte 5's `$derived` runes, we will aggregate overall stats from the `sessions` array automatically:

* `totalSessions = $derived(sessions.length)`
* `totalPresent = $derived(sessions.reduce((acc, s) => acc + (s.present_count ?? 0), 0))`
* `totalExpected = $derived(sessions.reduce((acc, s) => acc + (s.total_count ?? 0), 0))`
* `totalSubmitted = $derived(sessions.reduce((acc, s) => acc + (s.submitted_count ?? 0), 0))`
* `attendanceRate = $derived(totalExpected > 0 ? Math.round((totalPresent / totalExpected) * 100) : 0)`
* `completionRate = $derived(totalPresent > 0 ? Math.round((totalSubmitted / totalPresent) * 100) : 0)`

---

## 4. UI/UX Interface Design

### 4.1 Live Header
A custom banner with a soft-pulsing green circle icon representing live web-sockets or auto-refresh (15 seconds reload):
```html
<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
  <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
  Live Monitoring
</span>
```

### 4.2 Top-level KPI Panels
Create a 4-column KPI section at the top of the view:
* **Active Rooms**: count of in-progress sessions.
* **Testing Live**: count of present students.
* **Attendance Rate**: checked-in vs expected percentage.
* **Completion Rate**: completed vs checked-in percentage.

### 4.3 Interactive Table & Grid Layouts
- **Time Progress Calculation**: Calculate current duration elapsed.
  $$\text{Elapsed \%} = \frac{\text{Current Time} - \text{Started At}}{\text{Total Session Duration}}$$
  If `currentTime > endTime`, display a soft warning "Overtime" badge.
- **Seat Capacity Utilisation**: Comparison badge, e.g., `present / room.capacity`.
- **Proctors Tag List**: Styled badges using small font tags for rapid reference.

---

## 5. Verification Plan

- **Feature Test Integration**: Run `ExamSessionConflictTest.php` and verify backend routes.
- **Real-Time Simulation**: Verify that the Svelte front-end correctly computes sums and handles empty session lists without throwing console errors.
