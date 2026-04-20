<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import * as Table from '@/Components/ui/table';
  import * as Tabs from '@/Components/ui/tabs';
  import * as Dialog from '@/Components/ui/dialog';
  import { error as toastError } from '@/lib/toast';
  import { FileText } from 'lucide-svelte';

  let { summaries, online_summaries, f2f_summaries, release_mode = 'online', courses = [] } = $props();

  const page = usePage();
  const flash = $derived($page.props.flash ?? {});
  const breadcrumbs = [{ label: 'Release Management' }];

  // Selection state (F2F mode)
  let selectedIds = $state([]);

  // Panel state
  let selectedSummary = $state(null);
  let showPanel = $state(false);
  let saving = $state(false);
  let panelErrors = $state('');
  let recCourseId = $state('');
  let counselorComments = $state('');

  // Both-mode state
  let showConfirmDialog = $state(false);
  let activeTab = $state('online');

  // Derived: current dataset based on mode and tab
  let displaySummaries = $derived(
    release_mode === 'both'
      ? (activeTab === 'online' ? online_summaries : f2f_summaries)
      : summaries
  );

  let unreleasedCount = $derived(
    displaySummaries?.data?.filter((s) => s.status !== 'released').length ?? 0
  );

  let allSelected = $derived(
    displaySummaries?.data?.length > 0 &&
    displaySummaries.data
      .filter((s) => s.status !== 'released')
      .every((s) => selectedIds.includes(s.id))
  );

  function toggleAll() {
    const unreleasedIds = (displaySummaries?.data ?? [])
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
    const context = release_mode === 'both' ? (activeTab === 'online' ? 'online' : 'f2f') : release_mode;
    router.post(`/admin/release/summaries/${summaryId}/release`, { release_context: context }, { preserveScroll: true });
  }

  function releaseBulk() {
    if (selectedIds.length === 0) return;
    const context = release_mode === 'both' ? (activeTab === 'online' ? 'online' : 'f2f') : release_mode;
    router.post('/admin/release/summaries/bulk-release', { ids: selectedIds, release_context: context }, {
      preserveScroll: true,
      onSuccess: () => { selectedIds = []; },
    });
  }

  function handleReleaseAll() {
    router.post('/admin/release/summaries/release-all', {}, {
      preserveScroll: true,
      onSuccess: () => { showConfirmDialog = false; },
      onError: () => { toastError('Failed to release results. Please try again.'); },
    });
  }

  function handleTabChange(value) {
    activeTab = value;
    selectedIds = [];
  }

  function statusVariant(status) {
    if (status === 'released') return 'success';
    if (status === 'draft') return 'secondary';
    return 'muted';
  }

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
    router.put(`/admin/release/summaries/by-applicant/${applicantId}`, {
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

  function releaseOneFromPanel() {
    if (!selectedSummary) return;
    const context = release_mode === 'both' ? (activeTab === 'online' ? 'online' : 'f2f') : release_mode;
    router.post(`/admin/release/summaries/${selectedSummary.id}/release`, { release_context: context }, {
      preserveScroll: true,
      onSuccess: () => { closePanel(); },
      onError: () => { panelErrors = 'Failed to release. Please try again.'; },
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
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted-foreground">Release exam results to applicants</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        {#if release_mode === 'online' && unreleasedCount > 0}
          <Button onclick={() => (showConfirmDialog = true)} class="min-h-[44px]">
            Release All
          </Button>
        {/if}
        <Link href="/admin/release/result-templates">
          <Button variant="outline" class="min-h-[44px] gap-2">
            <FileText class="h-4 w-4" />
            <span class="hidden sm:inline">Result Templates</span>
          </Button>
        </Link>
      </div>
    </div>

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
        <strong>Online + F2F mode:</strong> Use the tabs below to manage each release type independently.
      </div>
    {/if}

    {#if release_mode === 'both'}
      <Tabs.Root bind:value={activeTab} onValueChange={handleTabChange}>
        <Tabs.List>
          <Tabs.Trigger value="online">Online</Tabs.Trigger>
          <Tabs.Trigger value="f2f">F2F</Tabs.Trigger>
        </Tabs.List>
        <Tabs.Content value="online">
          {#if activeTab === 'online'}
            {@render onlineTable()}
          {/if}
        </Tabs.Content>
        <Tabs.Content value="f2f">
          {#if activeTab === 'f2f'}
            {@render f2fTable()}
          {/if}
        </Tabs.Content>
      </Tabs.Root>
    {:else if release_mode === 'online'}
      {@render onlineTable()}
    {:else}
      {@render f2fTable()}
    {/if}
  </div>
</AuthenticatedLayout>

<!-- Release All Confirmation Dialog -->
<Dialog.Root bind:open={showConfirmDialog}>
  <Dialog.Portal>
    <Dialog.Overlay class="fixed inset-0 bg-black/40 z-50" />
    <Dialog.Content class="fixed top-[50%] left-[50%] -translate-x-1/2 -translate-y-1/2 z-50 bg-background rounded-lg border p-6 shadow-lg max-w-md w-[calc(100%-2rem)]">
      <Dialog.Header>
        <Dialog.Title>Confirm Release All</Dialog.Title>
        <Dialog.Description>
          This will release {unreleasedCount} results to applicants via email and portal notification. This action cannot be undone.
        </Dialog.Description>
      </Dialog.Header>
      <Dialog.Footer class="flex justify-end gap-2 mt-4">
        <Button variant="outline" onclick={() => (showConfirmDialog = false)}>Don't Release</Button>
        <Button onclick={handleReleaseAll}>Proceed</Button>
      </Dialog.Footer>
    </Dialog.Content>
  </Dialog.Portal>
</Dialog.Root>

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
          {#if release_mode === 'online' || (release_mode === 'both' && activeTab === 'online')}
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
          {#if release_mode === 'online' || (release_mode === 'both' && activeTab === 'online')}
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
        {saving ? 'Saving…' : 'Save Notes'}
      </Button>
      {#if release_mode !== 'online' && selectedSummary?.status !== 'released'}
        <Button
          onclick={releaseOneFromPanel}
          disabled={saving}
          class="w-full min-h-[44px]"
        >
          Release
        </Button>
      {/if}
      <Button variant="outline" onclick={closePanel} class="w-full min-h-[44px]">Close</Button>
    </div>
  </div>
{/if}

{#snippet onlineTable()}
  <!-- Online mode: read-only table with Release All, no checkboxes -->
  <div class="min-w-0">
    <div class="w-full overflow-x-auto scrollbar-hide">
      <Table.Root class="w-full min-w-[640px] text-sm">
        <Table.Header class="bg-muted/50">
          <Table.Row>
            <Table.Head class="px-4 py-3">Applicant</Table.Head>
            <Table.Head class="px-4 py-3">Course Preferences</Table.Head>
            <Table.Head class="px-4 py-3">Recommended Course</Table.Head>
            <Table.Head class="px-4 py-3">Status</Table.Head>
            <Table.Head class="px-4 py-3 text-right">Action</Table.Head>
          </Table.Row>
        </Table.Header>
        <Table.Body>
          {#each displaySummaries.data as summary (summary.id)}
            <Table.Row class={summary.status === 'released' ? 'opacity-60' : ''}>
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
                  <Button
                    size="sm"
                    variant="outline"
                    onclick={() => openPanel(summary)}
                    class="min-h-[36px]"
                  >
                    Edit
                  </Button>
                {:else}
                  <span class="text-xs text-muted-foreground">Released</span>
                {/if}
              </Table.Cell>
            </Table.Row>
          {:else}
            <Table.Row>
              <Table.Cell colspan={5} class="px-4 py-12 text-center text-muted-foreground">
                No results ready for release yet.
              </Table.Cell>
            </Table.Row>
          {/each}
        </Table.Body>
      </Table.Root>
    </div>

    {#if displaySummaries?.last_page > 1}
      <div class="flex items-center justify-between border-t border-border px-4 py-2">
        <p class="text-sm text-muted-foreground">
          Page {displaySummaries.current_page} of {displaySummaries.last_page}
        </p>
      </div>
    {/if}
  </div>
{/snippet}

{#snippet f2fTable()}
  <!-- F2F mode: checkbox table with per-row Edit+Release, bulk action bar -->
  <div class="flex items-center gap-3">
    <Button
      onclick={releaseBulk}
      disabled={selectedIds.length === 0}
      class="min-h-[44px]"
    >
      Release Selected ({selectedIds.length})
    </Button>
  </div>

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
          {#each displaySummaries.data as summary (summary.id)}
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

    {#if displaySummaries?.last_page > 1}
      <div class="flex items-center justify-between border-t border-border px-4 py-2">
        <p class="text-sm text-muted-foreground">
          Page {displaySummaries.current_page} of {displaySummaries.last_page}
        </p>
      </div>
    {/if}
  </div>
{/snippet}