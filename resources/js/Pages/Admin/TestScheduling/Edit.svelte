<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link } from '@inertiajs/svelte';
  import EditForm from './EditForm.svelte';

  let { session, rooms = [], proctors = [] } = $props();
const breadcrumbs = [{ label: 'Exam Sessions', href: '/admin/test-scheduling' }, { label: 'Edit Exam Session' }];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-lg space-y-6">
    <div class="flex items-center gap-4">
      <Link href="/admin/test-scheduling" class="text-sm text-muted-foreground hover:text-foreground">Back to exam sessions</Link>
    </div>

    {#if session?.id}
      {#key session.id}
        <EditForm {session} {rooms} {proctors} />
      {/key}
    {/if}
  </div>
</AuthenticatedLayout>
