<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import { Badge } from '@/Components/ui/badge';
  import * as Table from '@/Components/ui/table';
  import { Button } from '@/Components/ui/button';
  import { ClipboardList, ChevronLeft, ChevronRight } from 'lucide-svelte';

  let { sessions, filters = {}, seasons = [], active_season_id = null, statuses = [] } = $props();

  const breadcrumbs = [{ label: 'My Sessions' }];

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const error   = $derived($page.props.flash?.error   ?? null);

  function statusVariant(status) {
    if (status === 'draft')       return 'muted';
    if (status === 'published')   return 'success';
    if (status === 'in_progress') return 'warning';
    if (status === 'completed')   return 'outline';
    if (status === 'cancelled')   return 'danger';
    return 'outline';
  }

  function statusLabel(status) {
    const labels = {
      draft:       'Draft',
      published:   'Published',
      in_progress: 'In progress',
      completed:   'Completed',
      cancelled:   'Cancelled',
    };
    return labels[status] ?? status;
  }

  function formatDate(value) {
    if (!value) return '—';
    const part = String(value).split('T')[0];
    const [y, m, d] = part.split('-').map(Number);
    return new Date(y, (m || 1) - 1, d || 1)
      .toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function formatTime(value) {
    if (!value) return '—';
    const parts = String(value).split(':');
    const hours = parseInt(parts[0], 10) || 0;
    const mins  = parseInt(parts[1], 10) || 0;
    const h     = hours % 12 || 12;
    return `${h}:${String(mins).padStart(2, '0')} ${hours < 12 ? 'AM' : 'PM'}`;
  }
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="mt-1 text-sm text-muted-foreground">Exam sessions assigned to you. Click "Open roster" to manage attendance and submissions.</p>
      </div>
    </div>

    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">{success}</div>
    {/if}
    {#if error}
      <div class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">{error}</div>
    {/if}

    <!-- Session list -->
    <div class="rounded-lg border border-border bg-card">
      {#if (sessions?.data ?? []).length > 0}
        <div class="overflow-x-auto scrollbar-hide">
          <Table.Root>
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head>Date</Table.Head>
                <Table.Head>Time</Table.Head>
                <Table.Head>Room</Table.Head>
                <Table.Head>Status</Table.Head>
                <Table.Head>Applicants</Table.Head>
                <Table.Head class="text-center">Actions</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each sessions.data as session (session.id)}
                <Table.Row>
                  <Table.Cell class="font-medium">{formatDate(session.date)}</Table.Cell>
                  <Table.Cell class="text-muted-foreground">
                    {formatTime(session.start_time)}{#if session.end_time} – {formatTime(session.end_time)}{/if}
                  </Table.Cell>
                  <Table.Cell>{session.room?.name ?? '—'}</Table.Cell>
                  <Table.Cell>
                    <Badge variant={statusVariant(session.status)}>{statusLabel(session.status)}</Badge>
                  </Table.Cell>
                  <Table.Cell class="text-muted-foreground">{session.applicants_count ?? '—'}</Table.Cell>
                  <Table.Cell class="text-center">
                    <div class="flex justify-center">
                      <Link href={`/admin/test-admin/sessions/${session.id}/roster`}>
                        <Button size="sm" class="h-8 px-2 text-xs">
                          <ClipboardList class="mr-1.5 h-3.5 w-3.5" />
                          Open examinees
                        </Button>
                      </Link>
                    </div>
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>

        <!-- Pagination -->
        {#if sessions.last_page > 1}
          <div class="flex items-center justify-between px-4 py-3 border-t border-border">
            <p class="text-sm text-muted-foreground">
              Showing {sessions.from}–{sessions.to} of {sessions.total}
            </p>
            <div class="flex gap-2">
              {#if sessions.prev_page_url}
                <Link href={sessions.prev_page_url}>
                  <Button variant="outline" size="sm" class="min-h-[36px]">
                    <ChevronLeft class="h-4 w-4" />
                  </Button>
                </Link>
              {/if}
              {#if sessions.next_page_url}
                <Link href={sessions.next_page_url}>
                  <Button variant="outline" size="sm" class="min-h-[36px]">
                    <ChevronRight class="h-4 w-4" />
                  </Button>
                </Link>
              {/if}
            </div>
          </div>
        {/if}
      {:else}
        <div class="px-4 py-16 text-center">
          <ClipboardList class="mx-auto h-12 w-12 text-muted-foreground/40 mb-4" />
          <p class="text-sm text-muted-foreground">No sessions assigned to you yet.</p>
        </div>
      {/if}
    </div>
  </div>
</AuthenticatedLayout>
