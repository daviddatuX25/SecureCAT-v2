<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Input } from '@/Components/ui/input';
  import * as Table from '@/Components/ui/table';
  import { Filter, ChevronDown, CheckCircle, XCircle, UploadCloud, Plus, Pencil } from 'lucide-svelte';
  import ViewModeToggle from '@/Components/ViewModeToggle.svelte';
  import { success as showSuccess, error as showError } from '@/lib/toast';
  import { onMount } from 'svelte';

  let { applications, filters = {}, seasons = [], active_season_id = null, statuses = [] } = $props();

  const page = usePage();
  const authUser = $derived($page.props.auth?.user ?? null);
  const roles = $derived(authUser?.roles?.map((r) => r.name) ?? []);
  function hasRole(r) { return roles.includes(r); }

  // Show toasts on mount for flash messages
  onMount(() => {
    const flash = $page.props.flash;
    if (flash?.success) showSuccess(flash.success);
    if (flash?.error) showError(flash.error);

    // Initialize filters after mount
    initFilters();
  });

  let filterSearch = $state('');
  let filterStatus = $state('');
  let filterAcademicYearId = $state('');
  let filterDateFrom = $state('');
  let filterDateTo = $state('');
  let filtersOpen = $state(false);

  // Initialize filters - runs when props change
  function initFilters() {
    filterSearch = filters.search ?? '';
    filterStatus = filters.status ?? '';

    // Auto-select active season if no filter provided
    const seasonFromFilter = filters.academic_year_id;
    if (seasonFromFilter) {
      filterAcademicYearId = String(seasonFromFilter);
    } else if (active_season_id != null) {
      filterAcademicYearId = active_season_id;
    } else {
      filterAcademicYearId = '';
    }

    // Dates are not auto-populated from season window
    filterDateFrom = filters.date_from ?? '';
    filterDateTo = filters.date_to ?? '';
  }

  // React to prop changes
  $effect(() => {
    // Track dependencies
    const _ = filters.search;
    const __ = filters.status;
    const ___ = filters.academic_year_id;
    const ____ = filters.date_from;
    const _____ = filters.date_to;
    const ______ = active_season_id;
    const _______ = seasons.length;

    initFilters();
  });

  function applyFilters() {
    router.get('/admin/applications', {
      search: filterSearch || undefined,
      status: filterStatus || undefined,
      academic_year_id: filterAcademicYearId || undefined,
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
    return 'muted';
  }

  function statusLabel(value) {
    const s = statuses.find((x) => x.value === value);
    return s?.label ?? value;
  }

  function doAccept(id) {
    router.put(`/admin/applications/${id}/accept`, {}, {
      onSuccess: () => {},
    });
  }

  function doDismiss(id) {
    if (confirm('Are you sure you want to dismiss this application?')) {
      router.put(`/admin/applications/${id}/dismiss`, {}, {
        onSuccess: () => {},
      });
    }
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
    <div class="flex items-center justify-between">
      <div>
        <p class="mt-1 text-sm text-muted-foreground">View and manage applications by season</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        {#if hasRole('super_admin') || hasRole('staff') || hasRole('registrar_administrator')}
          <Link href="/admin/applications/create">
            <Button class="min-h-[44px] gap-2">
              <Plus class="h-4 w-4" />
              <span class="hidden sm:inline">Create</span>
            </Button>
          </Link>
        {/if}
        {#if hasRole('super_admin')}
          <Link href="/admin/applications/import">
            <Button variant="outline" class="min-h-[44px] gap-2">
              <UploadCloud class="h-4 w-4" />
              <span class="hidden sm:inline">Import</span>
            </Button>
          </Link>
        {/if}
      </div>
    </div>

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
              <select id="filter-season-mobile" bind:value={filterAcademicYearId} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[44px]">
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
          <select bind:value={filterAcademicYearId} class="rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[40px]">
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

    <!-- Table and Cards wrapped together with shared view toggle -->
    <div class="space-y-3">
      <!-- View toggle as sibling to both table and cards -->
      <div class="flex justify-end">
        <ViewModeToggle bind:value={viewMode} />
      </div>

      <div class="{viewMode === 'cards' ? 'hidden' : viewMode === 'table' ? 'block' : 'hidden md:block'} min-w-0">
        <div class="w-full min-w-0 overflow-x-auto scrollbar-hide">
        <Table.Root class="w-full min-w-[640px] text-sm">
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="px-4 py-3">Reference</Table.Head>
                <Table.Head class="px-4 py-3">Name</Table.Head>
                <Table.Head class="px-4 py-3">Email</Table.Head>
                <Table.Head class="px-4 py-3">Status</Table.Head>
                <Table.Head class="px-4 py-3">Submitted</Table.Head>
                <Table.Head class="text-center">Actions</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each list as app}
                <Table.Row class="border-t border-border hover:bg-muted/30">
                  <Table.Cell class="px-4 py-3 font-mono text-xs cursor-pointer" onclick={() => router.visit(`/admin/applications/${app.id}`)}>{app.reference_number ?? '—'}</Table.Cell>
                  <Table.Cell class="px-4 py-3 cursor-pointer" onclick={() => router.visit(`/admin/applications/${app.id}`)}>{app.full_name ?? '—'}</Table.Cell>
                  <Table.Cell class="px-4 py-3 text-muted-foreground cursor-pointer" onclick={() => router.visit(`/admin/applications/${app.id}`)}>{app.email ?? '—'}</Table.Cell>
                  <Table.Cell class="px-4 py-3 cursor-pointer" onclick={() => router.visit(`/admin/applications/${app.id}`)}>
                    <Badge variant={statusVariant(app.status)}>{statusLabel(app.status)}</Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3 text-muted-foreground cursor-pointer" onclick={() => router.visit(`/admin/applications/${app.id}`)}>
                    {app.submitted_at ? new Date(app.submitted_at).toLocaleDateString() : '—'}
                  </Table.Cell>
                  <Table.Cell class="text-left" onclick={(e) => e.stopPropagation()}>
                    <div class="flex justify-start gap-3" onclick={(e) => e.stopPropagation()}>
                      <Link href={`/admin/applications/${app.id}/edit`} class="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground">
                        <Pencil class="h-3.5 w-3.5" />
                        Edit
                      </Link>
                      {#if app.status === 'pending'}
                        <button
                          class="inline-flex items-center gap-1.5 text-xs text-green-600 hover:text-green-700 hover:underline"
                          onclick={(e) => { e.stopPropagation(); doAccept(app.id); }}
                        >
                          <CheckCircle class="h-3.5 w-3.5" />
                          Accept
                        </button>
                        <button
                          class="inline-flex items-center gap-1.5 text-xs text-red-600 hover:text-red-700 hover:underline"
                          onclick={(e) => { e.stopPropagation(); doDismiss(app.id); }}
                        >
                          <XCircle class="h-3.5 w-3.5" />
                          Dismiss
                        </button>
                      {/if}
                    </div>
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
          <div
            class="rounded-xl border border-border bg-card p-4 shadow-sm cursor-pointer hover:bg-muted/30"
            onclick={(e) => e.stopPropagation()}
            onkeydown={(e) => e.key === 'Enter' && router.visit(`/admin/applications/${app.id}`)}
            role="button"
            tabindex="0"
          >
            <p class="font-mono text-xs text-muted-foreground" onclick={() => router.visit(`/admin/applications/${app.id}`)}>{app.reference_number ?? '—'}</p>
            <p class="mt-1 font-medium" onclick={() => router.visit(`/admin/applications/${app.id}`)}>{app.full_name ?? '—'}</p>
            <p class="text-sm text-muted-foreground" onclick={() => router.visit(`/admin/applications/${app.id}`)}>{app.email ?? '—'}</p>
            <p class="mt-2" onclick={() => router.visit(`/admin/applications/${app.id}`)}>
              <Badge variant={statusVariant(app.status)}>{statusLabel(app.status)}</Badge>
            </p>
            <p class="mt-1 text-xs text-muted-foreground" onclick={() => router.visit(`/admin/applications/${app.id}`)}>
              {app.submitted_at ? new Date(app.submitted_at).toLocaleDateString() : '—'}
            </p>
            <div class="mt-3 flex flex-wrap gap-3" onclick={(e) => e.stopPropagation()}>
              <Link href={`/admin/applications/${app.id}/edit`} class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground" onclick={(e) => e.stopPropagation()}>
                <Pencil class="h-4 w-4" />
                Edit
              </Link>
              {#if app.status === 'pending'}
                <button
                  class="inline-flex items-center gap-1.5 text-sm text-green-600 hover:text-green-700 hover:underline"
                  onclick={(e) => { e.stopPropagation(); doAccept(app.id); }}
                >
                  <CheckCircle class="h-4 w-4" />
                  Accept
                </button>
                <button
                  class="inline-flex items-center gap-1.5 text-sm text-red-600 hover:text-red-700 hover:underline"
                  onclick={(e) => { e.stopPropagation(); doDismiss(app.id); }}
                >
                  <XCircle class="h-4 w-4" />
                  Dismiss
                </button>
              {/if}
            </div>
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
