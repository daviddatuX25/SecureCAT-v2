<script>
  import { Link } from '@inertiajs/svelte';
  import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
  import { Badge } from '@/Components/ui/badge';
  import { Button } from '@/Components/ui/button';

  let { applicants_pending = [], applicants_released = [], stats = {} } = $props();

  const tabs = ['pending', 'released'];
  let activeTab = $state('pending');

  const displayApplicants = $derived(activeTab === 'pending' ? applicants_pending : applicants_released);
</script>

<div class="space-y-6">
  <!-- Stats bar -->
  <div class="flex gap-4 text-sm text-muted-foreground">
    <span>{stats.pending ?? 0} pending</span>
    <span>·</span>
    <span>{stats.released ?? 0} released</span>
    <span>·</span>
    <span>{stats.total_with_scores ?? 0} total with scores</span>
  </div>

  <!-- Tabs -->
  <div class="flex gap-1 border-b">
    {#each tabs as tab}
      <button
        type="button"
        class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px {activeTab === tab
          ? 'border-primary text-primary'
          : 'border-transparent text-muted-foreground hover:text-foreground'}"
        onclick={() => (activeTab = tab)}
      >
        {tab === 'pending' ? 'Pending' : 'Released'}
      </button>
    {/each}
  </div>

  <!-- Applicant list -->
  <Card>
    <CardContent class="p-0">
      {#if displayApplicants.length === 0}
        <div class="p-8 text-center text-muted-foreground text-sm">
          No {activeTab} applicants.
        </div>
      {:else}
        <div class="divide-y">
          {#each displayApplicants as applicant}
            <div class="flex items-center justify-between px-6 py-4">
              <div class="min-w-0">
                <p class="font-medium truncate">{applicant.name}</p>
                <p class="text-sm text-muted-foreground">{applicant.reference}</p>
              </div>
              <div class="flex items-center gap-4">
                {#if activeTab === 'pending'}
                  <span class="text-sm text-muted-foreground">
                    Finalized {applicant.finalized_date ?? '—'}
                  </span>
                  <Button variant="outline" size="sm" as="a" href={`/consultation/applicants/${applicant.id}`}>
                    View
                  </Button>
                {:else}
                  <span class="text-sm text-muted-foreground">
                    Released {applicant.released_date ?? '—'}
                  </span>
                {/if}
              </div>
            </div>
          {/each}
        </div>
      {/if}
    </CardContent>
  </Card>
</div>