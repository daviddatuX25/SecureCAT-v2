<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import { Input } from '@/Components/ui/input';
  import { ArrowLeft, Save, User, ChevronRight } from 'lucide-svelte';

  let { sessionId = '1', applicantId = '1001', workflowStatus = 'in_progress', applicant = {}, domains = [], existing_scores = [] } = $props();
  const sid = $derived(String(sessionId));
  const isReadOnly = $derived(workflowStatus === 'completed');

  const breadcrumbs = $derived([
    { label: 'Grading', href: '/grading' },
    { label: 'Session #' + sid, href: `/grading/sessions/${sid}` },
    { label: applicant?.name ?? 'Applicant' }
  ]);

  const initialScores = $derived.by(() => {
    const arr = [];
    for (const d of domains) {
      const existing = existing_scores.find((s) => s.domain_id === d.id);
      arr.push({
        domain_id: d.id,
        raw_score: existing?.raw_score ?? 0,
        max_score: existing?.max_score ?? d.max_items ?? 0,
      });
    }
    return arr;
  });

  let scores = $state([]);
  $effect(() => {
    scores = [...initialScores];
  });

  function getScore(domainId) {
    const s = scores.find((x) => x.domain_id === domainId);
    return s ? s.raw_score : 0;
  }

  function setScore(domainId, value) {
    const num = Math.max(0, parseInt(value, 10) || 0);
    scores = scores.map((s) =>
      s.domain_id === domainId ? { ...s, raw_score: Math.min(num, s.max_score) } : s
    );
  }

  function getMax(domainId) {
    return scores.find((x) => x.domain_id === domainId)?.max_score ?? 0;
  }

  function pct(domainId) {
    const raw = getScore(domainId);
    const max = getMax(domainId);
    if (max <= 0) return 0;
    return Math.round((raw / max) * 100);
  }

  let saving = $state(false);
  let saveError = $state('');
  function saveScores() {
    saving = true;
    saveError = '';
    const s = scores.map((sc) => ({
      domain_id: sc.domain_id,
      raw_score: sc.raw_score,
      max_score: sc.max_score,
    }));
    router.put(`/grading/sessions/${sid}/applicants/${applicantId}/scores`, { scores: s }, {
      onError: (err) => {
        saving = false;
        saveError = Object.values(err).flat().join(', ') || 'Failed to save scores.';
      },
      onFinish: () => (saving = false),
    });
  }
</script>

<svelte:head>
  <title>Score input - {applicant.name} - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="space-y-6 min-w-0 max-w-2xl">
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <User class="h-5 w-5" />
          {applicant.name}
        </CardTitle>
        <CardDescription>Reference: {applicant.reference} · Enter raw score per domain (items correct).</CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- Domain score inputs -->
        <div class="grid gap-4 sm:grid-cols-2">
          {#each domains as domain}
            {@const raw = getScore(domain.id)}
            {@const max = domain.max_items ?? domain.max_score ?? 0}
            {@const percent = max > 0 ? Math.round((raw / max) * 100) : 0}
            <div class="rounded-lg border border-border p-4 space-y-2">
              <label for="score-{domain.id}" class="text-sm font-medium leading-none">{domain.name} ({domain.code})</label>
              <div class="flex items-center gap-3">
                <Input
                  id="score-{domain.id}"
                  type="number"
                  min="0"
                  max={max}
                  value={raw}
                  oninput={(e) => !isReadOnly && setScore(domain.id, e.currentTarget.value)}
                  disabled={isReadOnly}
                  class="w-20"
                />
                <span class="text-muted-foreground">/ {max}</span>
                <span class="text-sm font-medium tabular-nums">{percent}%</span>
              </div>
            </div>
          {/each}
        </div>

        {#if saveError}
          <p class="text-sm text-destructive">{saveError}</p>
        {/if}

        <div class="flex flex-wrap gap-3 pt-2">
          {#if !isReadOnly}
          <Button onclick={saveScores} disabled={saving} class="min-h-[44px]">
            <Save class="h-4 w-4 mr-2" />
            {saving ? 'Saving…' : 'Save scores'}
          </Button>
          {/if}
          <Link href={`/grading/sessions/${sid}`}>
            <Button variant="outline" class="min-h-[44px]">
              <ChevronRight class="h-4 w-4 mr-2" />
              Back to applicant list
            </Button>
          </Link>
        </div>
      </CardContent>
    </Card>
  </div>
</AuthenticatedLayout>
