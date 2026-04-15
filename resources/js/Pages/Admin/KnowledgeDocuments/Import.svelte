<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import { FileUpload } from '@/Components/ui/file-upload';
  import { Upload } from 'lucide-svelte';

  const breadcrumbs = [
    { label: 'Knowledge Documents', href: '/admin/knowledge-documents' },
    { label: 'Import' },
  ];

  const form = useForm({
    file: null,
    title: '',
    metadata: {
      category: '',
      year: '',
      description: '',
      tags: [],
    },
  });

  let tagsInput = $state('');
  let selectedFile = $state(null);

  function submitForm(e) {
    e.preventDefault();
    if (!selectedFile) return;
    const tags = tagsInput
      ? tagsInput.split(',').map((t) => t.trim()).filter(Boolean)
      : [];
    $form.transform((data) => ({
      ...data,
      file: selectedFile,
      metadata: { ...data.metadata, tags },
    }));
    $form.post('/admin/knowledge-documents/import', { forceFormData: true });
  }
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-2xl space-y-6">
    <p class="text-sm text-muted-foreground">
      Upload a CSV file. Rows will be converted to factual narrative sentences. <strong>Metadata defines the document</strong>—set category, year, and description so retrieval can find it.
    </p>

    <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="file" class="text-sm font-medium leading-none">CSV file</label>
        <FileUpload
          label="Upload CSV file"
          accept=".csv,.txt"
          maxSize="2MB"
          bind:files={selectedFile}
        />
        <p class="text-xs text-muted-foreground">UTF-8 encoding. First row is used as headers.</p>
        {#if $form.errors?.file}
          <p class="text-sm text-destructive">{$form.errors.file}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="title" class="text-sm font-medium leading-none">Title</label>
        <Input id="title" bind:value={$form.title} placeholder="e.g. Engineering success rates 2024" required class="min-h-[44px]" />
        {#if $form.errors?.title}
          <p class="text-sm text-destructive">{$form.errors.title}</p>
        {/if}
      </div>

      <p class="text-sm font-medium">Metadata (defines what this document is for retrieval)</p>
      <div class="grid gap-4 sm:grid-cols-2">
        <div class="space-y-2">
          <label for="category" class="text-sm font-medium leading-none">Category (optional)</label>
          <Input id="category" bind:value={$form.metadata.category} placeholder="e.g. Engineering" class="min-h-[44px]" />
          {#if $form.errors?.['metadata.category']}
            <p class="text-sm text-destructive">{$form.errors['metadata.category']}</p>
          {/if}
        </div>
        <div class="space-y-2">
          <label for="year" class="text-sm font-medium leading-none">Year (optional)</label>
          <Input id="year" bind:value={$form.metadata.year} placeholder="e.g. 2024" class="min-h-[44px]" />
          {#if $form.errors?.['metadata.year']}
            <p class="text-sm text-destructive">{$form.errors['metadata.year']}</p>
          {/if}
        </div>
      </div>

      <div class="space-y-2">
        <label for="meta-description" class="text-sm font-medium leading-none">Description (optional)</label>
        <Input id="meta-description" bind:value={$form.metadata.description} placeholder="e.g. Success rates by course" class="min-h-[44px]" />
      </div>

      <div class="space-y-2">
        <label for="tags" class="text-sm font-medium leading-none">Tags (optional, comma-separated)</label>
        <Input id="tags" bind:value={tagsInput} placeholder="e.g. success_rates, engineering" class="min-h-[44px]" />
      </div>

      <div class="flex gap-3 pt-2">
        <Button type="submit" disabled={$form.processing} class="min-h-[44px]">
          <Upload class="mr-2 size-4" />
          {$form.processing ? 'Importing…' : 'Import CSV'}
        </Button>
        <Link href="/admin/knowledge-documents">
          <Button type="button" variant="outline" class="min-h-[44px]">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
