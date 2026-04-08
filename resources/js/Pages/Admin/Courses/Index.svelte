<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Plus, Pencil, Trash2, Table2, LayoutGrid, MonitorSmartphone, CheckCircle } from 'lucide-svelte';
  import * as ToggleGroup from '@/Components/ui/toggle-group';

  let { courses } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);

  let viewMode = $state('responsive');
  let deleteId = $state(null);
  let activateId = $state(null);

  function confirmDelete(id) {
    deleteId = id;
  }

  function cancelDelete() {
    deleteId = null;
  }

  function doDelete() {
    if (deleteId) {
      router.delete(`/admin/courses/${deleteId}`, { onSuccess: () => (deleteId = null) });
    }
  }

  function doActivate(id) {
    router.post(`/admin/courses/${id}/activate`, { onSuccess: () => (activateId = null) });
  }
</script>

<svelte:head>
  <title>Courses - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold">Courses</h1>
        <p class="mt-1 text-sm text-muted-foreground">Manage courses</p>
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
        <Link href="/admin/courses/create">
          <Button class="min-h-[44px]">
            <Plus class="mr-2 h-4 w-4" />
            Add Course
          </Button>
        </Link>
      </div>
    </div>

    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">
        {success}
      </div>
    {/if}

    <div class="glass-panel rounded-2xl overflow-hidden min-w-0 max-w-full p-6">
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
              <th class="px-4 py-3 text-left font-medium">Code</th>
              <th class="px-4 py-3 text-left font-medium">Name</th>
              <th class="px-4 py-3 text-left font-medium">Department</th>
              <th class="px-4 py-3 text-left font-medium">Status</th>
              <th class="px-4 py-3 text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {#each courses.data as course}
              <tr class="border-t border-border hover:bg-muted/30">
                <td class="px-4 py-3 font-mono">{course.code}</td>
                <td class="px-4 py-3">{course.name}</td>
                <td class="px-4 py-3">{course.department?.code ?? '—'}</td>
                <td class="px-4 py-3">
                  <Badge variant={course.is_active ? 'success' : 'muted'}>{course.is_active ? 'Active' : 'Inactive'}</Badge>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    <Link href={`/admin/courses/${course.id}/edit`}>
                      <Button variant="ghost" size="icon" aria-label="Edit">
                        <Pencil class="h-4 w-4" />
                      </Button>
                    </Link>
                    {#if course.is_active}
                      <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Deactivate"
                        class="text-destructive hover:text-destructive"
                        onclick={() => confirmDelete(course.id)}
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    {:else}
                      <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Activate"
                        class="text-primary hover:text-primary"
                        onclick={() => doActivate(course.id)}
                      >
                        <CheckCircle class="h-4 w-4" />
                      </Button>
                    {/if}
                  </div>
                </td>
              </tr>
            {:else}
              <tr>
                <td colspan="5" class="px-4 py-12 text-center text-muted-foreground">
                  No courses yet. Create one to get started.
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>

      <div
        class="{viewMode === 'table'
          ? 'hidden'
          : viewMode === 'cards'
            ? 'block'
            : 'block md:hidden'} p-4"
      >
        {#if (courses?.data ?? []).length > 0}
          <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
            {#each (courses?.data ?? []) as course}
              <li class="flex flex-col gap-3 rounded-lg border border-border bg-card p-4">
                <div class="flex items-start justify-between gap-2">
                  <div>
                    <p class="font-mono text-xs text-muted-foreground">{course.code}</p>
                    <h3 class="font-semibold">{course.name}</h3>
                  </div>
                  <Badge variant={course.is_active ? 'success' : 'muted'}>{course.is_active ? 'Active' : 'Inactive'}</Badge>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                  <dt class="text-muted-foreground">Department</dt>
                  <dd>{course.department?.code ?? '—'}</dd>
                </dl>
                <div class="mt-auto flex gap-2 pt-2">
                  <Link href={`/admin/courses/${course.id}/edit`} class="flex-1">
                    <Button variant="outline" size="sm" class="w-full min-h-[44px]">
                      <Pencil class="h-4 w-4 mr-1.5" />
                      Edit
                    </Button>
                  </Link>
                  {#if course.is_active}
                    <Button
                      variant="outline"
                      size="sm"
                      class="min-h-[44px] text-destructive hover:text-destructive"
                      aria-label="Deactivate"
                      onclick={() => confirmDelete(course.id)}
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  {:else}
                    <Button
                      variant="outline"
                      size="sm"
                      class="min-h-[44px] text-primary hover:text-primary"
                      aria-label="Activate"
                      onclick={() => doActivate(course.id)}
                    >
                      <CheckCircle class="h-4 w-4" />
                    </Button>
                  {/if}
                </div>
              </li>
            {/each}
          </ul>
        {:else}
          <p class="py-12 text-center text-muted-foreground">No courses yet. Create one to get started.</p>
        {/if}
      </div>

      {#if courses.last_page > 1}
        <div class="flex items-center justify-between border-t border-border px-4 py-2">
          <p class="text-sm text-muted-foreground">
            Page {courses.current_page} of {courses.last_page}
          </p>
          <div class="flex gap-2">
            {#if courses.prev_page_url}
              <Link href={courses.prev_page_url}>
                <Button variant="outline" size="sm">Previous</Button>
              </Link>
            {/if}
            {#if courses.next_page_url}
              <Link href={courses.next_page_url}>
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
        <h2 id="delete-title" class="text-lg font-semibold">Deactivate course?</h2>
        <p class="mt-2 text-sm text-muted-foreground">
          The course will be marked inactive and hidden from the public application form.
        </p>
        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" onclick={cancelDelete}>Cancel</Button>
          <Button variant="destructive" onclick={doDelete}>Deactivate</Button>
        </div>
      </div>
    </div>
  {/if}
</AuthenticatedLayout>
