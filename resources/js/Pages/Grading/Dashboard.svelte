<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import * as Table from '@/Components/ui/table';
  import { ClipboardList, Plus, CheckCircle2, FileQuestion, Eye } from 'lucide-svelte';

  let { grading_sessions = [], completed_exams_without_grading = [] } = $props();

  function statusVariant(status) {
    if (status === 'open') return 'muted';
    if (status === 'in_progress') return 'warning';
    if (status === 'review') return 'outline';
    if (status === 'finalized') return 'success';
    return 'outline';
  }

  function statusLabel(status) {
    const labels = { open: 'Open', in_progress: 'In progress', review: 'Review', finalized: 'Finalized' };
    return labels[status] ?? status;
  }

  function formatDate(value) {
    if (!value) return '—';
    const s = String(value).split('T')[0];
    if (!s) return '—';
    const [y, m, d] = s.split('-').map(Number);
    return new Date(y, (m || 1) - 1, d || 1).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function formatTime(value) {
    if (!value) return '—';
    const parts = String(value).split(':');
    const h = parseInt(parts[0], 10) || 0;
    const m = parseInt(parts[1], 10) || 0;
    return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${h < 12 ? 'AM' : 'PM'}`;
  }

  function openNewSession(examId) {
    router.post('/grading', { exam_session_id: examId });
  }
</script>

<svelte:head>
  <title>Grading - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <div>
      <h1 class="text-2xl font-bold">Grading</h1>
      <p class="mt-1 text-sm text-muted-foreground">Input and manage exam scores. Open or continue a grading session.</p>
    </div>

    <!-- Grading sessions -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <ClipboardList class="h-5 w-5" />
          Grading sessions
        </CardTitle>
        <CardDescription>Active and recent grading sessions. Continue scoring or finalize.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="rounded-lg border border-border overflow-hidden min-w-0">
          <Table.Root class="w-full min-w-[640px]">
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="px-4 py-3">Exam</Table.Head>
                <Table.Head class="px-4 py-3">Date & time</Table.Head>
                <Table.Head class="px-4 py-3">Room</Table.Head>
                <Table.Head class="px-4 py-3">Progress</Table.Head>
                <Table.Head class="px-4 py-3">Status</Table.Head>
                <Table.Head class="px-4 py-3 text-right">Actions</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each grading_sessions as gs}
                <Table.Row>
                  <Table.Cell class="px-4 py-3">Session #{gs.exam_session_id}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{formatDate(gs.exam_date)} {formatTime(gs.exam_time)}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{gs.room_name}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{gs.applicants_scored} / {gs.applicants_total} scored</Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Badge variant={statusVariant(gs.status)}>{statusLabel(gs.status)}</Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3 text-right">
                    <Link href={`/grading/sessions/${gs.id}`}>
                      <Button variant="outline" size="sm" class="min-h-[44px]">
                        <Eye class="h-4 w-4 mr-1.5" />
                        View
                      </Button>
                    </Link>
                  </Table.Cell>
                </Table.Row>
              {:else}
                <Table.Row>
                  <Table.Cell colspan={6} class="px-4 py-12 text-center text-muted-foreground">
                    No grading sessions yet.
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>
      </CardContent>
    </Card>

    <!-- Completed exams without grading -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <FileQuestion class="h-5 w-5" />
          Completed exams without grading
        </CardTitle>
        <CardDescription>Exam sessions that are completed but have no grading session yet. Open one to start scoring.</CardDescription>
      </CardHeader>
      <CardContent>
        {#if completed_exams_without_grading.length > 0}
          <div class="rounded-lg border border-border overflow-hidden min-w-0">
            <Table.Root class="w-full min-w-[520px]">
              <Table.Header class="bg-muted/50">
                <Table.Row>
                  <Table.Head class="px-4 py-3">Exam session</Table.Head>
                  <Table.Head class="px-4 py-3">Date & time</Table.Head>
                  <Table.Head class="px-4 py-3">Room</Table.Head>
                  <Table.Head class="px-4 py-3">Applicants</Table.Head>
                  <Table.Head class="px-4 py-3 text-right">Action</Table.Head>
                </Table.Row>
              </Table.Header>
              <Table.Body>
                {#each completed_exams_without_grading as exam}
                  <Table.Row>
                    <Table.Cell class="px-4 py-3">Session #{exam.id}</Table.Cell>
                    <Table.Cell class="px-4 py-3">{formatDate(exam.exam_date)} {formatTime(exam.exam_time)}</Table.Cell>
                    <Table.Cell class="px-4 py-3">{exam.room_name}</Table.Cell>
                    <Table.Cell class="px-4 py-3">{exam.applicants_count}</Table.Cell>
                    <Table.Cell class="px-4 py-3 text-right">
                      <Button size="sm" class="min-h-[44px]" onclick={() => openNewSession(exam.id)}>
                        <Plus class="h-4 w-4 mr-1.5" />
                        Open grading session
                      </Button>
                    </Table.Cell>
                  </Table.Row>
                {/each}
              </Table.Body>
            </Table.Root>
          </div>
        {:else}
          <div class="rounded-lg border border-dashed border-border bg-muted/30 py-8 text-center text-muted-foreground">
            <CheckCircle2 class="mx-auto h-10 w-10 mb-2 opacity-60" />
            <p>All completed exams have a grading session.</p>
          </div>
        {/if}
      </CardContent>
    </Card>
  </div>
</AuthenticatedLayout>
