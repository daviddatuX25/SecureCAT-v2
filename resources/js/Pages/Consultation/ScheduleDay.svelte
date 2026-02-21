<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import { Input } from '@/Components/ui/input';
  import * as Table from '@/Components/ui/table';
  import { ArrowLeft, Calendar, CheckSquare, Square } from 'lucide-svelte';

  let { batches = [], applicantsByBatch = {} } = $props();

  let selectedBatch = $state(null);
  let selectedApplicants = $state(new Set());
  let consultationDate = $state('2025-02-22');

  const applicants = $derived(selectedBatch ? (applicantsByBatch[selectedBatch] ?? []) : []);

  function toggleBatch(id) {
    selectedBatch = selectedBatch === id ? null : id;
    selectedApplicants = new Set();
  }

  function toggleAll() {
    if (selectedApplicants.size === applicants.length) {
      selectedApplicants = new Set();
    } else {
      selectedApplicants = new Set(applicants.map((a) => a.applicant_id));
    }
  }

  function toggleOne(id) {
    const s = new Set(selectedApplicants);
    if (s.has(id)) s.delete(id);
    else s.add(id);
    selectedApplicants = s;
  }

  function scheduleConsultation() {
    router.post('/consultation/schedule', {
      scheduled_date: consultationDate,
      applicant_ids: Array.from(selectedApplicants),
      grading_session_id: selectedBatch,
    });
  }

  function formatDate(value) {
    if (!value) return '—';
    const s = String(value).split('T')[0];
    if (!s) return '—';
    const [y, m, d] = s.split('-').map(Number);
    return new Date(y, (m || 1) - 1, d || 1).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }
</script>

<svelte:head>
  <title>Schedule consultation day - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <Link href="/consultation" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1 min-h-[44px] items-center">
      <ArrowLeft class="h-4 w-4" />
      Back to consultation
    </Link>

    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Calendar class="h-5 w-5" />
          Schedule consultation day
        </CardTitle>
        <CardDescription>Select a batch (only applicants with printed results). Choose date and applicants for consultation day.</CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <div>
          <label for="batch" class="text-sm font-medium block mb-2">Batch</label>
          <div class="flex flex-wrap gap-2">
            {#each batches as b}
              <button
                type="button"
                onclick={() => toggleBatch(b.id)}
                class="rounded-lg border px-4 py-2 text-sm font-medium {selectedBatch === b.id ? 'border-primary bg-primary/10 text-primary' : 'border-border hover:bg-muted/50'}"
              >
                {b.name} · {formatDate(b.exam_date)} · {b.printed_count}/{b.total} printed
              </button>
            {/each}
          </div>
        </div>

        {#if selectedBatch}
          <div>
            <label for="date" class="text-sm font-medium block mb-1">Consultation date</label>
            <Input id="date" type="date" bind:value={consultationDate} class="w-[180px]" />
          </div>

          <div>
            <div class="flex flex-wrap gap-3 mb-3">
              <Button variant="outline" size="sm" onclick={toggleAll} class="min-h-[44px]">
                {selectedApplicants.size === applicants.length ? 'Deselect all' : 'Select all'}
              </Button>
              <Button onclick={scheduleConsultation} disabled={selectedApplicants.size === 0} class="min-h-[44px]">
                Schedule for {consultationDate}
              </Button>
            </div>

            <div class="rounded-lg border border-border overflow-hidden min-w-0">
              <Table.Root class="w-full min-w-[480px]">
                <Table.Header class="bg-muted/50">
                  <Table.Row>
                    <Table.Head class="px-4 py-3 w-10">
                      <button type="button" onclick={toggleAll} aria-label="Toggle all">
                        {selectedApplicants.size === applicants.length ? '✓' : '○'}
                      </button>
                    </Table.Head>
                    <Table.Head class="px-4 py-3">Reference</Table.Head>
                    <Table.Head class="px-4 py-3">Name</Table.Head>
                  </Table.Row>
                </Table.Header>
                <Table.Body>
                  {#each applicants as app}
                    <Table.Row>
                      <Table.Cell class="px-4 py-3">
                        <button type="button" onclick={() => toggleOne(app.applicant_id)} aria-label="Toggle">
                          {selectedApplicants.has(app.applicant_id) ? '✓' : '○'}
                        </button>
                      </Table.Cell>
                      <Table.Cell class="px-4 py-3">{app.reference}</Table.Cell>
                      <Table.Cell class="px-4 py-3">{app.name}</Table.Cell>
                    </Table.Row>
                  {/each}
                </Table.Body>
              </Table.Root>
            </div>
          </div>
        {/if}
      </CardContent>
    </Card>
  </div>
</AuthenticatedLayout>
