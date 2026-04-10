<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import * as Table from '@/Components/ui/table';
  import ElapsedTime from '@/Components/ElapsedTime.svelte';
  import { ClipboardList } from 'lucide-svelte';

  let { sessions = [] } = $props();

  const breadcrumbs = [{ label: 'Exam Monitoring' }];

  $effect(() => {
    const interval = setInterval(() => {
      router.reload({ only: ['sessions'] });
    }, 15000);
    return () => clearInterval(interval);
  });

  function formatDate(value) {
    if (value == null || value === '') return '—';
    const s = String(value);
    const part = s.split('T')[0];
    if (!part) return '—';
    const [y, m, d] = part.split('-').map(Number);
    const date = new Date(y, (m || 1) - 1, d || 1);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function formatTime(value) {
    if (value == null || value === '') return '—';
    const parts = String(value).split(':');
    const hours = parseInt(parts[0], 10) || 0;
    const mins = parseInt(parts[1], 10) || 0;
    const h = hours % 12 || 12;
    const ampm = hours < 12 ? 'AM' : 'PM';
    return `${h}:${String(mins).padStart(2, '0')} ${ampm}`;
  }
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <p class="text-sm text-muted-foreground">
        Live status of in-progress exam sessions. Data refreshes every 15 seconds.
      </p>

    {#if sessions.length === 0}
      <div class="rounded-lg border border-border bg-card p-8 text-center text-muted-foreground">
        <p class="font-medium">No sessions in progress</p>
        <p class="mt-1 text-sm">When proctors start an exam session, it will appear here.</p>
      </div>
    {:else}
      <div class="rounded-lg border border-border overflow-hidden min-w-0 max-w-full">
        <div class="w-full min-w-0 overflow-x-auto">
          <Table.Root>
            <Table.Header>
              <Table.Row>
                <Table.Head>Room</Table.Head>
                <Table.Head>Date</Table.Head>
                <Table.Head>Start time</Table.Head>
                <Table.Head>Total</Table.Head>
                <Table.Head>Present</Table.Head>
                <Table.Head>Submitted</Table.Head>
                <Table.Head>Elapsed</Table.Head>
                <Table.Head class="w-[120px]">Actions</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each sessions as session (session.id)}
                <Table.Row>
                  <Table.Cell class="font-medium">{session.room_name}</Table.Cell>
                  <Table.Cell>{formatDate(session.date)}</Table.Cell>
                  <Table.Cell>{formatTime(session.start_time)}</Table.Cell>
                  <Table.Cell>{session.total ?? 0}</Table.Cell>
                  <Table.Cell>{session.present ?? 0}</Table.Cell>
                  <Table.Cell>{session.submitted ?? 0}</Table.Cell>
                  <Table.Cell>
                    <ElapsedTime startedAt={session.started_at} />
                  </Table.Cell>
                  <Table.Cell>
                    <Link
                      href="/proctor/sessions/{session.id}"
                      class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline min-h-[44px] min-w-[44px] items-center"
                    >
                      <ClipboardList class="h-4 w-4" />
                      Examinees
                    </Link>
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>
      </div>
    {/if}
  </div>
</AuthenticatedLayout>
