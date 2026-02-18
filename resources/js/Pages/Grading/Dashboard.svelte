<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { FileEdit } from 'lucide-svelte';

  let { title = 'Grading', description = 'Input and manage exam scores.' } = $props();
</script>

<svelte:head>
  <title>Grading - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold">{title}</h1>
      <p class="mt-1 text-sm text-muted-foreground">{description}</p>
    </div>
    <div class="rounded-lg border border-dashed border-border bg-muted/30 p-12 text-center">
      <FileEdit class="mx-auto h-12 w-12 text-muted-foreground" />
      <p class="mt-4 text-muted-foreground">Grading interface will appear here.</p>
    </div>
  </div>
</AuthenticatedLayout>
