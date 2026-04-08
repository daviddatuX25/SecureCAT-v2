<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import { Plus, Pencil, Trash2, ChevronDown, Filter } from 'lucide-svelte';

  let { users, roles, filters = {} } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);

  let filterSearch = $state('');
  let filterRole = $state('');
  let mobileFiltersDetails = $state(null);
  $effect(() => {
    filterSearch = filters.search ?? '';
    filterRole = filters.role ?? '';
  });

  let deleteId = $state(null);

  function applyFilters() {
    if (mobileFiltersDetails) mobileFiltersDetails.open = false;
    router.get('/admin/users', {
      search: filterSearch || undefined,
      role: filterRole || undefined,
      page: 1,
    }, { preserveState: true });
  }

  function confirmDelete(id) {
    deleteId = id;
  }

  function cancelDelete() {
    deleteId = null;
  }

  function doDelete() {
    if (deleteId) {
      router.delete(`/admin/users/${deleteId}`, { onSuccess: () => (deleteId = null) });
    }
  }

  const breadcrumbs = $derived([{ label: 'Users' }]);
</script>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <Link href="/admin/users/create">
        <Button class="min-h-[44px]">
          <Plus class="mr-2 h-4 w-4" />
          Create User
        </Button>
      </Link>
    </div>

    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">
        {success}
      </div>
    {/if}

    <!-- Filters: one row on desktop; on mobile search + collapsible "Filters" dropdown, Apply always visible -->
    <div class="flex flex-col gap-3">
      <div class="hidden md:flex flex-wrap items-center gap-3">
        <Input
          type="search"
          placeholder="Search name or email"
          bind:value={filterSearch}
          onkeydown={(e) => e.key === 'Enter' && applyFilters()}
          class="min-w-[160px] max-w-[220px] h-10"
        />
        <label for="filter-role-desk" class="sr-only">Role</label>
        <select
          id="filter-role-desk"
          class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm min-w-[140px]"
          bind:value={filterRole}
        >
          <option value="">All roles</option>
          {#each roles as r}
            <option value={r.name}>{r.display_name}</option>
          {/each}
        </select>
        <Button onclick={applyFilters} class="min-h-[40px]">Apply</Button>
      </div>
      <div class="flex flex-wrap items-center gap-3 md:hidden">
        <Input
          type="search"
          placeholder="Search name or email"
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
              <label for="filter-role-mob" class="text-sm font-medium block mb-1">Role</label>
              <select
                id="filter-role-mob"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                bind:value={filterRole}
              >
                <option value="">All roles</option>
                {#each roles as r}
                  <option value={r.name}>{r.display_name}</option>
                {/each}
              </select>
            </div>
          </div>
        </details>
        <Button onclick={applyFilters} class="min-h-[44px]">Apply</Button>
      </div>
    </div>

    <div class="glass-panel rounded-2xl overflow-hidden min-w-0 max-w-full p-6">
      <div class="w-full min-w-0 overflow-x-scroll overscroll-x-contain">
        <table class="w-full min-w-[640px] text-sm">
          <thead class="bg-muted/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium">Name</th>
              <th class="px-4 py-3 text-left font-medium">Email</th>
              <th class="px-4 py-3 text-left font-medium">Roles</th>
              <th class="px-4 py-3 text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {#each users.data as user (user.id)}
              <tr class="border-t border-border hover:bg-muted/30">
                <td class="px-4 py-3">{user.name}</td>
                <td class="px-4 py-3">{user.email}</td>
                <td class="px-4 py-3">
                  <span class="inline-flex flex-wrap gap-1">
                    {#each (user.roles ?? []) as r}
                      <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                        {r.display_name}
                      </span>
                    {/each}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    <Link href={`/admin/users/${user.id}/edit`}>
                      <Button variant="ghost" size="icon" aria-label="Edit">
                        <Pencil class="h-4 w-4" />
                      </Button>
                    </Link>
                    <Button
                      variant="ghost"
                      size="icon"
                      aria-label="Delete"
                      class="text-destructive hover:text-destructive"
                      onclick={() => confirmDelete(user.id)}
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
      {#if users.last_page > 1}
        <div class="flex items-center justify-between border-t border-border px-4 py-2">
          <p class="text-sm text-muted-foreground">
            Page {users.current_page} of {users.last_page}
          </p>
          <div class="flex gap-2">
            {#if users.prev_page_url}
              <Link href={users.prev_page_url}>
                <Button variant="outline" size="sm">Previous</Button>
              </Link>
            {/if}
            {#if users.next_page_url}
              <Link href={users.next_page_url}>
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
        <h2 id="delete-title" class="text-lg font-semibold">Delete user?</h2>
        <p class="mt-2 text-sm text-muted-foreground">
          This cannot be undone.
        </p>
        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" onclick={cancelDelete}>Cancel</Button>
          <Button variant="destructive" onclick={doDelete}>Delete</Button>
        </div>
      </div>
    </div>
  {/if}
</AuthenticatedLayout>
