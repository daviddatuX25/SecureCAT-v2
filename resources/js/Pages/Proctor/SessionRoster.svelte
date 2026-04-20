<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Input } from '@/Components/ui/input';
  import * as Table from '@/Components/ui/table';
  import { ArrowLeft, UserCheck, UserX, FileCheck, Play, Square } from 'lucide-svelte';
  import { success as showSuccess, error as showError } from '@/lib/toast';
  import { onMount } from 'svelte';

  let { session, applicants = [], stats = {} } = $props();

  const page = usePage();

  // Show toasts on mount for flash messages
  onMount(() => {
    const flash = $page.props.flash;
    if (flash?.success) showSuccess(flash.success);
    if (flash?.error) showError(flash.error);
  });

  // Show toast when exam window has ended (once on initial load)
  $effect(() => {
    if (session?.is_past_end && !window._examWindowEndedToast) {
      window._examWindowEndedToast = true;
      showError('Exam window ended. Actions locked.');
    }
  });

  let searchQuery = $state('');
  let actionError = $state('');

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
    (session.status === 'published' || session.status === 'in_progress')
    && !session.is_past_end
  );
  const canLogSubmission = $derived(
    session.status === 'in_progress' && !session.is_past_end
  );
  const canBulkSubmit = $derived(
    session.status === 'in_progress' && !session.is_past_end && (stats.present_pending_submission ?? 0) > 0
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

  const breadcrumbs = $derived([
    { label: 'My Sessions', href: '/admin/exam-scheduling?view=proctor' },
    { label: session?.id ? 'Session #' + session.id : 'Session' }
  ]);
</script>

<svelte:head>
  <title>Session Roster - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="space-y-3">
    <!-- Session Header -->
    <div class="rounded-lg border border-border bg-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-lg font-bold">Session #{session.id}</h1>
          <p class="mt-1 text-sm text-muted-foreground">
            {formatDate(session.date)} · {formatTime(session.start_time)}{#if session.end_time} - {formatTime(session.end_time)}{/if} · {session.room?.name ?? '-'}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Badge variant={sessionStatusVariant(session.status)}>{sessionStatusLabel(session.status)}</Badge>
          {#if canStart}
            <Button size="sm" onclick={startSession}>
              <Play class="h-4 w-4 mr-1" />
              Start
            </Button>
          {/if}
          {#if canClose}
            <Button size="sm" variant="outline" onclick={closeSession}>
              <Square class="h-4 w-4 mr-1" />
              Close
            </Button>
          {/if}
        </div>
      </div>
      {#if outsideStartWindow && !session.can_override_schedule}
        <p class="mt-2 text-sm text-muted-foreground">Outside scheduled time window</p>
      {/if}
    </div>

    <!-- Stats -->
    <div class="rounded-lg border border-border bg-card p-4">
      <div class="flex flex-wrap gap-4 text-sm">
        <div><span class="text-muted-foreground">Total:</span> <strong>{stats.total ?? 0}</strong></div>
        <div><span class="text-muted-foreground">Present:</span> <strong>{stats.present ?? 0}</strong></div>
        <div><span class="text-muted-foreground">Absent:</span> <strong>{stats.absent ?? 0}</strong></div>
        <div><span class="text-muted-foreground">Pending:</span> <strong>{stats.pending ?? 0}</strong></div>
        <div><span class="text-muted-foreground">Submitted:</span> <strong>{stats.submitted ?? 0}</strong></div>
      </div>
    </div>

    <!-- Applicants -->
    <div class="rounded-lg border border-border bg-card p-4">
      <div class="flex flex-wrap items-center gap-3">
        <h2 class="text-base font-semibold">Applicants</h2>
        <Input
          type="search"
          placeholder="Search by name or reference..."
          class="h-9 max-w-xs"
          bind:value={searchQuery}
          aria-label="Search applicants"
        />
        {#if canBulkSubmit}
          <Button size="sm" variant="outline" onclick={logSubmissionBulk}>
            <FileCheck class="h-4 w-4 mr-1" />
            Mark all submitted
          </Button>
        {/if}
      </div>
      {#if session.is_past_end}
        <p class="mt-3 text-sm text-destructive">Exam window ended. Actions locked.</p>
      {/if}
      {#if filteredApplicants.length > 0}
        <div class="overflow-x-auto">
          <Table.Root class="w-full">
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="px-4 py-3">Reference</Table.Head>
                <Table.Head class="px-4 py-3">Name</Table.Head>
                <Table.Head class="px-4 py-3">Attendance</Table.Head>
                <Table.Head class="px-4 py-3">Time in</Table.Head>
                <Table.Head class="px-4 py-3">Submission</Table.Head>
                <Table.Head class="px-4 py-3">Submitted at</Table.Head>
                <Table.Head class="text-center px-4 py-3">Action</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each filteredApplicants as row (row.id)}
                <Table.Row>
                  <Table.Cell class="px-4 py-3">{row.reference_number ?? '-'}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{row.name ?? '-'}</Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Badge variant={attendanceStatusVariant(row.attendance_status)}>{row.attendance_status}</Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">{formatDateTime(row.attendance_marked_at)}</Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Badge variant={attendanceStatusVariant(row.submission_status)}>{row.submission_status}</Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">{formatDateTime(row.submitted_at)}</Table.Cell>
                  <Table.Cell class="px-4 py-3 text-center">
                    {#if row.attendance_status === 'pending' && canMarkAttendance}
                      <div class="flex justify-center gap-1">
                        <Button size="sm" variant="outline" onclick={() => markPresent(row.id)}>Present</Button>
                        <Button size="sm" variant="outline" onclick={() => markAbsent(row.id)}>Absent</Button>
                      </div>
                    {:else if row.attendance_status === 'present' && row.submission_status === 'pending' && canLogSubmission}
                      <Button size="sm" variant="outline" onclick={() => logSubmission(row.id)}>Submit</Button>
                    {:else}
                      <span class="text-muted-foreground">-</span>
                    {/if}
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>
      {:else}
        <p class="mt-3 text-sm text-muted-foreground">
          {searchQuery.trim() ? 'No applicants match your search.' : 'No applicants assigned.'}
        </p>
      {/if}
    </div>
  </div>
</AuthenticatedLayout>
