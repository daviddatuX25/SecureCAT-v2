<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { router } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';

  let { batches = [], flash = {} } = $props();

  const breadcrumbs = $derived([
  { label: 'Release & Consultation', href: '/consultation' },
  { label: 'Schedule Consultations' }
]);
</script>

<svelte:head>
  <title>Consultation Schedule - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
<div class="space-y-8 pb-8">
  {#if flash?.success}
    <div class="glass-panel p-4 rounded-xl border border-success/20 bg-success/5 text-success text-sm shadow-sm flex items-center gap-2">
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      {flash.success}
    </div>
  {/if}
  {#if flash?.error}
    <div class="glass-panel p-4 rounded-xl border border-destructive/20 bg-destructive/5 text-destructive text-sm shadow-sm flex items-center gap-2">
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
      {flash.error}
    </div>
  {/if}

  <Card class="overflow-hidden border-border/50 shadow-sm glass-panel bg-card/60">
    <div class="p-0 divide-y divide-border/50">
      {#if batches.length === 0}
        <div class="p-12 text-center text-muted-foreground">
          <p class="text-lg font-medium">No Pending Batches</p>
          <p class="text-sm">There are no batches requiring consultation scheduling.</p>
        </div>
      {/if}
      {#each batches as batch}
        <div class="group">
          <!-- Batch Header Row -->
          <div 
            class="flex flex-col sm:flex-row sm:items-center justify-between px-6 py-4 cursor-pointer transition-colors hover:bg-muted/10 focus-visible:outline-none focus-visible:bg-muted/10" 
            onclick={() => expandedBatch = expandedBatch === batch.id ? null : batch.id}
            onkeydown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); expandedBatch = expandedBatch === batch.id ? null : batch.id; } }}
            role="button"
            tabindex="0"
            aria-expanded={expandedBatch === batch.id}
          >
            <div class="mb-3 sm:mb-0">
              <p class="font-semibold text-foreground flex items-center gap-2">
                {batch.name}
                <svg class={`h-4 w-4 text-muted-foreground transition-transform ${expandedBatch === batch.id ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </p>
              <div class="flex items-center gap-3 text-sm text-muted-foreground mt-1">
                <span>Exam: {batch.exam_date}</span>
                <span class="text-border/50">•</span>
                <span>Printed: {batch.printed_count}/{batch.total}</span>
              </div>
            </div>
            <Badge variant={batch.printed_count === batch.total ? 'success' : 'secondary'} class="shadow-sm truncate shrink-0">
              {batch.printed_count}/{batch.total} Printed
            </Badge>
          </div>

          <!-- Expanded Content -->
          {#if expandedBatch === batch.id}
            <div class="px-6 py-5 bg-muted/20 border-t border-border/30">
              <p class="text-sm font-semibold mb-3 text-foreground tracking-tight">Applicants in Batch</p>
              <div class="space-y-2 mb-6 max-h-[300px] overflow-y-auto pr-2 rounded-lg border border-border/40 bg-background/50 p-3 shadow-inner">
                {#each (batch.applicants ?? []) as applicant}
                  <div class="flex items-center justify-between text-sm p-2 rounded hover:bg-muted/30 transition-colors">
                    <span class="font-medium text-foreground/80">{applicant.name}</span>
                    <Badge variant="outline" class="font-mono text-xs">{applicant.reference}</Badge>
                  </div>
                {/each}
                {#if !batch.applicants?.length}
                  <p class="text-sm text-muted-foreground italic text-center p-4">No applicants in this batch.</p>
                {/if}
              </div>
              
              <div class="flex flex-wrap items-end gap-4 p-4 rounded-xl border border-primary/10 bg-primary/5">
                <div class="flex-1 min-w-[200px]">
                  <label for={`date-input-${batch.id}`} class="block text-xs font-medium text-muted-foreground mb-1">Consultation Date</label>
                  <input
                    id={`date-input-${batch.id}`}
                    type="date"
                    bind:value={selectedDate}
                    class="w-full text-sm flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                  />
                </div>
                <Button
                  class="shadow-md"
                  disabled={!selectedDate}
                  onclick={() => {
                    if (!selectedDate) return;
                    router.post('/consultation/schedule', {
                      grading_session_id: batch.id,
                      scheduled_date: selectedDate,
                    });
                  }}
                >
                  Schedule Batch Consultations
                </Button>
              </div>
            </div>
          {/if}
        </div>
      {/each}
    </div>
  </Card>
</div>
</AuthenticatedLayout>