# Support Exam Session Backtracking to Completed State Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Implement a backtracking feature that allows an administrator to assign applicants to a session, mark them as present and submitted, and immediately complete the session. This transitions the applicants to `submitted` status and the session to `completed` status, allowing the session to go straight to grading.

**Architecture:** 
1. Update `AssignApplicantsRequest` to allow a `backtrack` boolean parameter.
2. In `ExamSessionController@assignApplicants`, if `backtrack` is true, perform the assignment, bulk-update all assigned applicants' attendance and submission statuses to present and submitted, transition their pipeline status to `submitted`, and update the session status to `completed`.
3. Add a dedicated `backtrack` POST route `/admin/exam-scheduling/{exam_session}/backtrack` to allow backtracking an already-assigned session.
4. Update `Show.svelte` to include a backtrack checkbox under the bulk assignment section, and a "Backtrack Session" button under schedule actions for already-assigned draft/published sessions.

**Tech Stack:** PHP, Laravel 12, Svelte 5, Inertia.js v2

---

### Task 1: Update AssignApplicantsRequest Validation
- Modify: `app/Http/Requests/AssignApplicantsRequest.php`
- Test: `tests/Feature/Admin/ExamSessionAssignNotificationTest.php`

**Step 1: Write the failing test**
In `tests/Feature/Admin/ExamSessionAssignNotificationTest.php`, add a test to verify validation accepts `backtrack`:
```php
    public function test_assign_request_accepts_backtrack_parameter(): void
    {
        $session = $this->createSession(['status' => ExamSession::STATUS_DRAFT]);
        $applicant = $this->createApplicant();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.assign-applicants', $session), [
                'applicant_ids' => [$applicant->id],
                'backtrack' => true,
            ]);

        $response->assertSessionHasNoErrors();
    }
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=test_assign_request_accepts_backtrack_parameter`
Expected: Fails or ignores the parameter because it's not in the request rules.

**Step 3: Write minimal impl**
Update `rules()` in `app/Http/Requests/AssignApplicantsRequest.php` to include:
```php
            'backtrack' => ['nullable', 'boolean'],
```

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=test_assign_request_accepts_backtrack_parameter`

**Step 5: Commit**
```bash
git add app/Http/Requests/AssignApplicantsRequest.php tests/Feature/Admin/ExamSessionAssignNotificationTest.php
git commit -m "feat: validate backtrack parameter in AssignApplicantsRequest"
```

---

### Task 2: Implement Backtracking Logic in Controller
- Modify: `app/Http/Controllers/Admin/ExamSessionController.php`
- Test: `tests/Feature/Admin/ExamSessionAssignNotificationTest.php`

**Step 1: Write the failing test**
In `tests/Feature/Admin/ExamSessionAssignNotificationTest.php`, add:
```php
    public function test_assign_with_backtrack_immediately_completes_session_and_submits_applicants(): void
    {
        $session = $this->createSession(['status' => ExamSession::STATUS_DRAFT]);
        $applicant = $this->createApplicant();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.assign-applicants', $session), [
                'applicant_ids' => [$applicant->id],
                'backtrack' => true,
            ]);

        $response->assertRedirect(route('admin.exam-scheduling.show', $session));
        
        $session->refresh();
        $this->assertEquals(ExamSession::STATUS_COMPLETED, $session->status);
        $this->assertNotNull($session->closed_at);

        $pivot = \Illuminate\Support\Facades\DB::table('exam_session_applicant')
            ->where('exam_session_id', $session->id)
            ->where('applicant_id', $applicant->id)
            ->first();

        $this->assertEquals('present', $pivot->attendance_status);
        $this->assertEquals('submitted', $pivot->submission_status);
        
        $applicant->refresh();
        $this->assertEquals('submitted', $applicant->application->pipeline_status);
    }
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=test_assign_with_backtrack_immediately_completes_session_and_submits_applicants`
Expected: FAIL (session status is still draft, applicant not present/submitted).

**Step 3: Write minimal impl**
In `assignApplicants` method in `app/Http/Controllers/Admin/ExamSessionController.php`:
```php
        $backtrack = (bool) $request->validated('backtrack');

        $applicantIds = array_values(array_unique(array_map('intval', $request->validated('applicant_ids'))));
        $alreadyAttached = $exam_session->applicants()->whereIn('applicants.id', $applicantIds)->pluck('applicants.id')->all();
        $toAttach = array_diff($applicantIds, $alreadyAttached);
        if (! empty($toAttach)) {
            $exam_session->applicants()->attach($toAttach);

            $pipeline = app(ApplicationPipelineService::class);

            if ($backtrack) {
                // Bulk update the pivot records for these newly assigned applicants to present and submitted
                \Illuminate\Support\Facades\DB::table('exam_session_applicant')
                    ->where('exam_session_id', $exam_session->id)
                    ->whereIn('applicant_id', $toAttach)
                    ->update([
                        'attendance_status' => 'present',
                        'attendance_marked_at' => now(),
                        'attendance_marked_by' => $request->user()->id,
                        'submission_status' => 'submitted',
                        'submitted_at' => now(),
                        'submitted_to' => $request->user()->id,
                        'updated_at' => now(),
                    ]);

                // Transition through scheduled -> attended -> submitted to record complete milestones
                $newApplicants = Applicant::whereIn('id', $toAttach)->with('application')->get();
                $newApplicants->each(function (Applicant $applicant) use ($pipeline, $exam_session) {
                    if ($applicant->application) {
                        $pipeline->transition($applicant->application, 'scheduled', ['session_id' => $exam_session->id]);
                        $pipeline->transition($applicant->application, 'attended', ['session_id' => $exam_session->id]);
                        $pipeline->transition($applicant->application, 'submitted', ['session_id' => $exam_session->id]);
                    }
                });
            } else {
                // Pipeline hook: newly assigned applicants advance to draft_scheduled or scheduled
                $targetStatus = $exam_session->status === ExamSession::STATUS_DRAFT
                    ? 'draft_scheduled'
                    : 'scheduled';
                $newApplicants = Applicant::whereIn('id', $toAttach)
                    ->with('application')
                    ->get();

                $newApplicants->each(function (Applicant $applicant) use ($pipeline, $targetStatus, $exam_session) {
                    if ($applicant->application) {
                        $pipeline->transition($applicant->application, $targetStatus, [
                            'session_id' => $exam_session->id,
                        ]);
                    }
                });

                // Notify late-assigned applicants when session is already published or in-progress.
                if (in_array($exam_session->status, [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS], true)) {
                    $exam_session->load('room');
                    $newApplicants->each(
                        fn (Applicant $applicant) => $applicant->notify(new ExamSessionPublished($exam_session))
                    );
                }
            }
        }

        if ($backtrack) {
            $exam_session->update([
                'status' => ExamSession::STATUS_COMPLETED,
                'closed_at' => now(),
            ]);

            app(AuditService::class)->log('exam_session.completed', ExamSession::class, $exam_session->id);
        }

        if (! empty($toAttach)) {
            app(AuditService::class)->log('exam_session.applicants_assigned', ExamSession::class, $exam_session->id, [], [
                'applicant_count' => count($toAttach),
                'backtrack' => $backtrack,
            ]);
        }
