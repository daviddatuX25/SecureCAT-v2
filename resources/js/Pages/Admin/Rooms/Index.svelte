<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { usePage } from '@inertiajs/svelte';
  import * as ToggleGroup from '@/Components/ui/toggle-group';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Input } from '@/Components/ui/input';
  import * as Table from '@/Components/ui/table';
  import { Plus, Pencil, Trash2, LayoutGrid, Table2, MonitorSmartphone } from 'lucide-svelte';

  let { rooms, filters = {} } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const error = $derived($page.props.flash?.error ?? null);

  let filterSearch = $state('');
  $effect(() => {
    filterSearch = filters.search ?? '';
  });

  function applyFilters() {
    router.get('/admin/rooms', {
      search: filterSearch || undefined,
      page: 1,
    }, { preserveState: true });
  }

  let deleteId = $state(null);

  function confirmDelete(id) {
    deleteId = id;
  }

  function cancelDelete() {
    deleteId = null;
  }

  function doDelete() {
    if (deleteId) {
      router.delete(`/admin/rooms/${deleteId}`, { onSuccess: () => (deleteId = null) });
    }
  }

  function formatFacilities(facilities) {
    if (!facilities || typeof facilities !== 'object') return '—';
    const items = Object.entries(facilities)
      .filter(([, v]) => v)
      .map(([k]) => k.replace(/_/g, ' '));
    return items.length ? items.join(', ') : '—';
  }

  // 'responsive' = cards on small, table on md+; 'table' | 'cards' = explicit override
  let viewMode = $state('responsive');
</script>

