<script>
  import { Link } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Badge } from '@/Components/ui/badge';
  import { Button } from '@/Components/ui/button';

  let { applicant = {}, scores = [], consultation_summary = {} } = $props();

  const isReleased = $derived(consultation_summary.status === 'released');
</script>

<div class="space-y-6">
  <!-- Back link -->
  <Link href="/consultation" class="text-sm text-muted-foreground hover:text-foreground flex items-center gap-1">
    ← Back to Dashboard
  </Link>

  <!-- Header -->
  <div class="flex items-start justify-between">
    <div>
      <h2 class="text-xl font-semibold">{applicant.name}</h2>
      <p class="text-muted-foreground text-sm">{applicant.reference}</p>
    </div>
    <Badge variant={isReleased ? 'success' : 'warning'}>
      {isReleased ? 'Released' : 'Pending'}
    </Badge>
  </div>

  <!-- Score breakdown -->
  <Card>
    <CardHeader>
      <CardTitle>Score Breakdown</CardTitle>
    </CardHeader>
    <CardContent class="p-0">
      <div class="divide-y">
        {#each scores as score}
          <div class="flex items-center justify-between px-6 py-3">
            <span class="font-medium">{score.domain}</span>
            <div class="flex items-center gap-3">
              <span class="text-sm text-muted-foreground">{score.raw}/{score.max}</span>
              <div class="w-24 h-2 bg-muted rounded-full overflow-hidden">
                <div class="h-full bg-primary" style="width: {score.pct}%"></div>
              </div>
              <span class="text-sm font-medium w-10 text-right">{score.pct}%</span>
            </div>
          </div>
        {/each}
      </div>
    </CardContent>
  </Card>

  <!-- Release action -->
  <div class="flex justify-end">
    <form method="POST" action={`/consultation/applicants/${applicant.id}/release`}>
      <Button type="submit" disabled={isReleased}>
        {isReleased ? 'Already Released' : 'Release Results'}
      </Button>
    </form>
  </div>
</div>
