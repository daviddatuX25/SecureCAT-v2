<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, useForm } from '@inertiajs/svelte';
  import { usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Input } from '@/Components/ui/input';
  import { ArrowLeft, UserPlus, UserMinus, Send, ClipboardList, RotateCcw } from 'lucide-svelte';

  let { session, assigned_applicants = [], available_applicants = [], proctors = [], view = 'admin' } = $props();
  const isProctorView = $derived(view === 'proctor');

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const error = $derived($page.props.flash?.error ?? null);

  let selectedAvailable = $state([]);

  function dateToYmd(value) {
    if (value == null || value === '') return '';
    const s = String(value);
    const part = s.split('T')[0];
    return part || '';
  }

  function formatDate(value) {
    if (value == null || value === '') return '—';
    const part = dateToYmd(value);
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

  const releaseDateForm = useForm({
    score_release_date: dateToYmd(session.score_release_date) || '',
  });

  function statusVariant(status) {
    if (status === 'draft') return 'muted';
    if (status === 'published') return 'success';
    if (status === 'in_progress') return 'warning';
    if (status === 'completed') return 'outline';
    if (status === 'cancelled') return 'danger';
    return 'outline';
  }

  function toggleAvailable(id) {
    if (selectedAvailable.includes(id)) {
      selectedAvailable = selectedAvailable.filter((x) => x !== id);
    } else {
      selectedAvailable = [...selectedAvailable, id];
    }
  }

  function assignSelected() {
    if (selectedAvailable.length === 0) return;
    router.post(`/admin/exam-sessions/${session.id}/assign-applicants`, { applicant_ids: selectedAvailable }, {
      onSuccess: () => (selectedAvailable = []),
    });
  }

  function removeAssigned(rowOrId) {
    const sessionApplicantId = typeof rowOrId === 'object' && rowOrId !== null ? rowOrId.session_applicant_id : rowOrId;
    router.post(`/admin/exam-sessions/${session.id}/remove-applicant`, { session_applicant_id: sessionApplicantId });
  }

  function publish() {
    router.post(`/admin/exam-sessions/${session.id}/publish`, {}, { onSuccess: () => router.reload() });
  }

  function unpublish() {
    router.post(`/admin/exam-sessions/${session.id}/unpublish`, {}, { onSuccess: () => router.reload() });
  }

  function reopenSession() {
    router.post(`/admin/exam-sessions/${session.id}/reopen`, {}, { onSuccess: () => router.reload() });
  }

  function submitReleaseDate(e) {
    e.preventDefault();
    releaseDateForm.transform((data) => data);
    $releaseDateForm.put(`/admin/exam-sessions/${session.id}/release-date`);
  }
</script>

<svelte:head>
  <title>Exam Session - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6">
    <div class="flex items-center gap-4">
      <Link href="/admin/exam-sessions" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
        <ArrowLeft class="h-4 w-4" /> {isProctorView ? 'Back to my sessions' : 'Back to exam sessions'}
      </Link>
    </div>

    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">
        {success}
      </div>
    {/if}
    {#if error}
      <div class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">
        {error}
      </div>
    {/if}

    <div class="rounded-lg border border-border bg-card p-6">
      <h1 class="text-2xl font-bold">Exam Session</h1>
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
          <dd class="font-medium">{session.room?.name ?? '—'} (cap. {session.room?.capacity ?? '—'})</dd>
        </div>
        <div>
          <dt class="text-sm text-muted-foreground">Status</dt>
          <dd>
            <Badge variant={statusVariant(session.status)}>{session.status}</Badge>
          </dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-sm text-muted-foreground">Proctors</dt>
          <dd class="font-medium">{(session.proctors ?? []).map((p) => p.name).join(', ') || '—'}</dd>
        </div>
      </dl>
      {#if isProctorView && (session.status === 'published' || session.status === 'in_progress')}
        <div class="mt-4 pt-4 border-t border-border">
          <Link
            href="/proctor/sessions/{session.id}"
            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 min-h-[44px]"
          >
            <ClipboardList class="h-4 w-4" />
            Open roster
          </Link>
        </div>
      {/if}
      {#if !isProctorView && session.status === 'completed'}
        <div class="mt-4 pt-4 border-t border-border">
          <Button class="min-h-[44px]" variant="outline" onclick={reopenSession}>
            <RotateCcw class="h-4 w-4 mr-2" />
            Reopen session
          </Button>
          <p class="mt-2 text-sm text-muted-foreground">Reopen so proctors can continue marking attendance or submissions (e.g. late examinee).</p>
        </div>
      {/if}
    </div>

    <!-- Assigned applicants (admin only) -->
    {#if !isProctorView}
    <div class="rounded-lg border border-border bg-card p-6">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-lg font-semibold">Assigned applicants</h2>
          <p class="mt-1 text-sm text-muted-foreground">Applicants assigned to this session. Remove to unassign.</p>
        </div>
      </div>
      {#if (assigned_applicants ?? []).length > 0}
        <div class="mt-4 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-muted/50">
              <tr>
                <th class="px-4 py-3 text-left font-medium">Reference</th>
                <th class="px-4 py-3 text-left font-medium">Name</th>
                <th class="px-4 py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              {#each assigned_applicants as row (row.session_applicant_id)}
                <tr class="border-t border-border hover:bg-muted/30">
                  <td class="px-4 py-3">{row.reference_number}</td>
                  <td class="px-4 py-3">{row.name}</td>
                  <td class="px-4 py-3 text-right">
                    <Button
                      variant="ghost"
                      size="sm"
                      class="text-destructive hover:text-destructive min-h-[44px]"
                      onclick={() => removeAssigned(row)}
                    >
                      <UserMinus class="h-4 w-4 mr-1" />
                      Remove
                    </Button>
                  </td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
      {:else}
        <p class="mt-4 text-sm text-muted-foreground">No applicants assigned yet. Select from available below and click Assign.</p>
      {/if}
    </div>

    <!-- Available applicants (bulk assign) -->
    <div class="rounded-lg border border-border bg-card p-6">
      <h2 class="text-lg font-semibold">Available applicants</h2>
      <p class="mt-1 text-sm text-muted-foreground">Accepted applicants not yet assigned to a session. Select and assign.</p>
      {#if (available_applicants ?? []).length > 0}
        <div class="mt-4 overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-muted/50">
              <tr>
                <th class="px-4 py-3 text-left w-12">
                  <span class="sr-only">Select</span>
                </th>
                <th class="px-4 py-3 text-left font-medium">Reference</th>
                <th class="px-4 py-3 text-left font-medium">Name</th>
              </tr>
            </thead>
            <tbody>
              {#each available_applicants as app (app.id)}
                <tr class="border-t border-border hover:bg-muted/30">
                  <td class="px-4 py-3">
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-input accent-primary"
                      checked={selectedAvailable.includes(app.id)}
                      onchange={() => toggleAvailable(app.id)}
                      aria-label="Select {app.name}"
                    />
                  </td>
                  <td class="px-4 py-3">{app.reference_number}</td>
                  <td class="px-4 py-3">{app.name}</td>
                </tr>
              {/each}
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <Button
            class="min-h-[44px]"
            disabled={selectedAvailable.length === 0}
            onclick={assignSelected}
          >
            <UserPlus class="h-4 w-4 mr-2" />
            Assign selected ({selectedAvailable.length})
          </Button>
        </div>
      {:else}
        <p class="mt-4 text-sm text-muted-foreground">No available applicants, or all accepted applicants are already assigned.</p>
      {/if}
    </div>
    {/if}

    <!-- Publish & release date (admin only) - hidden when completed, cancelled, or in_progress (E-002, E-003) -->
    {#if !isProctorView && !['completed', 'cancelled', 'in_progress'].includes(session.status)}
    <div class="rounded-lg border border-border bg-card p-6">
      <h2 class="text-lg font-semibold">Schedule actions</h2>
      <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:gap-6">
        <div>
          <Button
            class="min-h-[44px]"
            variant={session.status === 'published' ? 'destructive' : 'default'}
            onclick={session.status === 'published' ? unpublish : publish}
          >
            <Send class="h-4 w-4 mr-2" />
            {session.status === 'published' ? 'Unpublish' : 'Publish session'}
          </Button>
          <p class="mt-1 text-xs text-muted-foreground">Notify assigned applicants and lock schedule.</p>
        </div>
        <form onsubmit={submitReleaseDate} class="flex flex-col gap-2 sm:flex-row sm:items-end">
          <div class="space-y-1">
            <label for="score_release_date" class="text-sm font-medium">Score release date</label>
            <Input
              id="score_release_date"
              type="date"
              bind:value={$releaseDateForm.score_release_date}
            />
          </div>
          <Button type="submit" variant="outline" class="min-h-[44px]" disabled={$releaseDateForm.processing}>
            {$releaseDateForm.processing ? 'Saving...' : 'Set release date'}
          </Button>
        </form>
      </div>
    </div>
    {/if}
  </div>
</AuthenticatedLayout>
