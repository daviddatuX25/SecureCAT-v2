<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Plus, Pencil, Pause, Play, BookOpen } from 'lucide-svelte';
  import * as Table from '@/Components/ui/table';

  let { academic_years, success = null } = $props();

  function doDeactivate(id) {
    router.post(`/admin/academic-years/${id}/deactivate`, {}, { preserveScroll: true });
  }

  const page = usePage();
  const list = $derived(academic_years?.data ?? []);
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
    </div>

    {#if !list.some((ay) => ay.is_active)}
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
      <div class="w-full min-w-0 overflow-x-auto scrollbar-thin">
        <Table.Root>
          <Table.Header class="bg-muted/50">
            <Table.Row>
              <Table.Head>Academic Year</Table.Head>
              <Table.Head>Application window</Table.Head>
              <Table.Head>Status</Table.Head>
              <Table.Head>Applications</Table.Head>
              <Table.Head class="text-center">Actions</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#each list as ay}
              <Table.Row class={ay.is_active ? '' : 'text-muted-foreground/50 transition-colors'}>
                <Table.Cell class="font-bold tracking-tight">
                  {ay.label ?? (ay.academic_year ?? '—') + ' – ' + (ay.semester_label ?? '—')}
                </Table.Cell>
                <Table.Cell class="text-muted-foreground font-medium">
                  {ay.application_window ?? '— — —'}
                </Table.Cell>
                <Table.Cell>
                  <Badge variant={ay.is_active ? 'success' : 'muted'} class="font-medium">
                    {ay.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </Table.Cell>
                <Table.Cell class="font-mono text-xs">
                  {ay.applications_count ?? 0}
                </Table.Cell>
                <Table.Cell class="text-center">
                  <div class="flex justify-center gap-2 text-foreground!">
                    <Link href={`/admin/academic-years/${ay.id}/edit`}>
                      <Button variant="ghost" size="sm" class="h-8 px-2 text-xs font-semibold hover:bg-muted">
                        <Pencil class="mr-1.5 h-3.5 w-3.5" />
                        Edit
                      </Button>
                    </Link>
                    
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-8 px-2 text-xs font-semibold {ay.is_active 
                        ? 'text-amber-600 hover:text-amber-700 hover:bg-amber-50' 
                        : 'text-primary hover:text-primary-700 hover:bg-primary/5'}"
                      onclick={() => ay.is_active ? doDeactivate(ay.id) : router.post(`/admin/academic-years/${ay.id}/activate`)}
                    >
                      {#if ay.is_active}
                        <Pause class="mr-1.5 h-3.5 w-3.5" />
                        Deactivate
                      {:else}
                        <Play class="mr-1.5 h-3.5 w-3.5" />
                        Activate
                      {/if}
                    </Button>
                  </div>
                </Table.Cell>
              </Table.Row>
            {:else}
              <Table.Row>
                <Table.Cell colspan={5} class="py-12 text-center text-muted-foreground">
                  No academic years yet. Create one and set it active so applications can be submitted.
                </Table.Cell>
              </Table.Row>
            {/each}
          </Table.Body>
        </Table.Root>
      </div>

      {#if academic_years?.last_page > 1}
        <div class="flex items-center justify-between border-t border-border px-4 py-2">
          <p class="text-sm text-muted-foreground">
            Page {academic_years.current_page} of {academic_years.last_page}
          </p>
          <div class="flex gap-2">
            {#if academic_years.prev_page_url}
              <Link href={academic_years.prev_page_url}>
                <Button variant="outline" size="sm">Previous</Button>
              </Link>
            {/if}
            {#if academic_years.next_page_url}
              <Link href={academic_years.next_page_url}>
                <Button variant="outline" size="sm">Next</Button>
              </Link>
            {/if}
          </div>
        </div>
      {/if}
    </div>
  </div>
</AuthenticatedLayout>