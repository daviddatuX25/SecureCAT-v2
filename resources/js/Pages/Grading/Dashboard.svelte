<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import * as Table from '@/Components/ui/table';
  import { GraduationCap, PlusCircle, Layers, FileQuestion, UploadCloud, DoorOpen } from 'lucide-svelte';

  let {
    title = 'Grading',
    description = 'Input and manage exam scores.',
    grading_sessions = [],
    completed_exams_without_grading = [],
    aptitude_areas_count = 0,
  } = $props();


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
    router.post('/admin/grading', { exam_session_id: examSessionId });
  }

  const breadcrumbs = $derived([{ label: 'Grading' }]);
</script>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="space-y-6 min-w-0">

    {#if aptitude_areas_count === 0}
      <Card variant="glass" class="border-amber-500/50 bg-amber-500/5">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
            <Layers class="h-5 w-5" />
            No aptitude areas configured
          </CardTitle>
          <CardDescription>
            Aptitude areas must be set up before you can open grading sessions. Run
            <span class="font-mono text-sm">php artisan db:seed</span> to create default aptitude areas, or add them via
            <Link href="/admin/aptitude-areas" class="font-medium text-primary underline hover:no-underline">Admin → Aptitude Areas</Link>.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Link href="/admin/aptitude-areas">
            <Button class="min-h-[44px]">
              <Layers class="mr-2 h-4 w-4" />
              Go to Aptitude Areas
            </Button>
          </Link>
        </CardContent>
      </Card>
    {:else}
      <!-- Active grading sessions -->
      <Card variant="glass">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <GraduationCap class="h-5 w-5" />
              <CardTitle>Grading sessions</CardTitle>
            </div>
            <div class="flex items-center gap-2">
              <Link href="/admin/grading/import">
                <Button variant="outline" size="sm">
                  <UploadCloud class="mr-1.5 h-4 w-4" />
                  Import Scores
                </Button>
              </Link>
              <Link href="/admin/aptitude-areas">
                <Button variant="outline" size="sm">
                  <Layers class="mr-1.5 h-4 w-4" />
                  <span class="hidden sm:inline">Aptitude Areas</span>
                </Button>
              </Link>
            </div>
          </div>
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
                    <Table.Head class="text-center">Action</Table.Head>
                  </Table.Row>
                </Table.Header>
                <Table.Body>
                  {#each grading_sessions as gs (gs.id)}
                    <Table.Row>
                      <Table.Cell class="px-4 py-3 font-medium">
                        #{gs.exam_session_id} — {gs.room_name ?? '—'}
                        {#if gs.exam_session_type === 'direct'}
                          <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 ml-1">Direct</span>
                        {/if}
                      </Table.Cell>
                      <Table.Cell class="px-4 py-3 text-muted-foreground">
                        {formatDate(gs.exam_date)} {formatTime(gs.exam_time)}
                      </Table.Cell>
                      <Table.Cell class="px-4 py-3">
                        <Badge variant={gs.status === 'finalized' ? 'success' : 'warning'}>
                          {gs.applicants_scored ?? 0} / {gs.applicants_total ?? 0} scored
                        </Badge>
                      </Table.Cell>
                      <Table.Cell class="text-center">
                        <Link href={`/admin/grading/sessions/${gs.id}`}>
                          <Button variant="outline" size="sm" class="h-8 px-2 text-xs">
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
                    <Table.Head class="text-center">Action</Table.Head>
                  </Table.Row>
                </Table.Header>
                <Table.Body>
                  {#each completed_exams_without_grading as es (es.id)}
                    <Table.Row>
                      <Table.Cell class="px-4 py-3 font-medium">#{es.id} — {es.room_name ?? '—'}</Table.Cell>
                      <Table.Cell class="px-4 py-3 text-muted-foreground">{formatDate(es.exam_date)} {formatTime(es.exam_time)}</Table.Cell>
                      <Table.Cell class="px-4 py-3">{es.applicants_count ?? 0}</Table.Cell>
                      <Table.Cell class="text-center">
                        <Button
                          variant="default"
                          size="sm"
                          class="h-8 px-2 text-xs"
                          onclick={() => openGradingSession(es.id)}
                        >
                          <PlusCircle class="mr-1.5 h-3.5 w-3.5" />
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
