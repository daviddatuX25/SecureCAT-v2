<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Plus, Pencil, CheckCircle, BookOpen } from 'lucide-svelte';

  let { seasons } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const list = $derived(seasons?.data ?? []);
</script>

<svelte:head>
  <title>Academic Years - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold">Academic Years</h1>
        <p class="mt-1 text-sm text-muted-foreground">Academic periods. Only one season is active; new applications attach to it.</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <Link href="/admin/seasons/create">
          <Button class="min-h-[44px]">
            <Plus class="mr-2 h-4 w-4" />
            Add Season
          </Button>
        </Link>
        <Link href="/admin/courses">
          <Button variant="outline" class="min-h-[44px]">
            <BookOpen class="mr-2 h-4 w-4" />
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
            {#each list as season}
              <tr class="border-t border-border hover:bg-muted/30">
                <td class="px-4 py-3 font-medium">
                  {season.label ?? (season.academic_year ?? '—') + ' – ' + (season.semester ?? '—')}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                  {season.application_window ?? '— — —'}
                </td>
                <td class="px-4 py-3">
                  <Badge variant={season.is_active ? 'success' : 'muted'}>
                    {season.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </td>
                <td class="px-4 py-3">
                  {season.applications_count ?? 0}
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    {#if !season.is_active}
                      <Button
                        variant="outline"
                        size="sm"
                        class="min-h-[44px]"
                        onclick={() => router.post(`/admin/seasons/${season.id}/activate`)}
                      >
                        <CheckCircle class="mr-1.5 h-4 w-4" />
                        Set active
                      </Button>
                    {/if}
                    <Link href={`/admin/seasons/${season.id}/edit`}>
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
                  No seasons yet. Create one and set it active so applications can be submitted.
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
