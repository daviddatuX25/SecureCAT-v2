<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import * as Card from '@/Components/ui/card';
  import * as Table from '@/Components/ui/table';
  import { Plus, Pencil, Trash2 } from 'lucide-svelte';

  let { documents = [], ai_companion_persona = '' } = $props();

  const page = usePage();
  const flash = $derived($page.props.flash ?? {});
  const breadcrumbs = [{ label: 'AI Companion' }];

  // Tab state
  let activeTab = $state('documents');

  // Persona form
  const form = useForm({ ai_companion_persona });

  $effect(() => {
    form.update((f) => ({ ...f, ai_companion_persona }));
  });

  let savingPersona = $state(false);

  function submitPersona(e) {
    e.preventDefault();
    savingPersona = true;
    $form.put('/admin/ai-companion/persona', {
      preserveScroll: true,
      onFinish: () => { savingPersona = false; },
    });
  }

  function deleteDocument(id) {
    if (confirm('Delete this document? This cannot be undone.')) {
      router.delete(`/admin/knowledge-documents/${id}`, { preserveScroll: true });
    }
  }
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">

    {#if flash.success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">{flash.success}</div>
    {/if}

    <!-- Tab bar -->
    <div class="flex gap-1 border-b border-border">
      <button
        type="button"
        class="px-4 py-2 text-sm font-medium transition-colors {activeTab === 'documents'
          ? 'border-b-2 border-primary text-primary'
          : 'text-muted-foreground hover:text-foreground'}"
        onclick={() => (activeTab = 'documents')}
      >
        Knowledge Documents
      </button>
      <button
        type="button"
        class="px-4 py-2 text-sm font-medium transition-colors {activeTab === 'persona'
          ? 'border-b-2 border-primary text-primary'
          : 'text-muted-foreground hover:text-foreground'}"
        onclick={() => (activeTab = 'persona')}
      >
        Persona
      </button>
    </div>

    <!-- Tab: Knowledge Documents -->
    {#if activeTab === 'documents'}
      <div class="space-y-4">
        <div class="flex justify-end">
          <Link href="/admin/knowledge-documents/create">
            <Button class="min-h-[44px]">
              <Plus class="mr-2 h-4 w-4" />
              Add Document
            </Button>
          </Link>
        </div>

        <div class="glass-panel rounded-2xl overflow-hidden p-0">
          <Table.Root>
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="px-4 py-3">Title</Table.Head>
                <Table.Head class="px-4 py-3">Status</Table.Head>
                <Table.Head class="px-4 py-3">Added</Table.Head>
                <Table.Head class="px-4 py-3 text-right">Actions</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each documents as doc (doc.id)}
                <Table.Row>
                  <Table.Cell class="px-4 py-3 font-medium">{doc.title ?? doc.name ?? '—'}</Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Badge variant={doc.status === 'active' ? 'success' : 'muted'}>{doc.status ?? 'active'}</Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3 text-sm text-muted-foreground">{doc.created_at ?? '—'}</Table.Cell>
                  <Table.Cell class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2">
                      <Link href={`/admin/knowledge-documents/${doc.id}/edit`}>
                        <Button variant="ghost" size="icon" aria-label="Edit document">
                          <Pencil class="h-4 w-4" />
                        </Button>
                      </Link>
                      <Button
                        variant="ghost"
                        size="icon"
                        class="text-destructive hover:text-destructive"
                        aria-label="Delete document"
                        onclick={() => deleteDocument(doc.id)}
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </Table.Cell>
                </Table.Row>
              {:else}
                <Table.Row>
                  <Table.Cell colspan={4} class="px-4 py-12 text-center text-muted-foreground">
                    No knowledge documents yet. Add one to get started.
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>
      </div>
    {/if}

    <!-- Tab: Persona -->
    {#if activeTab === 'persona'}
      <Card.Root>
        <Card.Header>
          <Card.Title>AI companion persona</Card.Title>
          <Card.Description>
            System instructions for the AI advisor (tone, guardrails, scope). Used when applicants chat with the advisor.
            Plain text only — no HTML. If empty, a safe default is used.
          </Card.Description>
        </Card.Header>
        <Card.Content>
          <form onsubmit={submitPersona} class="space-y-4">
            <textarea
              bind:value={$form.ai_companion_persona}
              placeholder="e.g. You are an encouraging academic counselor. Base your advice only on the data provided."
              rows="8"
              class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[160px]"
              maxlength="5000"
            ></textarea>
            {#if $form.errors?.ai_companion_persona}
              <p class="text-sm text-destructive">{$form.errors.ai_companion_persona}</p>
            {/if}
            <p class="text-xs text-muted-foreground">Max 5000 characters.</p>
            <Button type="submit" disabled={savingPersona} class="min-h-[44px]">
              {savingPersona ? 'Saving…' : 'Save persona'}
            </Button>
          </form>
        </Card.Content>
      </Card.Root>
    {/if}

  </div>
</AuthenticatedLayout>