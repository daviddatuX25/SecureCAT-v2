<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Input } from '@/Components/ui/input';
  import * as Table from '@/Components/ui/table';
  import * as ToggleGroup from '@/Components/ui/toggle-group';
  import { Eye, Filter, ChevronDown, Table2, LayoutGrid, MonitorSmartphone } from 'lucide-svelte';

  let { applications, filters = {}, seasons = [], active_season_id = null, statuses = [] } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const error = $derived($page.props.flash?.error ?? null);

  let filterSearch = $state('');
  let filterStatus = $state('');
  let filterSeasonId = $state('');
  let filterDateFrom = $state('');
  let filterDateTo = $state('');
  let filtersOpen = $state(false);

  $effect(() => {
    filterSearch = filters.search ?? '';
    filterStatus = filters.status ?? '';
    filterSeasonId = filters.season_id ?? (active_season_id != null ? String(active_season_id) : '');
    filterDateFrom = filters.date_from ?? '';
    filterDateTo = filters.date_to ?? '';
  });

  function applyFilters() {
    router.get('/applications', {
      search: filterSearch || undefined,
      status: filterStatus || undefined,
      season_id: filterSeasonId || undefined,
      date_from: filterDateFrom || undefined,
      date_to: filterDateTo || undefined,
      page: 1,
    }, { preserveState: true });
    filtersOpen = false;
  }

  function statusVariant(status) {
    if (status === 'pending') return 'warning';
    if (status === 'accepted') return 'success';
    if (status === 'dismissed') return 'danger';
    if (status === 'incomplete_documents') return 'warning';
    return 'muted';
  }

  function statusLabel(value) {
    const s = statuses.find((x) => x.value === value);
    return s?.label ?? value;
  }

  function seasonLabel(season) {
    if (!season) return '—';
    return `${season.academic_year ?? ''} – ${season.semester ?? ''}`.trim() || '—';
  }

  const list = $derived(applications?.data ?? []);
  let viewMode = $state('responsive');

  const breadcrumbs = $derived([{ label: 'Applications' }]);
