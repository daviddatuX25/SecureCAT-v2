<script>
  import { Link, router } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Input } from '@/Components/ui/input';

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
</script>

<div class="space-y-6">
  <Card>
    <CardHeader>
      <CardTitle>Today's Consultation</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <!-- Search -->
      <Input
        type="search"
        placeholder="Search by name or reference (min 2 chars)..."
        bind:value={search}
        class="max-w-sm"
      />

      <!-- Applicant list -->
      {#if filteredApplicants.length === 0}
        <div class="text-center py-8 text-muted-foreground text-sm">
          No scheduled applicants found.
        </div>
      {:else}
        <div class="divide-y">
          {#each filteredApplicants as applicant}
            <div class="flex items-center justify-between py-4">
              <div class="min-w-0">
                <p class="font-medium truncate">{applicant.name}</p>
                <p class="text-sm text-muted-foreground">{applicant.reference}</p>
              </div>
              <div class="flex items-center gap-3">
                {#if applicant.score_pct !== undefined}
                  <span class="text-sm font-medium">{applicant.score_pct}%</span>
                {/if}
                <Link
                  href={`/consultation/applicants/${applicant.id}`}
                  class="text-sm text-primary hover:underline"
                >
                  View →
                </Link>
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </CardContent>
  </Card>
</div>
