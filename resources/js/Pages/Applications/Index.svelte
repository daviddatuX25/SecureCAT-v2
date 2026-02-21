<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import * as ToggleGroup from '@/Components/ui/toggle-group';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Input } from '@/Components/ui/input';
  import { FileText, LayoutGrid, Table2, MonitorSmartphone, ChevronDown, Filter } from 'lucide-svelte';

  let { applications, filters = {}, statuses = [] } = $props();

  let filterSearch = $state('');
  let filterStatus = $state('');
  let filterDateFrom = $state('');
  let filterDateTo = $state('');
  let mobileFiltersDetails = $state(null);
  $effect(() => {
    filterSearch = filters.search ?? '';
    filterStatus = filters.status ?? '';
    filterDateFrom = filters.date_from ?? '';
    filterDateTo = filters.date_to ?? '';
  });

  function applyFilters() {
    if (mobileFiltersDetails) mobileFiltersDetails.open = false;
    router.get('/applications', {
      search: filterSearch || undefined,
      status: filterStatus || undefined,
      date_from: filterDateFrom || undefined,
      date_to: filterDateTo || undefined,
      page: 1,
    }, { preserveState: true });
  }

  function statusLabel(value) {
    return statuses.find((s) => s.value === value)?.label ?? value;
  }

  function statusVariant(status) {
    if (status === 'pending') return 'warning';
    if (status === 'accepted') return 'success';
    return 'danger';
  }

  // 'responsive' = cards on small, table on md+; 'table' | 'cards' = explicit override
  let viewMode = $state('responsive');
</script>

