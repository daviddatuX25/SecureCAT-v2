<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Input } from '@/Components/ui/input';
  import { Badge } from '@/Components/ui/badge';
  import { Button } from '@/Components/ui/button';

  let { applicants = [], scheduledApplicantIds = [] } = $props();

  let search = $state('');
  let debouncedSearch = $state('');

  $effect(() => {
    const timer = setTimeout(() => {
      debouncedSearch = search;
      if (search.length >= 2) {
        router.get('/consultation/day', { search }, { preserveState: true });
      } else if (!search) {
        router.get('/consultation/day', {}, { preserveState: true });
      }
    }, 300);
    return () => clearTimeout(timer);
  });

  const filteredApplicants = $derived(
    debouncedSearch.length >= 2
      ? applicants
      : applicants
  );

  const breadcrumbs = $derived([
  { label: 'Release & Consultation', href: '/consultation' },
  { label: "Today's Consultations" }
]);

  function getScoreColor(pct) {
    if (pct >= 85) return "text-emerald-600 bg-emerald-500/10 border-emerald-500/20";
    if (pct >= 70) return "text-blue-600 bg-blue-500/10 border-blue-500/20";
    if (pct >= 50) return "text-amber-600 bg-amber-500/10 border-amber-500/20";
    return "text-rose-600 bg-rose-500/10 border-rose-500/20";
  }
</script>

<svelte:head>
  <title>Release & Consultation - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
<div class="space-y-8 pb-8">
  <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-4">
    <Input
      type="search"
      placeholder="Search name or ref (min 2 chars)..."
      bind:value={search}
      class="max-w-xs shadow-sm bg-background border-border/50"
    />
  </div>

  <Card class="overflow-hidden border-border/50 shadow-sm glass-panel bg-card/60">
    <div class="p-0">
      {#if filteredApplicants.length === 0}
        <div class="flex flex-col items-center justify-center p-12 text-center text-muted-foreground">
          <svg class="h-12 w-12 text-muted-foreground/50 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="text-lg font-medium">No Consultations Scheduled</p>
          <p class="text-sm max-w-sm mt-1">There are no applicants scheduled for consultation today matching your criteria.</p>
        </div>
      {:else}
        <div class="divide-y divide-border/50">
          {#each filteredApplicants as applicant}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 sm:px-6 transition-colors hover:bg-muted/10 group">
              <div class="min-w-0 flex-1 mb-3 sm:mb-0">
                <p class="font-medium text-foreground tracking-tight">{applicant.name}</p>
                <div class="flex items-center gap-2 mt-1">
                  <Badge variant="outline" class="text-xs text-muted-foreground font-normal">
                    {applicant.reference}
                  </Badge>
                </div>
              </div>
              <div class="flex items-center gap-4 shrink-0">
                {#if applicant.score_pct !== undefined}
                  <div class={`px-2.5 py-1 rounded-md border text-xs font-semibold ${getScoreColor(applicant.score_pct)}`}>
                    Score: {applicant.score_pct}%
                  </div>
                {/if}
                <Button size="sm" variant="secondary" as="a" href={`/consultation/applicants/${applicant.id}`} class="shadow-sm group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                  View Details
                </Button>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </div>
  </Card>
</div>
</AuthenticatedLayout>
