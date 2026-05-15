<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import { Input } from '@/Components/ui/input';
  import { ArrowLeft, Save, User, ChevronRight, Calculator, PenLine } from 'lucide-svelte';

  let { sessionId = '1', applicantId = '1001', workflowStatus = 'in_progress', enableNormalizedScores = false, applicant = {}, domains = [], existing_scores = [] } = $props();
  const sid = $derived(String(sessionId));
  const isReadOnly = $derived(workflowStatus === 'completed');
  const autoCompute = $derived(enableNormalizedScores === true);

  const breadcrumbs = $derived([
    { label: 'Grading', href: '/admin/grading' },
    { label: 'Session #' + sid, href: `/admin/grading/sessions/${sid}` },
    { label: applicant?.name ?? 'Applicant' }
  ]);

  const initialScores = $derived.by(() => {
    const arr = [];
    for (const d of domains) {
      const existing = existing_scores.find((s) => s.aptitude_area_id === d.id);
      arr.push({
        aptitude_area_id: d.id,
        raw_score: existing?.raw_score ?? 0,
        max_score: existing?.max_score ?? d.max_items ?? 0,
        normalized_score: existing?.normalized_score ?? null,
      });
    }
    return arr;
  });

  let scores = $state([]);
  $effect(() => {
    scores = [...initialScores];
  });

  function getScore(domainId) {
    const s = scores.find((x) => x.aptitude_area_id === domainId);
    return s ? s.raw_score : 0;
  }

  function setScore(domainId, value) {
    const num = Math.max(0, parseInt(value, 10) || 0);
    scores = scores.map((s) =>
      s.aptitude_area_id === domainId ? { ...s, raw_score: Math.min(num, s.max_score) } : s
    );
  }

  function getNormalizedScore(domainId) {
    const s = scores.find((x) => x.aptitude_area_id === domainId);
    return s?.normalized_score ?? null;
  }

  function setNormalizedScore(domainId, value) {
    const num = value === '' ? null : Math.max(0, parseFloat(value) || 0);
    scores = scores.map((s) =>
      s.aptitude_area_id === domainId ? { ...s, normalized_score: num } : s
    );
  }

  function getMax(domainId) {
    return scores.find((x) => x.aptitude_area_id === domainId)?.max_score ?? 0;
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
    const s = scores.map((sc) => {
      if (autoCompute) {
        return {
          aptitude_area_id: sc.aptitude_area_id,
          raw_score: sc.raw_score,
          max_score: sc.max_score,
        };
      }
      return {
        aptitude_area_id: sc.aptitude_area_id,
        normalized_score: sc.normalized_score,
      };
    });
    router.put(`/admin/grading/sessions/${sid}/applicants/${applicantId}/scores`, { scores: s }, {
      onError: (err) => {
        saving = false;
        saveError = Object.values(err).flat().join(', ') || 'Failed to save scores.';
      },
      onFinish: () => (saving = false),
    });
  }
</script>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="space-y-6 min-w-0 max-w-2xl">
    <!-- Mode indicator -->
    {#if autoCompute}
      <div class="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
        <Calculator class="h-4 w-4 shrink-0" />
        <span><strong>Auto-compute mode</strong> — Enter raw scores (items correct). Normalized scores are calculated automatically using aptitude area formulas.</span>
      </div>
    {:else}
      <div class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
        <PenLine class="h-4 w-4 shrink-0" />
        <span><strong>Manual mode</strong> — Enter normalized scores directly for each aptitude area.</span>
      </div>
    {/if}

    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <User class="h-5 w-5" />
          {applicant.name}
        </CardTitle>
        <CardDescription>
          Reference: {applicant.reference}
          {#if autoCompute}
            · Enter raw score per aptitude area (items correct)
          {:else}
            · Enter normalized score per aptitude area
          {/if}
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- Domain score inputs -->
        <div class="grid gap-4 sm:grid-cols-2">
          {#each domains as domain}
            {@const max = domain.max_items ?? domain.max_score ?? 0}
            <div class="rounded-lg border border-border p-4 space-y-2">
              <label for="score-{domain.id}" class="text-sm font-medium leading-none">
                {domain.name} ({domain.code})
              </label>
              {#if autoCompute}
                {@const raw = getScore(domain.id)}
                {@const percent = max > 0 ? Math.round((raw / max) * 100) : 0}
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
                {#if !domain.has_formula}
                  <p class="text-xs text-muted-foreground">No formula configured — normalized score will not be computed.</p>
                {/if}
              {:else}
                {@const normScore = getNormalizedScore(domain.id)}
                <div class="flex items-center gap-3">
                  <Input
                    id="score-{domain.id}"
                    type="number"
                    min="0"
                    step="0.01"
                    value={normScore ?? ''}
                    oninput={(e) => !isReadOnly && setNormalizedScore(domain.id, e.currentTarget.value)}
                    disabled={isReadOnly}
                    class="w-24"
                    placeholder="0.00"
                  />
                  <span class="text-sm text-muted-foreground">points</span>
                </div>
              {/if}
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
          <Link href={`/admin/grading/sessions/${sid}`}>
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