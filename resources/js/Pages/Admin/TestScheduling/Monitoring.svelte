<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import * as Table from '@/Components/ui/table';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import ElapsedTime from '@/Components/ElapsedTime.svelte';
  import { ClipboardList, Eye, Pencil } from 'lucide-svelte';
  import SwitchableListView from '@/Components/SwitchableListView.svelte';

  import { formatDate } from '@/lib/date-utils';

  let viewMode = $state('responsive');

  let { sessions = [], isProctorView = false, breadcrumbParent = { label: 'Exam Monitoring', href: '/admin/exam-monitoring' } } = $props();

  const breadcrumbs = $derived([
    breadcrumbParent,
    ...(isProctorView ? [{ label: 'Monitoring' }] : [])
  ]);

  function rosterHref(sessionId) {
    return isProctorView ? `/proctor/sessions/${sessionId}` : `/admin/test-admin/sessions/${sessionId}/roster`;
  }

  $effect(() => {
    const interval = setInterval(() => {
      router.reload({ only: ['sessions'] });
    }, 15000);
    return () => clearInterval(interval);
  });



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
      <div class="min-w-0 max-w-full">
        <SwitchableListView bind:viewMode class="sm:space-y-3">
          {#snippet table()}
            <Table.Root class="w-full min-w-[640px] text-sm">
              <Table.Header class="bg-muted/50">
                <Table.Row>
                  <Table.Head class="px-4 py-3">Room</Table.Head>
                  <Table.Head class="px-4 py-3">Date</Table.Head>
                  <Table.Head class="px-4 py-3">Start time</Table.Head>
                  <Table.Head class="px-4 py-3">Total</Table.Head>
                  <Table.Head class="px-4 py-3">Present</Table.Head>
                  <Table.Head class="px-4 py-3">Submitted</Table.Head>
                  <Table.Head class="px-4 py-3">Elapsed</Table.Head>
                  <Table.Head class="px-4 py-3 text-center">Actions</Table.Head>
                </Table.Row>
              </Table.Header>
              <Table.Body>
                {#each sessions as session (session.id)}
                  <Table.Row>
                    <Table.Cell class="px-4 font-medium">{session.room?.name ?? '—'}{session.room?.building ? ` (${session.room.building})` : ''}</Table.Cell>
                    <Table.Cell class="px-4">{formatDate(session.date)}</Table.Cell>
                    <Table.Cell class="px-4">{formatTime(session.start_time)}</Table.Cell>
                    <Table.Cell class="px-4">{session.total ?? 0}</Table.Cell>
                    <Table.Cell class="px-4">{session.present ?? 0}</Table.Cell>
                    <Table.Cell class="px-4">{session.submitted ?? 0}</Table.Cell>
                    <Table.Cell class="px-4">
                      <ElapsedTime startedAt={session.started_at} />
                    </Table.Cell>
                    <Table.Cell class="px-4 text-center">
                      <div class="flex justify-center gap-2">
                        <Link href={rosterHref(session.id)}>
                          <Button variant="ghost" size="sm" class="h-8 px-2 text-xs">
                            <ClipboardList class="mr-1.5 h-3.5 w-3.5" />
                            View Session
                          </Button>
                        </Link>
                      </div>
                    </Table.Cell>
                  </Table.Row>
                {/each}
              </Table.Body>
            </Table.Root>
          {/snippet}

          {#snippet cards()}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {#each sessions as session (session.id)}
                <div class="rounded-xl border border-border bg-card shadow-sm hover:shadow-md transition-all">
                  <div class="p-4 space-y-4">
                    <div class="flex justify-between items-start">
                      <div>
                        <p class="font-bold tracking-tight leading-none mb-1">{session.room?.name ?? '—'}{session.room?.building ? ` (${session.room.building})` : ''}</p>
                        <p class="text-sm text-muted-foreground">{formatDate(session.date)} • {formatTime(session.start_time)}</p>
                      </div>
                      <Badge variant="outline" class="font-mono bg-muted/50">
                        <ElapsedTime startedAt={session.started_at} />
                      </Badge>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center text-sm py-2 border-y border-border/50">
                      <div>
                        <p class="text-muted-foreground text-[11px] font-semibold uppercase tracking-wider mb-1">Total</p>
                        <p class="font-bold text-lg">{session.total ?? 0}</p>
                      </div>
                      <div>
                        <p class="text-muted-foreground text-[11px] font-semibold uppercase tracking-wider mb-1">Present</p>
                        <p class="font-bold text-lg">{session.present ?? 0}</p>
                      </div>
                      <div>
                        <p class="text-muted-foreground text-[11px] font-semibold uppercase tracking-wider mb-1">Done</p>
                        <p class="font-bold text-lg">{session.submitted ?? 0}</p>
                      </div>
                    </div>

                    <div class="flex">
                      <Link href={rosterHref(session.id)} class="w-full">
                        <Button variant="outline" size="sm" class="w-full min-h-[40px] font-semibold">
                          <ClipboardList class="mr-1.5 h-3.5 w-3.5" />
                          View Session
                        </Button>
                      </Link>
                    </div>
                  </div>
                </div>
              {/each}
            </div>
          {/snippet}
        </SwitchableListView>
      </div>
    {/if}
  </div>
</AuthenticatedLayout>
