<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Plus, Pencil, CheckCircle, XCircle, BookOpen } from 'lucide-svelte';

  let { seasons } = $props();

  function doDeactivate(id) {
    router.post(`/admin/academic-years/${id}/deactivate`, {}, { preserveScroll: true });
  }

  const page = usePage();
  const list = $derived(seasons?.data ?? []);
  const breadcrumbs = [{ label: 'Academic Years' }];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
      <div class="flex flex-wrap gap-3">
        <Link href="/admin/academic-years/create">
          <Button class="min-h-[44px]">
            <Plus class="mr-2 h-4 w-4" />
            Add Academic Year
          </Button>
        </Link>
        <Link href="/admin/courses">
          <Button variant="outline" class="min-h-[44px]">
            <BookOpen class="mr-2 h-4 w-4" />
            Manage Courses
          </Button>
        </Link>
      </div>
    </div>

    {#if !academic_years.some((ay) => ay.is_active)}
      <div class="mb-4 rounded-md border border-amber-500/50 bg-amber-500/10 px-4 py-3 text-sm text-foreground">
        No academic year is currently active. Applications are closed until one is activated.
      </div>
    {/if}

    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">
        {success}
      </div>
    {/if}

    <div class="glass-panel rounded-2xl overflow-hidden min-w-0 max-w-full p-6">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[520px] text-sm">
          <thead class="bg-muted/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium">Academic Year</th>
              <th class="px-4 py-3 text-left font-medium">Application window</th>
              <th class="px-4 py-3 text-left font-medium">Status</th>
              <th class="px-4 py-3 text-left font-medium">Applications</th>
              <th class="px-4 py-3 text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {#each list as ay}
              <tr class="border-t border-border hover:bg-muted/30">
                <td class="px-4 py-3 font-medium">
                  {ay.label ?? (ay.academic_year ?? '—') + ' – ' + (ay.semester_label ?? '—')}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                  {ay.application_window ?? '— — —'}
                </td>
                <td class="px-4 py-3">
                  <Badge variant={ay.is_active ? 'success' : 'muted'}>
                    {ay.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </td>
                <td class="px-4 py-3">
                  {ay.applications_count ?? 0}
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    {#if ay.is_active}
                      <Button
                        variant="outline"
                        size="sm"
                        class="min-h-[44px]"
                        onclick={() => doDeactivate(ay.id)}
                      >
                        <XCircle class="mr-1.5 h-4 w-4" />
                        Deactivate
                      </Button>
                    {:else}
                      <Button
                        variant="outline"
                        size="sm"
                        class="min-h-[44px]"
                        onclick={() => router.post(`/admin/academic-years/${ay.id}/activate`)}
                      >
                        <CheckCircle class="mr-1.5 h-4 w-4" />
                        Set active
                      </Button>
                    {/if}
                    <Link href={`/admin/academic-years/${ay.id}/edit`}>
                      <Button variant="ghost" size="icon" aria-label="Edit">
                        <Pencil class="h-4 w-4" />
                      </Button>
                    </Link>
                  </div>
                </td>
              </tr>
            {:else}
              <tr>
                <td colspan="5" class="px-4 py-12 text-center text-muted-foreground">
                  No academic years yet. Create one and set it active so applications can be submitted.
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>

      {#if seasons?.last_page > 1}
        <div class="flex items-center justify-between border-t border-border px-4 py-2">
          <p class="text-sm text-muted-foreground">
            Page {seasons.current_page} of {seasons.last_page}
          </p>
          <div class="flex gap-2">
            {#if seasons.prev_page_url}
              <Link href={seasons.prev_page_url}>
                <Button variant="outline" size="sm">Previous</Button>
              </Link>
            {/if}
            {#if seasons.next_page_url}
              <Link href={seasons.next_page_url}>
                <Button variant="outline" size="sm">Next</Button>
              </Link>
            {/if}
          </div>
        </div>
      {/if}
    </div>
  </div>
</AuthenticatedLayout>