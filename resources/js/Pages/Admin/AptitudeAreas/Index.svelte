<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import * as Table from '@/Components/ui/table';
  import { Plus, Pencil } from 'lucide-svelte';

  let { aptitude_areas = [] } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const list = $derived(Array.isArray(aptitude_areas) ? aptitude_areas : []);

  const breadcrumbs = [{ label: 'Aptitude Areas' }];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <Link href="/admin/aptitude-areas/create">
          <Button class="min-h-[44px]">
            <Plus class="mr-2 h-4 w-4" />
            Add aptitude area
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
      <div class="w-full min-w-0 overflow-x-auto scrollbar-hide">
        <Table.Root>
          <Table.Header class="bg-muted/50">
            <Table.Row>
              <Table.Head>Name</Table.Head>
              <Table.Head>Code</Table.Head>
              <Table.Head>Max items</Table.Head>
              <Table.Head>Order</Table.Head>
              <Table.Head>Status</Table.Head>
              <Table.Head class="text-center">Actions</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#each list as area}
              <Table.Row>
                <Table.Cell class="font-medium">{area.name ?? '—'}</Table.Cell>
                <Table.Cell class="font-mono text-muted-foreground">{area.code ?? '—'}</Table.Cell>
                <Table.Cell>{area.max_items ?? '—'}</Table.Cell>
                <Table.Cell>{area.display_order ?? 0}</Table.Cell>
                <Table.Cell>
                  <Badge variant={area.is_active ? 'success' : 'muted'}>
                    {area.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </Table.Cell>
                <Table.Cell class="text-center">
                  <div class="flex justify-center">
                    <Link href={`/admin/aptitude-areas/${area.id}/edit`}>
                      <Button variant="ghost" size="sm" class="h-8 px-2 text-xs">
                        <Pencil class="mr-1.5 h-3.5 w-3.5" />
                        Edit
                      </Button>
                    </Link>
                  </div>
                </Table.Cell>
              </Table.Row>
            {:else}
              <Table.Row>
                <Table.Cell colspan={6} class="py-12 text-center text-muted-foreground">
                  No aptitude areas yet. Add one to use in grading and result templates.
                </Table.Cell>
              </Table.Row>
            {/each}
          </Table.Body>
        </Table.Root>
      </div>
    </div>
  </div>
</AuthenticatedLayout>