<svelte:head>
  <title>Rooms - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold">Rooms</h1>
        <p class="mt-1 text-sm text-muted-foreground">Manage exam rooms</p>
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
        <Link href="/admin/rooms/create">
          <Button class="min-h-[44px]">
            <Plus class="mr-2 h-4 w-4" />
            Add Room
          </Button>
        </Link>
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

    <!-- Filters: one row on desktop; same on mobile (search + Apply) -->
    <div class="flex flex-col gap-3">
      <div class="flex flex-wrap items-center gap-3">
        <Input
          type="search"
          placeholder="Search name or building"
          bind:value={filterSearch}
          onkeydown={(e) => e.key === 'Enter' && applyFilters()}
          class="min-w-[160px] max-w-[220px] md:max-w-[220px] flex-1 min-w-0 md:flex-none min-h-[44px] md:min-h-[40px] h-10"
        />
        <Button onclick={applyFilters} class="min-h-[44px] md:min-h-[40px]">Apply</Button>
      </div>
    </div>

    <div class="glass-panel rounded-2xl overflow-hidden min-w-0 max-w-full p-6">
      <!-- Table view: single scroll container (Table.Root inner div); outer only constrains width -->
      <div
        class="w-full min-w-0 {viewMode === 'cards'
          ? 'hidden'
          : viewMode === 'table'
            ? 'block'
            : 'hidden md:block'}"
      >
        <Table.Root class="w-full min-w-[640px]">
          <Table.Header class="bg-muted/50">
            <Table.Row>
              <Table.Head class="px-4 py-3">Name</Table.Head>
              <Table.Head class="px-4 py-3">Building</Table.Head>
              <Table.Head class="px-4 py-3">Floor</Table.Head>
              <Table.Head class="px-4 py-3">Capacity</Table.Head>
              <Table.Head class="px-4 py-3">Facilities</Table.Head>
              <Table.Head class="px-4 py-3">Status</Table.Head>
              <Table.Head class="px-4 py-3 text-right">Actions</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#each rooms.data as room (room.id)}
              <Table.Row>
                <Table.Cell class="px-4 py-3">{room.name}</Table.Cell>
                <Table.Cell class="px-4 py-3">{room.building}</Table.Cell>
                <Table.Cell class="px-4 py-3">{room.floor ?? '—'}</Table.Cell>
                <Table.Cell class="px-4 py-3">{room.capacity}</Table.Cell>
                <Table.Cell class="px-4 py-3">{formatFacilities(room.facilities)}</Table.Cell>
                <Table.Cell class="px-4 py-3">
                  <Badge variant={room.is_active ? 'success' : 'muted'}>{room.is_active ? 'Active' : 'Inactive'}</Badge>
                </Table.Cell>
                <Table.Cell class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    <Link href={`/admin/rooms/${room.id}/edit`}>
                      <Button variant="ghost" size="icon" aria-label="Edit">
                        <Pencil class="h-4 w-4" />
                      </Button>
                    </Link>
                    {#if room.is_active}
                      <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Deactivate"
                        class="text-destructive hover:text-destructive"
                        onclick={() => confirmDelete(room.id)}
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    {/if}
                  </div>
                </Table.Cell>
              </Table.Row>
            {:else}
              <Table.Row>
                <Table.Cell colspan={7} class="px-4 py-12 text-center text-muted-foreground">
                  No rooms yet. Create one to get started.
                </Table.Cell>
              </Table.Row>
            {/each}
          </Table.Body>
        </Table.Root>
      </div>

      <!-- Card view -->
      <div
        class="{viewMode === 'table'
          ? 'hidden'
          : viewMode === 'cards'
            ? 'block'
            : 'block md:hidden'} p-4"
      >
        {#if (rooms?.data ?? []).length > 0}
          <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
            {#each (rooms?.data ?? []) as room (room.id)}
              <li
                class="flex flex-col gap-3 rounded-lg border border-border bg-card p-4"
              >
                <div class="flex items-start justify-between gap-2">
                  <h3 class="font-semibold">{room.name}</h3>
                  <Badge variant={room.is_active ? 'success' : 'muted'}>{room.is_active ? 'Active' : 'Inactive'}</Badge>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                  <dt class="text-muted-foreground">Building</dt>
                  <dd>{room.building}</dd>
                  <dt class="text-muted-foreground">Floor</dt>
                  <dd>{room.floor ?? '—'}</dd>
                  <dt class="text-muted-foreground">Capacity</dt>
                  <dd>{room.capacity}</dd>
                  <dt class="text-muted-foreground">Facilities</dt>
                  <dd class="col-span-1">{formatFacilities(room.facilities)}</dd>
                </dl>
                <div class="mt-auto flex gap-2 pt-2">
                  <Link href={`/admin/rooms/${room.id}/edit`} class="flex-1">
                    <Button variant="outline" size="sm" class="w-full min-h-[44px]">
                      <Pencil class="h-4 w-4 mr-1.5" />
                      Edit
                    </Button>
                  </Link>
                  {#if room.is_active}
                    <Button
                      variant="outline"
                      size="sm"
                      class="min-h-[44px] text-destructive hover:text-destructive"
                      aria-label="Deactivate"
                      onclick={() => confirmDelete(room.id)}
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  {/if}
                </div>
              </li>
            {/each}
          </ul>
        {:else}
          <p class="py-12 text-center text-muted-foreground">No rooms yet. Create one to get started.</p>
        {/if}
      </div>

      {#if rooms.last_page > 1}
        <div class="flex items-center justify-between border-t border-border px-4 py-2">
          <p class="text-sm text-muted-foreground">
            Page {rooms.current_page} of {rooms.last_page}
          </p>
          <div class="flex gap-2">
            {#if rooms.prev_page_url}
              <Link href={rooms.prev_page_url}>
                <Button variant="outline" size="sm">Previous</Button>
              </Link>
            {/if}
            {#if rooms.next_page_url}
              <Link href={rooms.next_page_url}>
                <Button variant="outline" size="sm">Next</Button>
              </Link>
            {/if}
          </div>
        </div>
      {/if}
    </div>
  </div>

  {#if deleteId}
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="delete-title"
    >
      <div class="rounded-lg bg-card p-6 shadow-lg max-w-sm w-full">
        <h2 id="delete-title" class="text-lg font-semibold">Deactivate room?</h2>
        <p class="mt-2 text-sm text-muted-foreground">
          The room will be marked inactive. You can reactivate it from the edit page.
        </p>
        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" onclick={cancelDelete}>Cancel</Button>
          <Button variant="destructive" onclick={doDelete}>Deactivate</Button>
        </div>
      </div>
    </div>
  {/if}
</AuthenticatedLayout>
