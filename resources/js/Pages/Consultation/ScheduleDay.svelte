<script>
  import { router } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';

  let { batches = [], flash = {} } = $props();

  let expandedBatch = $state(null);
  let selectedDate = $state('');
</script>

<div class="space-y-6">
  {#if flash?.success}
    <div class="p-3 rounded-lg bg-green-50 text-green-800 text-sm">{flash.success}</div>
  {/if}
  {#if flash?.error}
    <div class="p-3 rounded-lg bg-red-50 text-red-800 text-sm">{flash.error}</div>
  {/if}

  <Card>
    <CardHeader>
      <CardTitle>Schedule Applicants by Batch</CardTitle>
    </CardHeader>
    <CardContent class="p-0 divide-y">
      {#each batches as batch}
        <div>
          <div class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-muted/50" onclick={() => expandedBatch = expandedBatch === batch.id ? null : batch.id}>
            <div>
              <p class="font-medium">{batch.name}</p>
              <p class="text-sm text-muted-foreground">
                Exam date: {batch.exam_date} · Printed: {batch.printed_count}/{batch.total}
              </p>
            </div>
            <Badge variant={batch.printed_count === batch.total ? 'success' : 'secondary'}>
              {batch.printed_count}/{batch.total}
            </Badge>
          </div>

          {#if expandedBatch === batch.id}
            <div class="px-6 py-4 bg-muted/30 border-t">
              <p class="text-sm font-medium mb-3">Scheduled Applicants</p>
              <div class="space-y-2 mb-4">
                {#each (batch.applicants ?? []) as applicant}
                  <div class="flex items-center justify-between text-sm">
                    <span>{applicant.name}</span>
                    <span class="text-muted-foreground">{applicant.reference}</span>
                  </div>
                {/each}
                {#if !batch.applicants?.length}
                  <p class="text-sm text-muted-foreground">No applicants scheduled.</p>
                {/if}
              </div>
              <div class="flex items-center gap-3">
                <input
                  type="date"
                  bind:value={selectedDate}
                  class="text-sm border rounded px-3 py-2"
                />
                <Button
                  size="sm"
                  onclick={() => {
                    if (!selectedDate) return;
                    router.post('/consultation/schedule', {
                      grading_session_id: batch.id,
                      scheduled_date: selectedDate,
                    });
                  }}
                >
                  Schedule
                </Button>
              </div>
            </div>
          {/if}
        </div>
      {/each}
    </CardContent>
  </Card>
</div>