</script>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="mt-1 text-sm text-muted-foreground">View and manage applications by season</p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
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

    <!-- Filters -->
    <div class="flex flex-col gap-3">
      <div class="flex flex-wrap items-center gap-3">
        <Input
          type="search"
          placeholder="Search reference, name, email"
          bind:value={filterSearch}
          onkeydown={(e) => e.key === 'Enter' && applyFilters()}
          class="min-w-[160px] max-w-[220px] md:max-w-[220px] flex-1 min-w-0 md:flex-none min-h-[44px] md:min-h-[40px] h-10"
        />
        <details class="relative md:hidden" bind:open={filtersOpen}>
          <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-input bg-background px-3 py-2 text-sm min-h-[44px] min-w-[44px]">
            <Filter class="h-4 w-4" />
            <span>Filters</span>
            <ChevronDown class="h-4 w-4 transition-transform [details[open]_&]:rotate-180" />
          </summary>
          <div class="absolute right-0 top-full z-10 mt-1 w-[min(320px,calc(100vw-2rem))] rounded-lg border border-border bg-card p-4 shadow-lg">
            <div class="space-y-3">
              <label for="filter-status-mobile" class="block text-sm font-medium">Status</label>
              <select id="filter-status-mobile" bind:value={filterStatus} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[44px]">
                <option value="">All</option>
                {#each statuses as s}
                  <option value={s.value}>{s.label}</option>
                {/each}
              </select>
              <label for="filter-season-mobile" class="block text-sm font-medium">Season</label>
              <select id="filter-season-mobile" bind:value={filterSeasonId} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[44px]">
                <option value="">Active</option>
                {#each seasons as s}
                  <option value={s.id}>{seasonLabel(s)}</option>
                {/each}
              </select>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label for="filter-date-from-mobile" class="block text-xs text-muted-foreground">From</label>
                  <input id="filter-date-from-mobile" type="date" bind:value={filterDateFrom} class="w-full rounded-md border border-input bg-background px-2 py-2 text-sm min-h-[44px]" />
                </div>
                <div>
                  <label for="filter-date-to-mobile" class="block text-xs text-muted-foreground">To</label>
                  <input id="filter-date-to-mobile" type="date" bind:value={filterDateTo} class="w-full rounded-md border border-input bg-background px-2 py-2 text-sm min-h-[44px]" />
                </div>
              </div>
            </div>
          </div>
        </details>
        <div class="hidden md:flex md:items-center md:gap-3">
          <select bind:value={filterStatus} class="rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[40px]">
            <option value="">All statuses</option>
            {#each statuses as s}
              <option value={s.value}>{s.label}</option>
            {/each}
          </select>
          <select bind:value={filterSeasonId} class="rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[40px]">
            <option value="">Active season</option>
            {#each seasons as s}
              <option value={s.id}>{seasonLabel(s)}</option>
            {/each}
          </select>
          <input type="date" bind:value={filterDateFrom} class="rounded-md border border-input bg-background px-2 py-2 text-sm min-h-[40px]" placeholder="From" />
          <input type="date" bind:value={filterDateTo} class="rounded-md border border-input bg-background px-2 py-2 text-sm min-h-[40px]" placeholder="To" />
        </div>
        <Button class="min-h-[44px] md:min-h-[40px]" onclick={applyFilters}>
          Apply
        </Button>
      </div>
    </div>

    <!-- Table -->
    <div class="{viewMode === 'cards' ? 'hidden' : viewMode === 'table' ? 'block' : 'hidden md:block'} min-w-0">
      <div class="glass-panel rounded-2xl overflow-hidden min-w-0 max-w-full p-0">
        <div class="w-full min-w-0 overflow-x-auto">
          <Table.Root class="w-full min-w-[640px] text-sm">
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="px-4 py-3">Reference</Table.Head>
                <Table.Head class="px-4 py-3">Name</Table.Head>
                <Table.Head class="px-4 py-3">Email</Table.Head>
                <Table.Head class="px-4 py-3">Status</Table.Head>
                <Table.Head class="px-4 py-3">Submitted</Table.Head>
                <Table.Head class="px-4 py-3 text-right">Actions</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each list as app}
                <Table.Row class="border-t border-border hover:bg-muted/30">
                  <Table.Cell class="px-4 py-3 font-mono text-xs">{app.reference_number ?? '—'}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{app.full_name ?? '—'}</Table.Cell>
                  <Table.Cell class="px-4 py-3 text-muted-foreground">{app.email ?? '—'}</Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Badge variant={statusVariant(app.status)}>{statusLabel(app.status)}</Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3 text-muted-foreground">
                    {app.submitted_at ? new Date(app.submitted_at).toLocaleDateString() : '—'}
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3 text-right">
                    <Link href={`/applications/${app.id}`}>
                      <Button variant="ghost" size="sm" class="min-h-[44px]">
                        <Eye class="mr-1.5 h-4 w-4" />
                        View
                      </Button>
                    </Link>
                  </Table.Cell>
                </Table.Row>
              {:else}
                <Table.Row>
                  <Table.Cell colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                    No applications match the filters. Try a different season or search.
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
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

    <!-- Cards -->
    <div class="{viewMode === 'table' ? 'hidden' : viewMode === 'cards' ? 'block' : 'block md:hidden'}">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {#each list as app}
          <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
            <p class="font-mono text-xs text-muted-foreground">{app.reference_number ?? '—'}</p>
            <p class="mt-1 font-medium">{app.full_name ?? '—'}</p>
            <p class="text-sm text-muted-foreground">{app.email ?? '—'}</p>
            <p class="mt-2">
              <Badge variant={statusVariant(app.status)}>{statusLabel(app.status)}</Badge>
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
              {app.submitted_at ? new Date(app.submitted_at).toLocaleDateString() : '—'}
            </p>
            <Link href={`/applications/${app.id}`} class="mt-3 inline-block">
              <Button variant="outline" size="sm" class="min-h-[44px]">
                <Eye class="mr-1.5 h-4 w-4" />
                View
              </Button>
            </Link>
          </div>
        {:else}
          <div class="col-span-full rounded-xl border border-dashed border-border bg-muted/20 py-12 text-center text-muted-foreground">
            No applications match the filters.
          </div>
        {/each}
      </div>
      {#if applications?.last_page > 1}
        <div class="mt-4 flex justify-center gap-2">
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
      {/if}
    </div>
  </div>
</AuthenticatedLayout>
