<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import * as Table from '@/Components/ui/table';

  let { summaries, release_mode = 'online', courses = [] } = $props();

  const page = usePage();
  const flash = $derived($page.props.flash ?? {});
  const breadcrumbs = [{ label: 'Release Management' }];

  // Track selected summary IDs for bulk release
  let selectedIds = $state([]);

  const allSelected = $derived(
    summaries.data.length > 0 &&
    summaries.data
      .filter((s) => s.status !== 'released')
      .every((s) => selectedIds.includes(s.id))
  );

  function toggleAll() {
    const unreleasedIds = summaries.data
      .filter((s) => s.status !== 'released')
      .map((s) => s.id);
    if (allSelected) {
      selectedIds = [];
    } else {
      selectedIds = unreleasedIds;
    }
  }

  function toggleOne(id) {
    if (selectedIds.includes(id)) {
      selectedIds = selectedIds.filter((i) => i !== id);
    } else {
      selectedIds = [...selectedIds, id];
    }
  }

  function releaseOne(summaryId) {
    router.post(`/release/summaries/${summaryId}/release`, {}, { preserveScroll: true });
  }

  function releaseBulk() {
    if (selectedIds.length === 0) return;
    router.post('/release/summaries/bulk-release', { ids: selectedIds }, {
      preserveScroll: true,
      onSuccess: () => { selectedIds = []; },
    });
  }

  function statusVariant(status) {
    if (status === 'released') return 'success';
    if (status === 'draft') return 'secondary';
    return 'muted';
  }

  // Edit side panel state
  let selectedSummary = $state(null);
  let showPanel = $state(false);
  let saving = $state(false);
  let panelErrors = $state('');
  let recCourseId = $state('');
  let counselorComments = $state('');

  function openPanel(summary) {
    selectedSummary = summary;
    recCourseId = summary.recommended_course?.id ?? '';
    counselorComments = summary.counselor_comments ?? '';
    showPanel = true;
    panelErrors = '';
  }

  function closePanel() {
    showPanel = false;
    selectedSummary = null;
    recCourseId = '';
    counselorComments = '';
    panelErrors = '';
  }

  function saveSummary() {
    saving = true;
    panelErrors = '';
    const applicantId = selectedSummary.applicant?.id ?? selectedSummary.applicant_id;
    router.put(`/release/summaries/by-applicant/${applicantId}`, {
      recommended_course_id: recCourseId || null,
      counselor_comments: counselorComments || null,
    }, {
      preserveScroll: true,
      onError: (err) => {
        panelErrors = Object.values(err).flat().join(', ');
        saving = false;
      },
      onSuccess: () => {
        saving = false;
        closePanel();
      },
    });
  }

  function getCoursePreferences(summary) {
    const app = summary.applicant?.application;
    if (!app) return [];
    return [
      app.coursePreference1,
      app.coursePreference2,
      app.coursePreference3,
    ].filter(Boolean);
  }

  const panelPrefs = $derived(selectedSummary ? getCoursePreferences(selectedSummary) : []);
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">

    <!-- Mode banner -->
    {#if release_mode === 'f2f'}
      <div class="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm">
        <strong>F2F mode:</strong> Results will be provided to applicants in person. Email delivery is disabled.
      </div>
    {:else if release_mode === 'online'}
      <div class="rounded-lg border border-primary/30 bg-primary/5 px-4 py-3 text-sm">
        <strong>Online mode:</strong> Releasing a result will send applicants a portal notification and email.
      </div>
    {:else}
      <div class="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm">
        <strong>Online + F2F mode:</strong> Applicants will receive a portal notification and email when released.
      </div>
    {/if}

    <!-- Quick links -->
    <div class="flex items-center gap-3">
      <Link href="/release/result-sheet-templates" class="text-sm text-primary hover:underline">
        Result Sheet Templates →
      </Link>
    </div>

    <!-- Bulk action bar -->
    <div class="flex items-center gap-3">
      <Button
        onclick={releaseBulk}
        disabled={selectedIds.length === 0}
        class="min-h-[44px]"
      >
        Release Selected ({selectedIds.length})
      </Button>
      {#if release_mode !== 'f2f' && selectedIds.length > 0}
        <span class="text-xs text-muted-foreground">
          Email notifications will be sent to selected applicants.
        </span>
      {/if}
    </div>

    <!-- Table -->
    <div class="min-w-0">
      <div class="w-full overflow-x-auto scrollbar-hide">
        <Table.Root class="w-full min-w-[640px] text-sm">
          <Table.Header class="bg-muted/50">
            <Table.Row>
              <Table.Head class="w-10 px-4 py-3">
                <input
                  type="checkbox"
                  checked={allSelected}
                  onchange={toggleAll}
                  aria-label="Select all unreleased"
                  class="h-4 w-4 cursor-pointer"
                />
              </Table.Head>
              <Table.Head class="px-4 py-3">Applicant</Table.Head>
              <Table.Head class="px-4 py-3">Course Preferences</Table.Head>
              <Table.Head class="px-4 py-3">Recommended Course</Table.Head>
              <Table.Head class="px-4 py-3">Status</Table.Head>
              <Table.Head class="px-4 py-3 text-right">Action</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#each summaries.data as summary (summary.id)}
              <Table.Row class={summary.status === 'released' ? 'opacity-60' : ''}>
                <Table.Cell class="px-4 py-3">
                  {#if summary.status !== 'released'}
                    <input
                      type="checkbox"
                      checked={selectedIds.includes(summary.id)}
                      onchange={() => toggleOne(summary.id)}
                      aria-label="Select {summary.applicant?.name ?? summary.id}"
                      class="h-4 w-4 cursor-pointer"
                    />
                  {/if}
                </Table.Cell>
                <Table.Cell class="px-4 py-3">
                  <p class="font-medium">{summary.applicant?.name ?? '—'}</p>
                  <p class="text-xs text-muted-foreground">{summary.applicant?.email ?? ''}</p>
                </Table.Cell>
                <Table.Cell class="px-4 py-3">
          {@const prefs = getCoursePreferences(summary)}
                  {#if prefs.length}
                    <div class="text-xs space-y-0.5">
                      {#each prefs as pref, i}
                        <span class="font-medium">{i + 1}.</span> {pref.name}
                      {/each}
                    </div>
                  {:else}
                    <span class="text-muted-foreground">—</span>
                  {/if}
                </Table.Cell>
                <Table.Cell class="px-4 py-3">
                  {summary.recommended_course?.name ?? '—'}
                </Table.Cell>
                <Table.Cell class="px-4 py-3">
                  <Badge variant={statusVariant(summary.status)}>
                    {summary.status}
                  </Badge>
                </Table.Cell>
                <Table.Cell class="px-4 py-3 text-right">
                  {#if summary.status !== 'released'}
                    <div class="flex items-center justify-end gap-2">
                      <Button
                        size="sm"
                        variant="outline"
                        onclick={() => openPanel(summary)}
                        class="min-h-[36px]"
                      >
                        Edit
                      </Button>
                      <Button
                        size="sm"
                        variant="default"
                        onclick={() => releaseOne(summary.id)}
                        class="min-h-[36px]"
                      >
                        Release
                      </Button>
                    </div>
                  {:else}
                    <span class="text-xs text-muted-foreground">Released</span>
                  {/if}
                </Table.Cell>
              </Table.Row>
            {:else}
              <Table.Row>
                <Table.Cell colspan={6} class="px-4 py-12 text-center text-muted-foreground">
                  No results ready for release yet.
                </Table.Cell>
              </Table.Row>
            {/each}
          </Table.Body>
        </Table.Root>
      </div>

      {#if summaries.last_page > 1}
        <div class="flex items-center justify-between border-t border-border px-4 py-2">
          <p class="text-sm text-muted-foreground">
            Page {summaries.current_page} of {summaries.last_page}
          </p>
        </div>
      {/if}
    </div>
  </div>
</AuthenticatedLayout>

<!-- Edit Side Panel Backdrop -->
{#if showPanel}
  <div class="fixed inset-0 bg-black/20 z-40" onclick={closePanel}></div>
{/if}

<!-- Edit Side Panel -->
{#if showPanel && selectedSummary}
  <div class="fixed inset-y-0 right-0 w-96 bg-background border-l border-border shadow-lg z-50 flex flex-col">
    <div class="flex items-center justify-between p-6 border-b border-border">
      <h3 class="font-semibold">Edit Result — {selectedSummary.applicant?.name ?? ''}</h3>
      <button onclick={closePanel} class="text-muted-foreground hover:text-foreground text-2xl leading-none">&times;</button>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-6">
      {#if panelErrors}
        <div class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">{panelErrors}</div>
      {/if}

      <!-- Read-only: Applicant's Course Preferences -->
      <div class="space-y-1.5">
        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Applicant's Course Preferences</p>
        {#if panelPrefs.length}
          <div class="space-y-1">
            {#each panelPrefs as pref, i}
              <p class="text-sm">
                <span class="font-medium text-muted-foreground">{i + 1}.</span> {pref.name}
                {#if pref.code}<span class="text-muted-foreground"> ({pref.code})</span>{/if}
              </p>
            {/each}
          </div>
        {:else}
          <p class="text-sm text-muted-foreground">No preferences on file.</p>
        {/if}
      </div>

      <!-- Recommended Course -->
      <div class="space-y-1.5">
        <label for="rec-course" class="text-sm font-medium">
          Recommended Course
          {#if release_mode === 'online'}
            <span class="text-destructive">*</span>
          {:else}
            <span class="text-xs text-muted-foreground font-normal">(optional for F2F)</span>
          {/if}
        </label>
        <select
          id="rec-course"
          bind:value={recCourseId}
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
        >
          <option value="">— Select course —</option>
          {#each courses as course}
            <option value={course.id}>{course.name} ({course.code})</option>
          {/each}
        </select>
      </div>

      <!-- Counselor Comments -->
      <div class="space-y-1.5">
        <label for="counselor-comments" class="text-sm font-medium">
          Counselor Comments
          {#if release_mode === 'online'}
            <span class="text-destructive">*</span>
          {:else}
            <span class="text-xs text-muted-foreground font-normal">(optional for F2F)</span>
          {/if}
        </label>
        <textarea
          id="counselor-comments"
          bind:value={counselorComments}
          rows="5"
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary"
          placeholder="Enter comments or notes for the applicant..."
        ></textarea>
      </div>
    </div>

    <div class="p-6 border-t border-border space-y-2">
      <Button onclick={saveSummary} disabled={saving} class="w-full min-h-[44px]">
        {saving ? 'Saving…' : 'Save'}
      </Button>
      <Button variant="outline" onclick={closePanel} class="w-full min-h-[44px]">Cancel</Button>
    </div>
  </div>
{/if}
