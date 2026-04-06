<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Badge } from '@/Components/ui/badge';
  import { Button } from '@/Components/ui/button';
  import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';

  let { applicant = {}, scores = [], consultation_summary = {} } = $props();

  const isReleased = $derived(consultation_summary.status === 'released');

  let releasing = $state(false);
  let dialogOpen = $state(false);

  function releaseConsultation() {
    if (isReleased || releasing) return;
    releasing = true;
    router.post(`/consultation/applicants/${applicant.id}/release`, {
      onError: (err) => {
        releasing = false;
        console.error('Release failed:', err);
      },
      onFinish: () => {
        releasing = false;
        dialogOpen = false;
      },
    });
  }

  function getBarClass(pct) {
    if (pct >= 85) return "bg-emerald-500";
    if (pct >= 70) return "bg-blue-500";
    if (pct >= 50) return "bg-amber-500";
    return "bg-rose-500";
  }
</script>

<svelte:head>
  <title>Release & Consultation - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
<div class="space-y-8 pb-8">
  <!-- Back link -->
  <Link href="/consultation" class="text-sm border border-transparent text-muted-foreground hover:text-foreground inline-flex items-center gap-1 transition-colors px-3 py-1.5 rounded-full hover:bg-muted/50">
    ← Back to Dashboard
  </Link>

  <!-- Header Card (Frosted Profile) -->
  <div class="relative overflow-hidden rounded-2xl glass-panel p-6 border bg-card/50 shadow-sm flex items-start justify-between">
    <div class="absolute inset-0 bg-gradient-to-r from-primary/5 to-transparent pointer-events-none"></div>
    <div class="relative z-10">
      <h2 class="text-2xl font-bold tracking-tight text-foreground">{applicant.name}</h2>
      <div class="flex items-center gap-3 mt-2">
        <span class="text-muted-foreground font-mono text-sm">{applicant.reference}</span>
        <Badge variant={isReleased ? 'success' : 'warning'} class="shadow-sm">
          {isReleased ? 'Released' : 'Pending Review'}
        </Badge>
      </div>
    </div>
  </div>

  <!-- Score breakdown -->
  <Card class="overflow-hidden border-border/50 shadow-sm">
    <CardHeader class="bg-muted/30 pb-4 border-b">
      <CardTitle class="text-lg">Score Breakdown</CardTitle>
    </CardHeader>
    <CardContent class="p-0">
      <div class="divide-y divide-border/50">
        {#each scores as score}
          <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 sm:px-6 hover:bg-muted/5 transition-colors">
            <span class="font-medium text-foreground min-w-40 mb-2 sm:mb-0">{score.domain}</span>
            <div class="flex items-center gap-4 w-full sm:w-auto">
              <span class="text-sm text-muted-foreground font-mono w-12 text-right">{score.raw}/{score.max}</span>
              <div class="flex-1 sm:w-48 h-2.5 bg-muted rounded-full overflow-hidden shadow-inner">
                <div class={`h-full transition-all duration-500 ease-out ${getBarClass(score.pct)}`} style="width: {score.pct}%"></div>
              </div>
              <span class="text-sm font-bold w-12 text-right tracking-tight">{score.pct}%</span>
            </div>
          </div>
        {/each}
      </div>
    </CardContent>
  </Card>

  <!-- Release action -->
  <div class="flex justify-end pt-4">
    {#if !isReleased}
      <Dialog bind:open={dialogOpen}>
        <DialogTrigger asChild>
          <Button size="lg" disabled={releasing} class="shadow-md hover:shadow-lg transition-shadow">
            {releasing ? 'Releasing…' : 'Release Results'}
          </Button>
        </DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Confirm Release</DialogTitle>
            <DialogDescription>
              Are you sure you want to release the consultation results for <strong>{applicant.name}</strong>? This action will make the scores visible to the applicant.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter class="mt-6">
            <Button variant="outline" onclick={() => dialogOpen = false}>Cancel</Button>
            <Button onclick={releaseConsultation} disabled={releasing}>
              {releasing ? 'Releasing…' : 'Yes, Release'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    {:else}
      <div class="glass-panel px-6 py-3 rounded-xl border border-success/20 bg-success/5 text-success font-medium shadow-sm">
        Results have been publicly released.
      </div>
    {/if}
  </div>
</div>
</AuthenticatedLayout>
