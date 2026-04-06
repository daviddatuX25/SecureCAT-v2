<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Badge } from '@/Components/ui/badge';
  import { Button } from '@/Components/ui/button';
  import KpiCard from '@/Components/KpiCard.svelte';

  const page = usePage();
  const flash = $derived($page.props.flash ?? {});

  let { applicants_pending = { data: [] }, applicants_released = { data: [] }, stats = {} } = $props();

  const tabs = ['pending', 'released'];
  let activeTab = $state('pending');

  const displayPaginator = $derived(activeTab === 'pending' ? applicants_pending : applicants_released);
  const displayApplicants = $derived(displayPaginator.data || []);

  let selected = $state(new Set());

  function toggleSelect(id) {
    if (selected.has(id)) {
      selected.delete(id);
    } else {
      selected.add(id);
    }
    selected = new Set(selected);
  }

  function toggleSelectAll() {
    if (selected.size === displayApplicants.length) {
      selected.clear();
    } else {
      displayApplicants.forEach((a) => selected.add(a.id));
    }
    selected = new Set(selected);
  }

  let isReleasing = $state(false);
  function releaseBulk() {
    if (selected.size === 0 || isReleasing) return;
    isReleasing = true;
    router.post('/consultation/applicants/bulk-release', {
      applicant_ids: Array.from(selected)
    }, {
      onFinish: () => {
        isReleasing = false;
        selected = new Set();
      }
    });
  }
</script>

<svelte:head>
  <title>Release & Consultation - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-8 pb-8">
    {#if flash.success}
      <div class="glass-panel p-4 rounded-xl border border-success/20 bg-success/5 text-success text-sm shadow-sm flex items-center gap-2">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        {flash.success}
      </div>
    {/if}

    <!-- Hero KPIs -->
    <div class="grid gap-4 md:grid-cols-3">
      <KpiCard
        label="Total Processed"
        value={stats.total_with_scores ?? 0}
        status="ok"
      />
      <KpiCard
        label="Pending Release"
        value={stats.pending ?? 0}
        status={stats.pending > 0 ? 'warn' : 'ok'}
      />
      <KpiCard
        label="Released"
        value={stats.released ?? 0}
        status="ok"
      />
    </div>

    <!-- Action Center: Tabs & Bulk -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b pb-1">
      <div class="flex gap-4">
        {#each tabs as tab}
          <button
            type="button"
            class="pb-2 text-sm font-medium transition-all border-b-2 -mb-[5px] {activeTab === tab
              ? 'border-primary text-primary'
              : 'border-transparent text-muted-foreground hover:text-foreground'}"
            onclick={() => { activeTab = tab; selected.clear(); }}
          >
            {tab === 'pending' ? 'Pending Actions' : 'Released Consultations'}
            <Badge variant="secondary" class="ml-2 bg-muted/50">
              {tab === 'pending' ? (stats.pending ?? 0) : (stats.released ?? 0)}
            </Badge>
          </button>
        {/each}
      </div>
      {#if activeTab === 'pending' && displayApplicants.length > 0}
        <div class="flex items-center gap-3">
          <span class="text-sm text-muted-foreground">{selected.size} selected</span>
          <Button onclick={releaseBulk} disabled={selected.size === 0 || isReleasing} class="shadow-sm transition-shadow hover:shadow-md">
            {isReleasing ? 'Releasing...' : 'Release Selected'}
          </Button>
        </div>
      {/if}
    </div>

    <!-- Insights: Applicant Roster -->
    <div class="rounded-xl border bg-card/50 backdrop-blur shadow-sm overflow-hidden text-sm">
      {#if displayApplicants.length === 0}
        <div class="p-12 text-center text-muted-foreground">
          <p class="text-lg font-medium">No results found</p>
          <p class="text-sm">There are no {activeTab} applicants to display.</p>
        </div>
      {:else}
        {#if activeTab === 'pending'}
          <div class="flex items-center gap-3 bg-muted/30 p-3 px-4 sm:px-6 border-b border-border/50">
            <input 
              type="checkbox" 
              checked={selected.size === displayApplicants.length && displayApplicants.length > 0}
              indeterminate={selected.size > 0 && selected.size < displayApplicants.length}
              onchange={toggleSelectAll} 
              class="rounded border-muted-foreground/30 text-primary shadow-sm focus:ring-primary"
            />
            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Select All</span>
          </div>
        {/if}
        <div class="divide-y divide-border/50">
          {#each displayApplicants as applicant}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 sm:px-6 transition-colors hover:bg-muted/10 group">
              <div class="flex items-center gap-4 flex-1">
                {#if activeTab === 'pending'}
                  <input 
                    type="checkbox" 
                    checked={selected.has(applicant.id)}
                    onchange={() => toggleSelect(applicant.id)} 
                    class="rounded border-muted-foreground/30 text-primary shadow-sm focus:ring-primary h-4 w-4"
                  />
                {/if}
                <div class="min-w-0 flex-1 mb-3 sm:mb-0">
                  <p class="font-medium text-foreground tracking-tight">{applicant.name}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <Badge variant="outline" class="text-xs text-muted-foreground font-normal shrink-0">
                      {applicant.reference}
                    </Badge>
                    {#if activeTab === 'pending'}
                      <span class="text-xs text-muted-foreground truncate">
                        Finalized {applicant.finalized_date ?? '—'}
                      </span>
                    {/if}
                    {#if activeTab === 'released'}
                      <span class="text-xs text-muted-foreground truncate">
                        Released {applicant.released_date ?? '—'}
                      </span>
                    {/if}
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0 ml-8 sm:ml-0">
                {#if activeTab === 'pending'}
                  <Button size="sm" as="a" href={`/consultation/applicants/${applicant.id}`}>
                    Review & Release
                  </Button>
                {:else}
                  <Button variant="outline" size="sm" as="a" href={`/consultation/applicants/${applicant.id}`}>
                    View Details
                  </Button>
                {/if}
              </div>
            </div>
          {/each}
        </div>
        {#if displayPaginator.last_page > 1}
          <div class="flex items-center justify-between bg-muted/10 px-4 sm:px-6 py-3 border-t border-border/50">
            <span class="text-xs text-muted-foreground">Page {displayPaginator.current_page} of {displayPaginator.last_page}</span>
            <div class="flex items-center gap-2">
              {#if displayPaginator.prev_page_url}
                <Link href={displayPaginator.prev_page_url} preserveState preserveScroll>
                  <Button variant="outline" size="sm">Previous</Button>
                </Link>
              {/if}
              {#if displayPaginator.next_page_url}
                <Link href={displayPaginator.next_page_url} preserveState preserveScroll>
                  <Button variant="outline" size="sm">Next</Button>
                </Link>
              {/if}
            </div>
          </div>
        {/if}
      {/if}
    </div>
  </div>
</AuthenticatedLayout>