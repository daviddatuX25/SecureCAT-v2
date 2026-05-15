<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import * as Table from '@/Components/ui/table';
  import * as Dialog from '@/Components/ui/dialog';
  import * as Select from '@/Components/ui/select';
  import { error as toastError } from '@/lib/toast';
  import { FileText, Printer } from 'lucide-svelte';
  import * as Popover from '@/Components/ui/popover';
  import * as Command from '@/Components/ui/command';
  import { Textarea } from '@/Components/ui/textarea';
  import { Checkbox } from '@/Components/ui/checkbox';

  import SwitchableListView from '@/Components/SwitchableListView.svelte';
  import { Card, CardContent } from '@/Components/ui/card';

  let viewMode = $state('responsive');

  let { summaries, release_mode = 'online', courses = [], gradingSessions = [] } = $props();


  const page = usePage();
  const flash = $derived($page.props.flash ?? {});
  const breadcrumbs = [{ label: 'Release Management' }];

  let selectedIds = $state([]);
  let selectedSummary = $state(null);
  let showPanel = $state(false);
  let saving = $state(false);
  let panelErrors = $state('');
  let recCourseId = $state('');
  let counselorComments = $state('');
  let showConfirmDialog = $state(false);
  let printPopoverOpen = $state(false);

  const isF2F = $derived(release_mode === 'f2f');

  let unreleasedCount = $derived(
    summaries?.data?.filter((s) => s.status !== 'released').length ?? 0
  );

  let allSelected = $derived(
    summaries?.data?.length > 0 &&
    summaries.data
      .filter((s) => s.status !== 'released')
      .every((s) => selectedIds.includes(s.id))
  );

  const selectedApplicants = $derived(
    (summaries?.data ?? [])
      .filter((s) => selectedIds.includes(s.id))
      .map((s) => ({ id: s.applicant?.id, grading_session_id: s.grading_session_id }))
      .filter((a) => a.id && a.grading_session_id)
  );

  function toggleAll() {
    const unreleasedIds = (summaries?.data ?? [])
      .filter((s) => s.status !== 'released')
      .map((s) => s.id);
    selectedIds = allSelected ? [] : unreleasedIds;
  }

  function toggleOne(id) {
    selectedIds = selectedIds.includes(id)
      ? selectedIds.filter((i) => i !== id)
      : [...selectedIds, id];
  }

  function releaseOne(summaryId) {
    router.post(`/admin/release/summaries/${summaryId}/release`, { release_context: release_mode }, { preserveScroll: true });
  }

  function releaseBulk() {
    if (selectedIds.length === 0) return;
    router.post('/admin/release/summaries/bulk-release', { ids: selectedIds, release_context: release_mode }, {
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

  function statusVariant(status) {
    if (status === 'released') return 'success';
    if (status === 'draft') return 'secondary';
    return 'muted';
  }

  function openPanel(summary) {
    selectedSummary = summary;
    recCourseId = summary.recommended_course?.id ? String(summary.recommended_course.id) : '';
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
    router.post(`/admin/release/summaries/${selectedSummary.id}/release`, { release_context: release_mode }, {
      preserveScroll: true,
      onSuccess: () => { closePanel(); },
      onError: () => { panelErrors = 'Failed to release. Please try again.'; },
    });
  }

  function printSelected() {
    const apps = selectedApplicants;
    if (apps.length === 0) return;
    if (apps.length === 1) {
      window.open(`/admin/release/print/${apps[0].grading_session_id}/applicants/${apps[0].id}`, '_blank', 'noopener');
    } else {
      const ids = apps.map((a) => a.id).join(',');
      router.visit(`/admin/release/print/bulk?ids=${ids}`);
    }
  }

  function getCoursePreferences(summary) {
    const app = summary.applicant?.application;
    if (!app?.course_preferences) return [];
    return app.course_preferences.filter((p) => p.course !== null);
  }

  const panelPrefs = $derived(selectedSummary ? getCoursePreferences(selectedSummary) : []);
</script>

{#snippet applicantInfo(summary)}
  <p class="font-medium leading-snug">{summary.applicant?.full_name || '—'}</p>
  {#if summary.applicant?.reference_number}
    <p class="text-xs text-muted-foreground font-mono">{summary.applicant.reference_number}</p>
  {/if}
  {#if summary.applicant?.email}
    <p class="text-xs text-muted-foreground">{summary.applicant.email}</p>
  {/if}
{/snippet}

{#snippet coursePreferences(summary)}
  {@const prefs = getCoursePreferences(summary)}
  {#if prefs.length}
    <div class="flex flex-wrap items-center gap-2">
      {#each prefs as pref}
        <div class="inline-flex items-center rounded-full border border-border/60 bg-background px-2.5 py-0.5 text-xs font-medium shadow-sm transition-colors hover:border-border hover:bg-muted/40 cursor-default" title={pref.course.name}>
          <span class="mr-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary">
            {pref.rank}
          </span>
          <span class="truncate max-w-[120px] text-foreground tracking-tight">{pref.course.code}</span>
        </div>
      {/each}
    </div>
  {:else}
    <span class="text-xs text-muted-foreground italic">No preferences</span>
  {/if}
{/snippet}

{#snippet statusBadge(summary)}
  <Badge variant={statusVariant(summary.status)} class="capitalize">
    {summary.status}
  </Badge>
{/snippet}

{#snippet printedBadge(summary)}
  {#if summary.printed}
    <Badge variant="success" class="gap-1 text-xs">
      <Printer class="h-3 w-3" />
      Printed
    </Badge>
  {:else}
    <Badge variant="muted" class="gap-1 text-xs">
      <Printer class="h-3 w-3" />
      Not printed
    </Badge>
  {/if}
{/snippet}

{#snippet rowActions(summary)}
  {#if summary.status !== 'released'}
    {#if isF2F && summary.grading_session_id}
      <Link href={`/admin/release/print/${summary.grading_session_id}/applicants/${summary.applicant.id}`} target="_blank">
        <Button variant="outline" size="sm" class="h-8 px-2 text-xs">
          <Printer class="mr-1 h-3 w-3" />
          Result sheet
        </Button>
      </Link>
    {/if}
    <Button size="sm" variant="outline" onclick={() => openPanel(summary)} class="min-h-[36px]">
      Edit
    </Button>
    <Button size="sm" variant="default" onclick={() => releaseOne(summary.id)} class="min-h-[36px]">
      Release
    </Button>
  {:else}
    <Badge variant="success" class="capitalize">Released</Badge>
  {/if}
{/snippet}

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted-foreground">Release exam results to applicants</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        {#if !isF2F && unreleasedCount > 0}
          <Button onclick={() => (showConfirmDialog = true)} class="min-h-[44px]">
            Release All
          </Button>
        {/if}
        {#if selectedIds.length > 0}
          <Button onclick={releaseBulk} variant="outline" class="min-h-[44px]">
            Release Selected ({selectedIds.length})
          </Button>
        {/if}
        {#if isF2F && selectedApplicants.length > 0}
          <Button onclick={printSelected} variant="outline" class="min-h-[44px] gap-2">
            <Printer class="h-4 w-4" />
            Print Selected ({selectedApplicants.length})
          </Button>
        {/if}
        {#if isF2F && gradingSessions.length > 0}
          <Popover.Root bind:open={printPopoverOpen}>
            <Popover.Trigger>
              <Button variant="outline" class="min-h-[44px] gap-2">
                <Printer class="h-4 w-4" />
                Print by Exam Session
              </Button>
            </Popover.Trigger>
            <Popover.Content class="w-72 p-0" align="end">
              <Command.Root>
                <Command.Input placeholder="Search sessions..." />
                <Command.List>
                  <Command.Empty>No sessions found.</Command.Empty>
                  <Command.Group>
                    {#each gradingSessions as gs}
                      <Command.Item
                        value={`${gs.label} ${gs.exam_date} ${gs.room_name}`}
                        onSelect={() => {
                          printPopoverOpen = false;
                          router.visit(`/admin/release/print/${gs.id}`);
                        }}
                      >
                        <div class="flex flex-col">
                          <span class="font-medium">{gs.label}</span>
                          <span class="text-muted-foreground text-xs">{gs.exam_date} · {gs.room_name}</span>
                        </div>
                      </Command.Item>
                    {/each}
                  </Command.Group>
                </Command.List>
              </Command.Root>
            </Popover.Content>
          </Popover.Root>
        {/if}
        <Link href="/admin/release/result-templates">
          <Button variant="outline" class="min-h-[44px] gap-2">
            <FileText class="h-4 w-4" />
            <span class="hidden sm:inline">Result Templates</span>
          </Button>
        </Link>
      </div>
    </div>

    {#if isF2F}
      <div class="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm">
        <strong>F2F mode:</strong> Results will be provided to applicants in person. Email delivery is disabled.
      </div>
    {:else}
      <div class="rounded-lg border border-primary/30 bg-primary/5 px-4 py-3 text-sm">
        <strong>Online mode:</strong> Releasing a result will send applicants a portal notification and email.
      </div>
    {/if}

    <div class="min-w-0">
      <SwitchableListView bind:viewMode>
        {#snippet table()}
          <Table.Root class="w-full min-w-[640px] text-sm">
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="w-10 px-4 py-3">
                  <Checkbox
                    checked={allSelected}
                    onCheckedChange={toggleAll}
                    aria-label="Select all unreleased"
                    class="h-4 w-4"
                  />
                </Table.Head>
                <Table.Head class="px-4 py-3">Applicant</Table.Head>
                <Table.Head class="px-4 py-3">Course Preferences</Table.Head>
                <Table.Head class="px-4 py-3">Recommended Course</Table.Head>
                <Table.Head class="px-4 py-3">Status</Table.Head>
                <Table.Head class="px-4 py-3">Printed</Table.Head>
                <Table.Head class="px-4 py-3 text-right">Action</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each summaries.data as summary (summary.id)}
                <Table.Row class={summary.status === 'released' ? 'opacity-60' : ''}>
                  <Table.Cell class="px-4 py-3">
                    {#if summary.status !== 'released'}
                      <Checkbox
                        checked={selectedIds.includes(summary.id)}
                        onCheckedChange={() => toggleOne(summary.id)}
                        aria-label="Select {summary.applicant?.full_name ?? summary.id}"
                        class="h-4 w-4"
                      />
                    {/if}
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    {@render applicantInfo(summary)}
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    {@render coursePreferences(summary)}
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    {#if summary.recommended_course}
                      <div class="inline-flex items-center rounded-full border border-primary/20 bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary shadow-sm" title={summary.recommended_course.name}>
                        <span class="truncate max-w-[150px] tracking-tight">{summary.recommended_course.code}</span>
                      </div>
                    {:else}
                      <span class="text-xs text-muted-foreground italic">Not set</span>
                    {/if}
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    {@render statusBadge(summary)}
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    {@render printedBadge(summary)}
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                      {@render rowActions(summary)}
                    </div>
                  </Table.Cell>
                </Table.Row>
              {:else}
                <Table.Row>
                  <Table.Cell colspan={7} class="px-4 py-16 text-center text-muted-foreground">
                    <p class="font-medium">No results ready for release yet.</p>
                    <p class="text-xs mt-1">Applicants appear here once all aptitude area scores are saved.</p>
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        {/snippet}

        {#snippet cards()}
          <div class="space-y-4">
            {#if summaries.data.length > 0}
              <div class="flex items-center gap-3 px-1 mb-4">
                <Checkbox
                  checked={allSelected}
                  onCheckedChange={toggleAll}
                  aria-label="Select all unreleased"
                  class="h-4 w-4"
                />
                <span class="text-sm font-medium text-muted-foreground">Select all unreleased</span>
              </div>
            {/if}
            
            {#each summaries.data as summary (summary.id)}
              <Card class={summary.status === 'released' ? 'opacity-60' : ''}>
                <CardContent class="p-4 space-y-4">
                  <div class="flex justify-between items-start">
                    <div class="flex gap-3">
                      {#if summary.status !== 'released'}
                        <div class="pt-1">
                          <Checkbox
                            checked={selectedIds.includes(summary.id)}
                            onCheckedChange={() => toggleOne(summary.id)}
                            aria-label="Select {summary.applicant?.full_name ?? summary.id}"
                            class="h-4 w-4"
                          />
                        </div>
                      {/if}
                      <div>
                        {@render applicantInfo(summary)}
                      </div>
                    </div>
                    {@render statusBadge(summary)}
                  </div>
                  
                  <div class="flex items-center gap-2 text-xs">
                    <span class="text-muted-foreground">Print status:</span>
                    {@render printedBadge(summary)}
                  </div>
                  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm bg-muted/30 p-3 rounded-md">
                    <div>
                      <p class="text-xs text-muted-foreground font-medium mb-1">Preferences</p>
                      {@render coursePreferences(summary)}
                    </div>
                    <div>
                      <p class="text-xs text-muted-foreground font-medium mb-1">Recommended</p>
                      {#if summary.recommended_course}
                        <div class="inline-flex items-center rounded-full border border-primary/20 bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary shadow-sm" title={summary.recommended_course.name}>
                          <span class="truncate max-w-[150px] tracking-tight">{summary.recommended_course.code}</span>
                        </div>
                      {:else}
                        <span class="text-xs text-muted-foreground italic">Not set</span>
                      {/if}
                    </div>
                  </div>

                  <div class="flex items-center justify-end gap-2 pt-2 border-t border-border/50">
                    {@render rowActions(summary)}
                  </div>
                </CardContent>
              </Card>
            {:else}
              <div class="py-12 text-center text-muted-foreground bg-card rounded-lg border border-border">
                <p class="font-medium">No results ready for release yet.</p>
                <p class="text-xs mt-1">Applicants appear here once all aptitude area scores are saved.</p>
              </div>
            {/each}
          </div>
        {/snippet}
      </SwitchableListView>

      {#if summaries?.last_page > 1}
        <div class="flex items-center justify-between border-t border-border px-4 py-2">
          <p class="text-sm text-muted-foreground">
            Page {summaries.current_page} of {summaries.last_page}
          </p>
        </div>
      {/if}
    </div>
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
      <h3 class="font-semibold">Edit Result — {selectedSummary.applicant?.full_name ?? ''}</h3>
      <button onclick={closePanel} class="text-muted-foreground hover:text-foreground text-2xl leading-none">&times;</button>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-6">
      {#if panelErrors}
        <div class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">{panelErrors}</div>
      {/if}

      <div class="space-y-1.5">
        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Applicant's Course Preferences</p>
        {#if panelPrefs.length}
          <div class="flex flex-wrap items-center gap-2">
            {#each panelPrefs as pref}
              <div class="inline-flex items-center rounded-full border border-border/60 bg-background px-2.5 py-0.5 text-xs font-medium shadow-sm transition-colors hover:border-border hover:bg-muted/40 cursor-default" title={pref.course.name}>
                <span class="mr-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold text-primary">
                  {pref.rank}
                </span>
                <span class="truncate max-w-[150px] text-foreground tracking-tight">{pref.course.code}</span>
              </div>
            {/each}
          </div>
        {:else}
          <p class="text-sm text-muted-foreground">No preferences on file.</p>
        {/if}
      </div>

      <div class="space-y-1.5">
        <label for="rec-course" class="text-sm font-medium">
          Recommended Course
          {#if !isF2F}
            <span class="text-destructive">*</span>
          {:else}
            <span class="text-xs text-muted-foreground font-normal">(optional for F2F)</span>
          {/if}
        </label>
        <Select.Root type="single" bind:value={recCourseId}>
          <Select.Trigger class="w-full">
            {#if recCourseId}
              {courses.find((c) => String(c.id) === recCourseId)?.name ?? 'Select course'}
            {:else}
              <span class="text-muted-foreground">Select course…</span>
            {/if}
          </Select.Trigger>
          <Select.Content>
            <Select.Item value="" label="None">— No selection —</Select.Item>
            {#each courses as course (course.id)}
              <Select.Item value={String(course.id)} label={course.name}>
                {course.name} ({course.code})
              </Select.Item>
            {/each}
          </Select.Content>
        </Select.Root>
      </div>

      <div class="space-y-1.5">
        <label for="counselor-comments" class="text-sm font-medium">
          Counselor Comments
          {#if !isF2F}
            <span class="text-destructive">*</span>
          {:else}
            <span class="text-xs text-muted-foreground font-normal">(optional for F2F)</span>
          {/if}
        </label>
        <Textarea
          id="counselor-comments"
          bind:value={counselorComments}
          rows="5"
          class="w-full resize-none"
          placeholder="Enter comments or notes for the applicant..."
        />
      </div>
    </div>

    <div class="p-6 border-t border-border space-y-2">
      <Button onclick={saveSummary} disabled={saving} class="w-full min-h-[44px]">
        {saving ? 'Saving…' : 'Save Notes'}
      </Button>
      {#if selectedSummary?.status !== 'released'}
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