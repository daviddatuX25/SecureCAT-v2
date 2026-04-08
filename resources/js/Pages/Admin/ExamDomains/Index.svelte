<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Plus, Pencil } from 'lucide-svelte';

  let { exam_domains = [] } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const list = $derived(Array.isArray(exam_domains) ? exam_domains : []);
const breadcrumbs = [{ label: 'Exam Pillars' }];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
      <Link href="/admin/exam-domains/create">
        <Button class="min-h-[44px]">
          <Plus class="mr-2 h-4 w-4" />
          Add pillar
        </Button>
      </Link>
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
              <th class="px-4 py-3 text-left font-medium">Name</th>
              <th class="px-4 py-3 text-left font-medium">Code</th>
              <th class="px-4 py-3 text-left font-medium">Max items</th>
              <th class="px-4 py-3 text-left font-medium">Order</th>
              <th class="px-4 py-3 text-left font-medium">Status</th>
              <th class="px-4 py-3 text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {#each list as domain}
              <tr class="border-t border-border hover:bg-muted/30">
                <td class="px-4 py-3 font-medium">{domain.name ?? '—'}</td>
                <td class="px-4 py-3 font-mono text-muted-foreground">{domain.code ?? '—'}</td>
                <td class="px-4 py-3">{domain.max_items ?? '—'}</td>
                <td class="px-4 py-3">{domain.display_order ?? 0}</td>
                <td class="px-4 py-3">
                  <Badge variant={domain.is_active ? 'success' : 'muted'}>
                    {domain.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </td>
                <td class="px-4 py-3 text-right">
                  <Link href={`/admin/exam-domains/${domain.id}/edit`}>
                    <Button variant="ghost" size="icon" aria-label="Edit">
                      <Pencil class="h-4 w-4" />
                    </Button>
                  </Link>
                </td>
              </tr>
            {:else}
              <tr>
                <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                  No exam pillars yet. Add one to use in grading and result templates.
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </div>
  </div>
</AuthenticatedLayout>
