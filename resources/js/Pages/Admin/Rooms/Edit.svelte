<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link } from '@inertiajs/svelte';
  import EditForm from './EditForm.svelte';

  let { room } = $props();
</script>

<svelte:head>
  <title>Edit Room - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="max-w-lg space-y-6">
    <div class="flex items-center gap-4">
      <Link href="/admin/rooms" class="text-sm text-muted-foreground hover:text-foreground">Back to rooms</Link>
      <h1 class="text-2xl font-bold">Edit Room</h1>
    </div>

    {#key room?.id}
      <EditForm {room} />
    {/key}
  </div>
</AuthenticatedLayout>
