<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Button } from '@/Components/ui/button';
  import { BookOpen } from 'lucide-svelte';

  let { title = 'Courses', description = 'Manage courses.' } = $props();
</script>

<svelte:head>
  <title>Courses - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold">{title}</h1>
        <p class="mt-1 text-sm text-muted-foreground">{description}</p>
      </div>
      <Button disabled class="min-h-[44px]">Add Course (coming soon)</Button>
    </div>
    <div class="rounded-lg border border-dashed border-border bg-muted/30 p-12 text-center">
      <BookOpen class="mx-auto h-12 w-12 text-muted-foreground" />
      <p class="mt-4 text-muted-foreground">Course list and CRUD will appear here.</p>
    </div>
  </div>
</AuthenticatedLayout>
