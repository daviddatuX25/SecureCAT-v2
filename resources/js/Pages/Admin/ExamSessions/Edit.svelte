<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link } from '@inertiajs/svelte';
  import EditForm from './EditForm.svelte';

  let { session, rooms = [], proctors = [] } = $props();
</script>

<svelte:head>
  <title>Edit Exam Session - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="max-w-lg space-y-6">
    <div class="flex items-center gap-4">
      <Link href="/admin/exam-sessions" class="text-sm text-muted-foreground hover:text-foreground">Back to exam sessions</Link>
      <h1 class="text-2xl font-bold">Edit Exam Session</h1>
    </div>

    {#if session?.id}
      {#key session.id}
        <EditForm {session} {rooms} {proctors} />
      {/key}
    {/if}
  </div>
</AuthenticatedLayout>