<svelte:head>
  <title>Applications - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold">Applications</h1>
        <p class="mt-1 text-sm text-muted-foreground">Search and manage applications.</p>
      </div>
      <ToggleGroup.Root
        type="single"
        bind:value={viewMode}
        variant="outline"
        size="sm"
        class="min-h-[44px] rounded-lg border border-border"
        aria-label="View layout"
      >
        <ToggleGroup.Item value="responsive" aria-label="Auto (responsive)" class="min-h-[44px]">
            <MonitorSmartphone class="h-4 w-4 md:mr-1.5" />
            <span class="hidden md:inline">Auto</span>
          </ToggleGroup.Item>
          <ToggleGroup.Item value="table" aria-label="Table view" class="min-h-[44px]">
            <Table2 class="h-4 w-4 md:mr-1.5" />
            <span class="hidden md:inline">Table</span>
          </ToggleGroup.Item>
          <ToggleGroup.Item value="cards" aria-label="Card view" class="min-h-[44px]">
            <LayoutGrid class="h-4 w-4 md:mr-1.5" />
            <span class="hidden md:inline">Cards</span>
          </ToggleGroup.Item>
      </ToggleGroup.Root>
    </div>

    <!-- Filters: one row on desktop; on mobile search + collapsible "Filters" dropdown, dates always together, Apply always visible -->
    <div class="flex flex-col gap-3">
      <div class="hidden md:flex flex-wrap items-center gap-3">
        <Input
          type="search"
          placeholder="Search by name or reference number"
          bind:value={filterSearch}
          onkeydown={(e) => e.key === 'Enter' && applyFilters()}
          class="min-w-[160px] max-w-[220px] h-10"
        />
        <label for="filter-status-desk" class="sr-only">Status</label>
        <select
          id="filter-status-desk"
          class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm min-w-[140px]"
          bind:value={filterStatus}
        >
          <option value="">All statuses</option>
          {#each statuses as s}
            <option value={s.value}>{s.label}</option>
          {/each}
        </select>
        <div class="flex items-center gap-2">
          <label for="filter-date-from-desk" class="text-sm text-muted-foreground whitespace-nowrap">From</label>
          <input
            id="filter-date-from-desk"
            type="date"
            bind:value={filterDateFrom}
            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
          />
          <label for="filter-date-to-desk" class="text-sm text-muted-foreground whitespace-nowrap">To</label>
          <input
            id="filter-date-to-desk"
            type="date"
            bind:value={filterDateTo}
            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
          />
        </div>
        <Button onclick={applyFilters} class="min-h-[40px]">Apply</Button>
      </div>

      <div class="flex flex-col gap-2 md:hidden">
        <div class="flex items-center gap-3">
          <Input
            type="search"
            placeholder="Search by name or reference number"
            bind:value={filterSearch}
            onkeydown={(e) => e.key === 'Enter' && applyFilters()}
            class="min-h-[44px] flex-1 min-w-0"
          />
          <details class="relative group" bind:this={mobileFiltersDetails}>
            <summary class="list-none flex items-center gap-2 min-h-[44px] px-4 rounded-md border border-input bg-background text-sm font-medium cursor-pointer hover:bg-muted/50">
              <Filter class="h-4 w-4" />
              <span>Filters</span>
              <ChevronDown class="h-4 w-4 transition-transform group-open:rotate-180" />
            </summary>
            <div class="absolute right-0 top-full z-10 mt-1 w-[min(320px,calc(100vw-2rem))] rounded-lg border border-border bg-card p-4 shadow-lg flex flex-col gap-3">
              <div>
                <label for="filter-status-mob" class="text-sm font-medium block mb-1">Status</label>
                <select
                  id="filter-status-mob"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  bind:value={filterStatus}
                >
                  <option value="">All statuses</option>
                  {#each statuses as s}
                    <option value={s.value}>{s.label}</option>
                  {/each}
                </select>
              </div>
              <div>
                <span class="text-sm font-medium block mb-1">Date range</span>
                <div class="flex items-center gap-2">
                  <input
                    id="filter-date-from-mob"
                    type="date"
                    bind:value={filterDateFrom}
                    class="flex h-10 flex-1 min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm"
                  />
                  <span class="text-muted-foreground">–</span>
                  <input
                    id="filter-date-to-mob"
                    type="date"
                    bind:value={filterDateTo}
                    class="flex h-10 flex-1 min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm"
                  />
                </div>
              </div>
            </div>
          </details>
        </div>
        <Button onclick={applyFilters} class="min-h-[44px] w-full">Apply</Button>
      </div>
    </div>

    <div class="rounded-lg border border-border overflow-hidden min-w-0 max-w-full">
      <!-- Table view: visible on md+ when responsive, or when Table chosen -->
      <div
        class="w-full min-w-0 overflow-x-scroll overscroll-x-contain {viewMode === 'cards'
          ? 'hidden'
          : viewMode === 'table'
            ? 'block'
            : 'hidden md:block'}"
      >
        <table class="w-full min-w-[640px] text-sm">
          <thead class="bg-muted/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium">Reference</th>
              <th class="px-4 py-3 text-left font-medium">Name</th>
              <th class="px-4 py-3 text-left font-medium">Email</th>
              <th class="px-4 py-3 text-left font-medium">Status</th>
              <th class="px-4 py-3 text-left font-medium">Submitted</th>
              <th class="px-4 py-3 text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {#each (applications?.data ?? []) as app}
              <tr class="border-t border-border hover:bg-muted/30">
                <td class="px-4 py-3 font-mono text-xs">{app.reference_number}</td>
                <td class="px-4 py-3">{app.full_name}</td>
                <td class="px-4 py-3">{app.email}</td>
                <td class="px-4 py-3">
                  <Badge variant={statusVariant(app.status)}>{statusLabel(app.status)}</Badge>
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                  {app.submitted_at ? new Date(app.submitted_at).toLocaleDateString() : '—'}
                </td>
                <td class="px-4 py-3 text-right">
                  <Link href={`/applications/${app.id}`}>
                    <Button variant="ghost" size="icon" aria-label="View details">
                      <FileText class="h-4 w-4" />
                    </Button>
                  </Link>
                </td>
              </tr>
            {:else}
              <tr>
                <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                  No applications match your filters.
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>

      <!-- Card view: visible on small when responsive, or when Cards chosen -->
      <div
        class="{viewMode === 'table'
          ? 'hidden'
          : viewMode === 'cards'
            ? 'block'
            : 'block md:hidden'} p-4"
      >
        {#if (applications?.data ?? []).length > 0}
          <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
            {#each (applications?.data ?? []) as app}
              <li>
                <Link
                  href={`/applications/${app.id}`}
                  class="flex min-h-[44px] flex-col gap-2 rounded-lg border border-border bg-card p-4 text-left transition-colors hover:bg-muted/50 focus:outline-none focus:ring-2 focus:ring-ring"
                >
                  <div class="flex items-start justify-between gap-2">
                    <span class="font-mono text-xs text-muted-foreground">{app.reference_number}</span>
                    <Badge variant={statusVariant(app.status)}>{statusLabel(app.status)}</Badge>
                  </div>
                  <p class="font-medium">{app.full_name}</p>
                  <p class="text-sm text-muted-foreground">{app.email}</p>
                  <p class="text-xs text-muted-foreground">
                    Submitted {app.submitted_at ? new Date(app.submitted_at).toLocaleDateString() : '—'}
                  </p>
                  <span class="mt-1 inline-flex items-center text-sm text-primary">
                    View details
                    <FileText class="ml-1 h-3.5 w-3.5" />
                  </span>
                </Link>
              </li>
            {/each}
          </ul>
        {:else}
          <p class="py-12 text-center text-muted-foreground">No applications match your filters.</p>
        {/if}
      </div>

      {#if applications?.last_page > 1}
        <div class="flex items-center justify-between border-t border-border px-4 py-2">
          <p class="text-sm text-muted-foreground">
            Page {applications.current_page} of {applications.last_page}
          </p>
          <div class="flex gap-2">
            {#if applications.prev_page_url}
              <Link href={applications.prev_page_url}>
                <Button variant="outline" size="sm">Previous</Button>
              </Link>
            {/if}
            {#if applications.next_page_url}
              <Link href={applications.next_page_url}>
                <Button variant="outline" size="sm">Next</Button>
              </Link>
            {/if}
          </div>
        </div>
      {/if}
    </div>
  </div>
</AuthenticatedLayout>
