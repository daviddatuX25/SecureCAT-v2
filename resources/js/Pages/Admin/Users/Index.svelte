<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, useForm } from '@inertiajs/svelte';
  import { usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import { Plus, Pencil, Trash2 } from 'lucide-svelte';

  let { users, roles, filters } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);

  const filterForm = useForm({
    search: filters?.search ?? '',
    role: filters?.role ?? '',
  });

  let deleteId = $state(null);

  function applyFilters() {
    router.get('/admin/users', {
      search: $filterForm.search,
      role: $filterForm.role,
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
</script>

<svelte:head>
  <title>Users - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold">User Management</h1>
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

    <div class="flex flex-col gap-4 sm:flex-row">
      <Input
        type="search"
        placeholder="Search name or email"
        bind:value={$filterForm.search}
        onkeydown={(e) => e.key === 'Enter' && applyFilters()}
        class="max-w-xs"
      />
      <select
        bind:value={$filterForm.role}
        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
      >
        <option value="">All roles</option>
        {#each roles as r}
          <option value={r.name}>{r.display_name}</option>
        {/each}
      </select>
      <Button variant="secondary" onclick={applyFilters}>Apply</Button>
    </div>

    <div class="rounded-lg border border-border overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-muted/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium">Name</th>
              <th class="px-4 py-3 text-left font-medium">Email</th>
              <th class="px-4 py-3 text-left font-medium">Roles</th>
              <th class="px-4 py-3 text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {#each users.data as user}
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
