<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Input } from '@/Components/ui/input';
  import QrScanner from '@/Components/QrScanner.svelte';
  import { ArrowLeft, UserCheck, UserX, FileCheck, Play, Square, QrCode } from 'lucide-svelte';
  import axios from 'axios';

  let { session, applicants = [], stats = {} } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const error = $derived($page.props.flash?.error ?? null);

  let searchQuery = $state('');
  let actionError = $state('');
  let showScanner = $state(false);
  let scanMode = $state('attendance'); // 'attendance' | 'submission'
  let scanHandled = $state(false);
  let scanError = $state('');

  const filteredApplicants = $derived(
    searchQuery.trim()
      ? applicants.filter(
          (a) =>
            (a.name ?? '').toLowerCase().includes(searchQuery.toLowerCase()) ||
            (a.reference_number ?? '').toLowerCase().includes(searchQuery.toLowerCase())
        )
      : applicants
  );

  /** Session statuses: draft, published, in_progress, completed, cancelled. Per E-019. */
  function sessionStatusVariant(status) {
    if (status === 'draft') return 'muted';
    if (status === 'published') return 'success';
    if (status === 'in_progress') return 'warning';
    if (status === 'completed') return 'outline';
    if (status === 'cancelled') return 'danger';
    return 'outline';
  }

  /** Attendance/submission statuses: pending, present, absent, submitted. Per E-019. */
  function attendanceStatusVariant(status) {
    if (status === 'pending') return 'muted';
    if (status === 'present' || status === 'submitted') return 'success';
    if (status === 'absent') return 'danger';
    return 'outline';
  }

  function sessionStatusLabel(status) {
    const labels = {
      draft: 'Draft',
      published: 'Published (scheduled)',
      in_progress: 'In progress',
      completed: 'Completed',
      cancelled: 'Cancelled',
    };
    return labels[status] ?? status;
  }

  function handleRosterError(err) {
    actionError = err?.message ?? err ?? 'Something went wrong.';
    setTimeout(() => (actionError = ''), 5000);
  }

  /** Extract reference number from QR content (URL or plain ref). */
  function extractReference(decodedText) {
    const t = String(decodedText ?? '').trim();
    const match = t.match(/\/(?:admission|consultation)\/([^/?#]+)/i);
    if (match) return match[1];
    if (/^APP-\d{4}-\d{5}$/.test(t)) return t;
    return t;
  }

  function handleQrScan(decodedText) {
    if (scanHandled) return;
    scanHandled = true;
    scanError = '';
    const ref = extractReference(decodedText);
    if (!ref) {
      scanError = 'Invalid QR content.';
      scanHandled = false;
      return;
    }
    if (scanMode === 'attendance') {
      axios
        .post(`/proctor/sessions/${session.id}/scan-attendance`, { reference_number: ref })
        .then(() => {
          showScanner = false;
          scanHandled = false;
          scanError = '';
          router.reload();
        })
        .catch((err) => {
          const msg = err?.response?.data?.message ?? err?.message ?? 'Scan failed.';
          scanError = msg;
          setTimeout(() => (scanError = ''), 5000);
          scanHandled = false;
        });
      return;
    }
    // scanMode === 'submission': resolve ref to applicant in this session, then log submission
    const applicant = applicants.find(
      (a) => (a.reference_number ?? '').trim().toLowerCase() === ref.trim().toLowerCase()
    );
    if (!applicant) {
      scanError = 'Applicant not in this session.';
      scanHandled = false;
      return;
    }
    axios
      .post(`/proctor/sessions/${session.id}/submission`, { applicant_id: applicant.id })
      .then(() => {
        showScanner = false;
        scanHandled = false;
        scanError = '';
        router.reload();
      })
      .catch((err) => {
        const msg = err?.response?.data?.message ?? err?.message ?? 'Submission failed.';
        scanError = msg;
        setTimeout(() => (scanError = ''), 5000);
        scanHandled = false;
      });
  }

  function openScannerForAttendance() {
    scanMode = 'attendance';
    scanHandled = false;
    scanError = '';
    showScanner = true;
  }

  function openScannerForSubmission() {
    scanMode = 'submission';
    scanHandled = false;
    scanError = '';
    showScanner = true;
  }

  function closeScanner() {
    showScanner = false;
    scanHandled = false;
    scanError = '';
  }

  function markPresent(applicantId) {
    actionError = '';
    router.post(`/proctor/sessions/${session.id}/attendance`, { applicant_id: applicantId, status: 'present' }, {
      onError: handleRosterError,
      onSuccess: () => router.reload(),
    });
  }

  function markAbsent(applicantId) {
    actionError = '';
    router.post(`/proctor/sessions/${session.id}/attendance`, { applicant_id: applicantId, status: 'absent' }, {
      onError: handleRosterError,
      onSuccess: () => router.reload(),
    });
  }

  function logSubmission(applicantId) {
    actionError = '';
    router.post(`/proctor/sessions/${session.id}/submission`, { applicant_id: applicantId }, {
      onError: handleRosterError,
      onSuccess: () => router.reload(),
    });
  }

  function logSubmissionBulk() {
    actionError = '';
    router.post(`/proctor/sessions/${session.id}/submission-bulk`, {}, {
      onError: handleRosterError,
      onSuccess: () => router.reload(),
    });
  }

  function startSession() {
    actionError = '';
    router.post(`/proctor/sessions/${session.id}/start`, {}, { onError: handleRosterError, onSuccess: () => router.reload() });
  }

  function closeSession() {
    actionError = '';
    router.post(`/proctor/sessions/${session.id}/close`, {}, { onError: handleRosterError, onSuccess: () => router.reload() });
  }

  const canStart = $derived(
    session.status === 'published' &&
    (session.is_within_start_window !== false || session.can_override_schedule === true)
  );
  const canClose = $derived(session.status === 'in_progress');
  const outsideStartWindow = $derived(session.status === 'published' && session.is_within_start_window === false);
  const showAdminOverrideHint = $derived(outsideStartWindow && session.can_override_schedule === true);
  const canMarkAttendance = $derived(
    session.status === 'published' || session.status === 'in_progress'
  );
  const canLogSubmission = $derived(session.status === 'in_progress');
  const canBulkSubmit = $derived(
    session.status === 'in_progress' && (stats.present_pending_submission ?? 0) > 0
  );

  function formatDate(value) {
    if (value == null || value === '') return '—';
    const s = String(value);
    const part = s.split('T')[0];
    if (!part) return '—';
    const [y, m, d] = part.split('-').map(Number);
    const date = new Date(y, (m || 1) - 1, d || 1);
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).replace(',', '');
  }

  function formatTime(value) {
    if (value == null || value === '') return '—';
    const parts = String(value).split(':');
    const hours = parseInt(parts[0], 10) || 0;
    const mins = parseInt(parts[1], 10) || 0;
    const h = hours % 12 || 12;
    const ampm = hours < 12 ? 'AM' : 'PM';
    return `${h}:${String(mins).padStart(2, '0')} ${ampm}`;
  }

  function formatDateTime(isoString) {
    if (isoString == null || isoString === '') return '—';
    try {
      const d = new Date(isoString);
      if (Number.isNaN(d.getTime())) return '—';
      const h = d.getHours() % 12 || 12;
      const m = String(d.getMinutes()).padStart(2, '0');
      const ampm = d.getHours() < 12 ? 'AM' : 'PM';
      return `${h}:${m} ${ampm}`;
    } catch {
      return '—';
    }
  }
</script>

<svelte:head>
  <title>Session Roster - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6">
    <div class="flex items-center gap-4">
      <Link
        href="/admin/exam-sessions"
        class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1"
      >
        <ArrowLeft class="h-4 w-4" /> Back to my sessions
      </Link>
    </div>

    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">{success}</div>
    {/if}
    {#if error || actionError}
      <div class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">{actionError || error}</div>
    {/if}

    <div class="rounded-lg border border-border bg-card p-6">
      <h1 class="text-2xl font-bold">Session Roster</h1>
      <dl class="mt-4 grid gap-3 sm:grid-cols-2">
        <div>
          <dt class="text-sm text-muted-foreground">Date</dt>
          <dd class="font-medium">{formatDate(session.date)}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Time</dt>
          <dd class="font-medium">{formatTime(session.start_time)}{#if session.end_time} – {formatTime(session.end_time)}{/if}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Room</dt>
          <dd class="font-medium">{session.room?.name ?? '—'}</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Status</dt>
          <dd>
            <Badge variant={sessionStatusVariant(session.status)}>{sessionStatusLabel(session.status)}</Badge>
          </dd>
        </div>
        {#if session.started_at}
          <div>
            <dt class="text-sm text-muted-foreground">Started</dt>
            <dd class="font-medium">{formatDateTime(session.started_at)}</dd>
          </div>
        {/if}
        {#if session.closed_at}
          <div>
            <dt class="text-sm text-muted-foreground">Closed</dt>
            <dd class="font-medium">{formatDateTime(session.closed_at)}</dd>
          </div>
        {/if}
      </dl>

      <div class="mt-4 flex flex-wrap gap-3">
        {#if outsideStartWindow && !session.can_override_schedule}
          <p class="text-sm text-muted-foreground">Outside scheduled time. Only an admin can start this session.</p>
        {:else if showAdminOverrideHint}
          <p class="text-sm text-amber-600 dark:text-amber-500">Outside schedule; you have admin override.</p>
        {/if}
        {#if canStart}
          <Button class="min-h-[44px]" onclick={startSession}>
            <Play class="h-4 w-4 mr-2" />
            Start session
          </Button>
        {/if}
        {#if canClose}
          <Button class="min-h-[44px]" variant="outline" onclick={closeSession}>
            <Square class="h-4 w-4 mr-2" />
            Close session
          </Button>
        {/if}
      </div>
    </div>

    <div class="rounded-lg border border-border bg-card p-6">
      <h2 class="text-lg font-semibold">Stats</h2>
      <div class="mt-3 flex flex-wrap gap-4">
        <span class="text-sm text-muted-foreground">Total: <strong class="text-foreground">{stats.total ?? 0}</strong></span>
        <span class="text-sm text-muted-foreground">Present: <strong class="text-foreground">{stats.present ?? 0}</strong></span>
        <span class="text-sm text-muted-foreground">Absent: <strong class="text-foreground">{stats.absent ?? 0}</strong></span>
        <span class="text-sm text-muted-foreground">Pending (attendance): <strong class="text-foreground">{stats.pending ?? 0}</strong></span>
        <span class="text-sm text-muted-foreground">Submitted: <strong class="text-foreground">{stats.submitted ?? 0}</strong></span>
        {#if (stats.present_pending_submission ?? 0) > 0}
          <span class="text-sm text-muted-foreground">Present, not yet submitted: <strong class="text-foreground">{stats.present_pending_submission ?? 0}</strong></span>
        {/if}
      </div>
    </div>

    <div class="rounded-lg border border-border bg-card p-6">
      <h2 class="text-lg font-semibold">Applicants</h2>
      <p class="mt-1 text-sm text-muted-foreground">Mark attendance and log submission. Attendance cannot be changed once set.</p>
      <div class="mt-4 flex flex-wrap items-center gap-3">
        <Input
          type="search"
          placeholder="Search by name or reference..."
          class="max-w-xs"
          bind:value={searchQuery}
          aria-label="Search applicants"
        />
        {#if canMarkAttendance}
          <Button variant="outline" class="min-h-[44px]" onclick={openScannerForAttendance}>
            <QrCode class="h-4 w-4 mr-2" />
            Scan for attendance
          </Button>
        {/if}
        {#if canLogSubmission}
          <Button variant="outline" class="min-h-[44px]" onclick={openScannerForSubmission}>
            <QrCode class="h-4 w-4 mr-2" />
            Scan for submission
          </Button>
        {/if}
        {#if canBulkSubmit}
          <Button variant="outline" class="min-h-[44px]" onclick={logSubmissionBulk}>
            <FileCheck class="h-4 w-4 mr-2" />
            Mark all present as submitted
          </Button>
        {/if}
      </div>
      {#if filteredApplicants.length > 0}
        <div class="mt-4 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-muted/50">
              <tr>
                <th class="px-4 py-3 text-left font-medium">Reference</th>
                <th class="px-4 py-3 text-left font-medium">Name</th>
                <th class="px-4 py-3 text-left font-medium">Attendance</th>
                <th class="px-4 py-3 text-left font-medium">Time in</th>
                <th class="px-4 py-3 text-left font-medium">Submission</th>
                <th class="px-4 py-3 text-left font-medium">Submitted at</th>
                <th class="px-4 py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              {#each filteredApplicants as row (row.id)}
                <tr class="border-t border-border hover:bg-muted/30">
                  <td class="px-4 py-3">{row.reference_number ?? '—'}</td>
                  <td class="px-4 py-3">{row.name ?? '—'}</td>
                  <td class="px-4 py-3">
                    <Badge variant={attendanceStatusVariant(row.attendance_status)}>{row.attendance_status}</Badge>
                  </td>
                  <td class="px-4 py-3">{formatDateTime(row.attendance_marked_at)}</td>
                  <td class="px-4 py-3">
                    <Badge variant={attendanceStatusVariant(row.submission_status)}>{row.submission_status}</Badge>
                  </td>
                  <td class="px-4 py-3">{formatDateTime(row.submitted_at)}</td>
                  <td class="px-4 py-3 text-right">
                    {#if row.attendance_status === 'pending' && canMarkAttendance}
                      <div class="flex flex-wrap justify-end gap-1">
                        <Button
                          variant="outline"
                          size="sm"
                          class="min-h-[44px]"
                          onclick={() => markPresent(row.id)}
                        >
                          <UserCheck class="h-4 w-4 mr-1" />
                          Present
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          class="min-h-[44px] text-destructive hover:text-destructive"
                          onclick={() => markAbsent(row.id)}
                        >
                          <UserX class="h-4 w-4 mr-1" />
                          Absent
                        </Button>
                      </div>
                    {:else if row.attendance_status === 'present' && row.submission_status === 'pending' && canLogSubmission}
                      <Button
                        variant="outline"
                        size="sm"
                        class="min-h-[44px]"
                        onclick={() => logSubmission(row.id)}
                      >
                        <FileCheck class="h-4 w-4 mr-1" />
                        Log submission
                      </Button>
                    {:else}
                      <span class="text-muted-foreground text-xs">—</span>
                    {/if}
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      {:else}
        <p class="mt-4 text-sm text-muted-foreground">
          {searchQuery.trim() ? 'No applicants match your search.' : 'No applicants assigned to this session.'}
        </p>
      {/if}
    </div>
  </div>

  {#if showScanner}
    <QrScanner
      title={scanMode === 'attendance' ? 'Scan for attendance' : 'Scan for submission'}
      onScan={handleQrScan}
      onClose={closeScanner}
    />
    {#if scanError}
      <div class="fixed bottom-4 left-4 right-4 z-[60] rounded-lg bg-destructive/90 px-4 py-3 text-sm text-destructive-foreground sm:left-auto sm:right-4 sm:max-w-sm">
        {scanError}
      </div>
    {/if}
  {/if}
</AuthenticatedLayout>
