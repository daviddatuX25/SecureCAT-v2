<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { MessageSquare } from 'lucide-svelte';

  let { title = 'Consultation', description = 'Review scores and release consultations.' } = $props();
</script>

<svelte:head>
  <title>Consultation - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold">{title}</h1>
      <p class="mt-1 text-sm text-muted-foreground">{description}</p>
    </div>
    <div class="rounded-lg border border-dashed border-border bg-muted/30 p-12 text-center">
      <MessageSquare class="mx-auto h-12 w-12 text-muted-foreground" />
      <p class="mt-4 text-muted-foreground">Consultation management will appear here.</p>
    </div>
  </div>
</AuthenticatedLayout>
