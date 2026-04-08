<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  const form = useForm({
    name: '',
    code: '',
    description: '',
    max_items: 25,
    display_order: 0,
    is_active: true,
  });

  function submitForm(e) {
    e.preventDefault();
    $form.post('/admin/exam-domains');
  }
const breadcrumbs = [{ label: 'Aptitude Areas', href: '/admin/exam-domains' }, { label: 'Create' }];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-lg space-y-6">
    <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="name" class="text-sm font-medium">Name</label>
        <Input
          id="name"
          bind:value={$form.name}
          placeholder="e.g., Spatial Awareness"
          required
          maxlength="100"
        />
        {#if $form.errors?.name}
          <p class="text-sm text-destructive">{$form.errors.name}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="code" class="text-sm font-medium">Code</label>
        <Input
          id="code"
          bind:value={$form.code}
          placeholder="e.g., SA"
          required
          maxlength="20"
        />
        {#if $form.errors?.code}
          <p class="text-sm text-destructive">{$form.errors.code}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="description" class="text-sm font-medium">Description (optional)</label>
        <textarea
          id="description"
          bind:value={$form.description}
          rows="2"
          class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
          placeholder="Brief description"
        ></textarea>
        {#if $form.errors?.description}
          <p class="text-sm text-destructive">{$form.errors.description}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="max_items" class="text-sm font-medium">Max items (score ceiling)</label>
        <Input
          id="max_items"
          type="number"
          bind:value={$form.max_items}
          min="1"
          max="999"
          required
        />
        {#if $form.errors?.max_items}
          <p class="text-sm text-destructive">{$form.errors.max_items}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="display_order" class="text-sm font-medium">Display order</label>
        <Input
          id="display_order"
          type="number"
          bind:value={$form.display_order}
          min="0"
        />
        {#if $form.errors?.display_order}
          <p class="text-sm text-destructive">{$form.errors.display_order}</p>
        {/if}
      </div>

      <div class="flex items-center gap-2">
        <input
          type="checkbox"
          id="is_active"
          bind:checked={$form.is_active}
          class="h-4 w-4 rounded border-input"
        />
        <label for="is_active" class="text-sm font-medium">Active (included in grading and templates)</label>
      </div>
      {#if $form.errors?.is_active}
        <p class="text-sm text-destructive">{$form.errors.is_active}</p>
      {/if}

      <div class="flex gap-2 pt-4">
        <Button type="submit" disabled={$form.processing}>
          {$form.processing ? 'Creating...' : 'Create pillar'}
        </Button>
        <Link href="/admin/exam-domains">
          <Button type="button" variant="outline">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
