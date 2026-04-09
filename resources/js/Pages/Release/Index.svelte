<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import * as Table from '@/Components/ui/table';

  let { summaries, release_mode = 'online' } = $props();

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
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">

    {#if flash.success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">{flash.success}</div>
    {/if}
    {#if flash.error}
      <div class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">{flash.error}</div>
    {/if}

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
    <div class="glass-panel rounded-2xl overflow-hidden min-w-0 p-0">
      <div class="w-full overflow-x-auto">
        <Table.Root class="w-full min-w-[640px]">
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
                      onclick={() => releaseOne(summary.id)}
                      class="min-h-[36px]"
                    >
                      Release
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