```

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=test_assign_with_backtrack_immediately_completes_session_and_submits_applicants`

**Step 5: Commit**
```bash
git add app/Http/Controllers/Admin/ExamSessionController.php tests/Feature/Admin/ExamSessionAssignNotificationTest.php
git commit -m "feat: support backtracking assigned applicants on ExamSessionController"
```

---

### Task 3: Support Direct Backtrack Endpoint
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Admin/ExamSessionController.php`
- Modify: `app/Policies/ExamSessionPolicy.php`
- Test: `tests/Feature/Admin/ExamSessionAssignNotificationTest.php`

**Step 1: Write the failing test**
In `tests/Feature/Admin/ExamSessionAssignNotificationTest.php`, add:
```php
    public function test_direct_backtrack_endpoint_completes_session_and_submits_existing_applicants(): void
    {
        $session = $this->createSession(['status' => ExamSession::STATUS_PUBLISHED]);
        $applicant = $this->createApplicant();
        $session->applicants()->attach($applicant->id);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.backtrack', $session));

        $response->assertRedirect(route('admin.exam-scheduling.show', $session));

        $session->refresh();
        $this->assertEquals(ExamSession::STATUS_COMPLETED, $session->status);

        $pivot = \Illuminate\Support\Facades\DB::table('exam_session_applicant')
            ->where('exam_session_id', $session->id)
            ->where('applicant_id', $applicant->id)
            ->first();

        $this->assertEquals('present', $pivot->attendance_status);
        $this->assertEquals('submitted', $pivot->submission_status);

        $applicant->refresh();
        $this->assertEquals('submitted', $applicant->application->pipeline_status);
    }
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=test_direct_backtrack_endpoint_completes_session_and_submits_existing_applicants`
Expected: FAIL (route or action not defined).

**Step 3: Write minimal impl**
1. Add route in `routes/web.php` in the `admin` named group (e.g. line 178):
```php
        Route::post('exam-scheduling/{exam_session}/backtrack', [ExamSessionController::class, 'backtrack'])->name('exam-scheduling.backtrack');
```

2. Add policy check in `app/Policies/ExamSessionPolicy.php`:
```php
    public function backtrack(User $user, ExamSession $examSession): bool
    {
        if (in_array($examSession->status, [ExamSession::STATUS_COMPLETED, ExamSession::STATUS_CANCELLED], true)) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
    }
