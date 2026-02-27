<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import * as Table from '@/Components/ui/table';
  import { Plus, Pencil, Trash2 } from 'lucide-svelte';

  let { templates = [] } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);

  let deleteId = $state(null);

  function confirmDelete(id) {
    deleteId = id;
  }

  function cancelDelete() {
    deleteId = null;
  }

  function doDelete() {
    if (deleteId) {
      router.delete(`/admin/admission-slip-templates/${deleteId}`, { onSuccess: () => (deleteId = null) });
    }
  }
</script>

<svelte:head>
  <title>Admission slip templates - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold">Admission slip templates</h1>
        <p class="mt-1 text-sm text-muted-foreground">Manage templates for printing admission slips</p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <Link href="/admin/admission-slip-templates/create">
          <Button class="min-h-[44px]">
            <Plus class="mr-2 h-4 w-4" />
            Create template
          </Button>
        </Link>
      </div>
    </div>

    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">
        {success}
      </div>
    {/if}

    <div class="rounded-lg border border-border overflow-hidden min-w-0 max-w-full">
      <div class="w-full min-w-0">
        <Table.Root class="w-full min-w-[640px]">
          <Table.Header class="bg-muted/50">
            <Table.Row>
              <Table.Head class="px-4 py-3">Name</Table.Head>
              <Table.Head class="px-4 py-3">Mode</Table.Head>
              <Table.Head class="px-4 py-3">Paper</Table.Head>
              <Table.Head class="px-4 py-3">Status</Table.Head>
              <Table.Head class="px-4 py-3 text-right">Actions</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#each templates as t (t.id)}
              <Table.Row>
                <Table.Cell class="px-4 py-3">{t.name}</Table.Cell>
                <Table.Cell class="px-4 py-3">{t.mode ?? 'html'}</Table.Cell>
                <Table.Cell class="px-4 py-3">{t.paper_size ?? 'a4'} {t.orientation === 'landscape' ? '(landscape)' : ''}</Table.Cell>
                <Table.Cell class="px-4 py-3">
                  <Badge variant={t.is_active ? 'success' : 'muted'}>{t.is_active ? 'Active' : 'Inactive'}</Badge>
                </Table.Cell>
                <Table.Cell class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    <Link href={`/admin/admission-slip-templates/${t.id}/edit`}>
                      <Button variant="ghost" size="icon" aria-label="Edit"><Pencil class="h-4 w-4" /></Button>
                    </Link>
                    <Button variant="ghost" size="icon" aria-label="Delete" class="text-destructive" onclick={() => confirmDelete(t.id)}>
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </Table.Cell>
              </Table.Row>
            {:else}
              <Table.Row>
                <Table.Cell colspan={5} class="px-4 py-12 text-center text-muted-foreground">
                  No templates yet.
                </Table.Cell>
              </Table.Row>
            {/each}
          </Table.Body>
        </Table.Root>
      </div>
    </div>
  </div>

  {#if deleteId}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
      <div class="rounded-lg bg-card p-6 shadow-lg max-w-sm w-full">
        <h2 class="text-lg font-semibold">Delete template?</h2>
        <p class="mt-2 text-sm text-muted-foreground">This action cannot be undone.</p>
        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" onclick={cancelDelete}>Cancel</Button>
          <Button variant="destructive" onclick={doDelete}>Delete</Button>
        </div>
      </div>
    </div>
  {/if}
</AuthenticatedLayout>
