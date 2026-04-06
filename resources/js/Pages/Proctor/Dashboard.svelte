<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Calendar } from 'lucide-svelte';

  let { title = 'My Sessions', description = 'View assigned exam sessions.' } = $props();

  const breadcrumbs = $derived([{ label: title }]);
</script>

<svelte:head>
  <title>Proctor - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="space-y-6">
    <div>
      <p class="mt-1 text-sm text-muted-foreground">{description}</p>
    </div>
    <div class="rounded-lg border border-dashed border-border bg-muted/30 p-12 text-center">
      <Calendar class="mx-auto h-12 w-12 text-muted-foreground" />
      <p class="mt-4 text-muted-foreground">Assigned exam sessions will appear here.</p>
    </div>
  </div>
</AuthenticatedLayout>