```

3. Add `backtrack` method in `app/Http/Controllers/Admin/ExamSessionController.php`:
```php
    public function backtrack(ExamSession $exam_session): RedirectResponse
    {
        $this->authorize('backtrack', $exam_session);

        if ($exam_session->applicants()->count() === 0) {
            return redirect()->route('admin.exam-scheduling.show', $exam_session)
                ->with('error', 'Cannot backtrack a session with no applicants assigned.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($exam_session) {
            \Illuminate\Support\Facades\DB::table('exam_session_applicant')
                ->where('exam_session_id', $exam_session->id)
                ->update([
                    'attendance_status' => 'present',
                    'attendance_marked_at' => now(),
                    'attendance_marked_by' => auth()->id(),
                    'submission_status' => 'submitted',
                    'submitted_at' => now(),
                    'submitted_to' => auth()->id(),
                    'updated_at' => now(),
                ]);

            $pipeline = app(ApplicationPipelineService::class);
            $exam_session->applicants()->with('application')->get()->each(function (Applicant $applicant) use ($pipeline, $exam_session) {
                if ($applicant->application) {
                    $pipeline->transition($applicant->application, 'scheduled', ['session_id' => $exam_session->id]);
                    $pipeline->transition($applicant->application, 'attended', ['session_id' => $exam_session->id]);
                    $pipeline->transition($applicant->application, 'submitted', ['session_id' => $exam_session->id]);
                }
            });

            $exam_session->update([
                'status' => ExamSession::STATUS_COMPLETED,
                'closed_at' => now(),
            ]);

            app(AuditService::class)->log('exam_session.completed', ExamSession::class, $exam_session->id);
        });

        return redirect()->route('admin.exam-scheduling.show', $exam_session)
            ->with('success', 'Session completed and applicants marked as submitted.');
    }
```

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=test_direct_backtrack_endpoint_completes_session_and_submits_existing_applicants`

**Step 5: Run full test suite for exam scheduling/publish to verify no regressions**
Run: `php artisan test --filter=ExamSession`

**Step 6: Commit**
```bash
git add routes/web.php app/Http/Controllers/Admin/ExamSessionController.php app/Policies/ExamSessionPolicy.php tests/Feature/Admin/ExamSessionAssignNotificationTest.php
git commit -m "feat: implement backtrack endpoint and policy"
```

---

### Task 4: Add Backtrack Options to Frontend UI
- Modify: `resources/js/Pages/Admin/TestScheduling/Show.svelte`

**Step 1: Implement the UI Changes**
1. Add `backtrack` reactive state to the script:
```javascript
  let backtrack = $state(false);
```

2. Update `assignSelected` to pass the `backtrack` parameter:
```javascript
  function assignSelected() {
    if (selectedAvailable.length === 0) return;
    router.post(`/admin/exam-scheduling/${session.id}/assign-applicants`, {
      applicant_ids: selectedAvailable,
      backtrack: backtrack
    }, {
      onSuccess: () => {
        selectedAvailable = [];
        backtrack = false;
      },
    });
  }
```

3. Add backtrack checkbox next to the "Assign selected" button inside the "Available applicants" card (around line 230):
```svelte
        <div class="mt-3 flex flex-wrap items-center gap-4">
          <Button
            class="min-h-[44px]"
            disabled={selectedAvailable.length === 0}
            onclick={assignSelected}
          >
            <UserPlus class="h-4 w-4 mr-2" />
            Assign selected ({selectedAvailable.length})
          </Button>

          <label class="flex items-center gap-2 text-sm select-none cursor-pointer">
            <input
              type="checkbox"
              class="h-4 w-4 rounded border-input accent-primary"
              bind:checked={backtrack}
            />
            <span>Backtrack (mark present/submitted and complete session immediately)</span>
          </label>
        </div>
```

4. Add the direct "Backtrack Session" button in the "Schedule actions" card (around line 250):
```svelte
          {#if session.status !== 'completed' && assigned_applicants.length > 0}
            <Button
              class="min-h-[44px] ml-2"
              variant="outline"
              onclick={() => {
                if (confirm('Are you sure you want to backtrack this session? All assigned applicants will be marked as present/submitted, and the session will be marked as completed.')) {
                  router.post(`/admin/exam-scheduling/${session.id}/backtrack`, {}, { onSuccess: () => router.reload() });
                }
              }}
            >
              <ClipboardList class="h-4 w-4 mr-2" />
              Backtrack Session
            </Button>
          {/if}
```

**Step 2: Format changes with Pint**
Run: `vendor/bin/pint --dirty --format agent`

**Step 3: Verify build**
Run: `npm run build` or verify there are no compilation errors.

**Step 4: Commit**
```bash
git add resources/js/Pages/Admin/TestScheduling/Show.svelte
git commit -m "feat: expose backtrack options on exam session show page"
```
