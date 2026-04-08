<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import * as Table from '@/Components/ui/table';
  import { GraduationCap, PlusCircle, Layers, FileQuestion } from 'lucide-svelte';

  let {
    title = 'Grading',
    description = 'Input and manage exam scores.',
    grading_sessions = [],
    completed_exams_without_grading = [],
    exam_domains_count = 0,
  } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);

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

  function openGradingSession(examSessionId) {
    router.post('/grading', { exam_session_id: examSessionId });
  }

  const breadcrumbs = $derived([{ label: 'Grading' }]);
</script>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="space-y-6 min-w-0">
    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">{success}</div>
    {/if}

    {#if exam_domains_count === 0}
      <Card variant="glass" class="border-amber-500/50 bg-amber-500/5">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
            <Layers class="h-5 w-5" />
            No grading pillars configured
          </CardTitle>
          <CardDescription>
            Grade exam domains (pillars) must be set up before you can open grading sessions. Run
            <span class="font-mono text-sm">./vendor/bin/sail artisan db:seed</span> to create default exam domains, or add them via
            <Link href="/admin/exam-domains" class="font-medium text-primary underline hover:no-underline">Admin → Exam pillars</Link>.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Link href="/admin/exam-domains">
            <Button class="min-h-[44px]">
              <Layers class="mr-2 h-4 w-4" />
              Go to Exam pillars
            </Button>
          </Link>
        </CardContent>
      </Card>
    {:else}
      <!-- Active grading sessions -->
      <Card variant="glass">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <GraduationCap class="h-5 w-5" />
            Grading sessions
          </CardTitle>
          <CardDescription>Open sessions where scores are being or have been entered.</CardDescription>
        </CardHeader>
        <CardContent>
          {#if grading_sessions.length > 0}
            <div class="rounded-lg border border-border overflow-hidden min-w-0">
              <Table.Root class="w-full min-w-[520px]">
                <Table.Header class="bg-muted/50">
                  <Table.Row>
                    <Table.Head class="px-4 py-3">Session</Table.Head>
                    <Table.Head class="px-4 py-3">Date</Table.Head>
                    <Table.Head class="px-4 py-3">Progress</Table.Head>
                    <Table.Head class="px-4 py-3 text-right">Action</Table.Head>
                  </Table.Row>
                </Table.Header>
                <Table.Body>
                  {#each grading_sessions as gs (gs.id)}
                    <Table.Row>
                      <Table.Cell class="px-4 py-3 font-medium">
                        #{gs.exam_session_id} — {gs.room_name ?? '—'}
                      </Table.Cell>
                      <Table.Cell class="px-4 py-3 text-muted-foreground">
                        {formatDate(gs.exam_date)} {formatTime(gs.exam_time)}
                      </Table.Cell>
                      <Table.Cell class="px-4 py-3">
                        <Badge variant={gs.status === 'finalized' ? 'success' : 'warning'}>
                          {gs.applicants_scored ?? 0} / {gs.applicants_total ?? 0} scored
                        </Badge>
                      </Table.Cell>
                      <Table.Cell class="px-4 py-3 text-right">
                        <Link href={`/grading/sessions/${gs.id}`}>
                          <Button variant="outline" size="sm" class="min-h-[44px]">
                            {gs.status === 'finalized' ? 'View' : 'Input scores'}
                          </Button>
                        </Link>
                      </Table.Cell>
                    </Table.Row>
                  {/each}
                </Table.Body>
              </Table.Root>
            </div>
          {:else}
            <p class="py-8 text-center text-muted-foreground">No grading sessions yet. Open one from completed exams below.</p>
          {/if}
        </CardContent>
      </Card>

      <!-- Completed exams without grading -->
      <Card variant="glass">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <FileQuestion class="h-5 w-5" />
            Completed exams (ready for grading)
          </CardTitle>
          <CardDescription>Exam sessions that are completed but have no grading session yet.</CardDescription>
        </CardHeader>
        <CardContent>
          {#if completed_exams_without_grading.length > 0}
            <div class="rounded-lg border border-border overflow-hidden min-w-0">
              <Table.Root class="w-full min-w-[520px]">
                <Table.Header class="bg-muted/50">
                  <Table.Row>
                    <Table.Head class="px-4 py-3">Session</Table.Head>
                    <Table.Head class="px-4 py-3">Date</Table.Head>
                    <Table.Head class="px-4 py-3">Applicants</Table.Head>
                    <Table.Head class="px-4 py-3 text-right">Action</Table.Head>
                  </Table.Row>
                </Table.Header>
                <Table.Body>
                  {#each completed_exams_without_grading as es (es.id)}
                    <Table.Row>
                      <Table.Cell class="px-4 py-3 font-medium">#{es.id} — {es.room_name ?? '—'}</Table.Cell>
                      <Table.Cell class="px-4 py-3 text-muted-foreground">{formatDate(es.exam_date)} {formatTime(es.exam_time)}</Table.Cell>
                      <Table.Cell class="px-4 py-3">{es.applicants_count ?? 0}</Table.Cell>
                      <Table.Cell class="px-4 py-3 text-right">
                        <Button variant="default" size="sm" class="min-h-[44px]" onclick={() => openGradingSession(es.id)}>
                          <PlusCircle class="mr-2 h-4 w-4" />
                          Open grading session
                        </Button>
                      </Table.Cell>
                    </Table.Row>
                  {/each}
                </Table.Body>
              </Table.Root>
            </div>
          {:else}
            <p class="py-8 text-center text-muted-foreground">No completed exams waiting for grading.</p>
          {/if}
        </CardContent>
      </Card>
    {/if}
  </div>
</AuthenticatedLayout>
