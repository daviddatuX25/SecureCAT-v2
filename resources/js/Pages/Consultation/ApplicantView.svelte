<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import { Input } from '@/Components/ui/input';
  import * as Table from '@/Components/ui/table';
  import { ArrowLeft, User, Send, ClipboardList, BookOpen, MessageSquare } from 'lucide-svelte';
  import { EXAM_PILLARS } from '@/lib/domains.js';

  let { applicantId = '1', applicant = {}, application = null, scores = [], matched_rules = [], consultation_summary = {}, courses = [] } = $props();
  const aid = $derived(Number(applicantId) || 1);
  const mockApplicant = $derived({
    id: aid,
    name: 'Maria Santos',
    email: 'maria.santos@example.com',
    reference: 'APP-2025-001',
  });

  const displayApplicant = $derived(applicant.id ? applicant : mockApplicant);
  const mockApplication = $state({
    id: 501,
    status: 'accepted',
    submitted_at: '2025-01-15',
    course_preferences: ['BSIT', 'BAP', 'BSED'],
  });

  const displayApplication = $derived(application ?? mockApplication);
  const mockScores = $state([
    { domain: 'Spatial Awareness', raw: 18, max: 25, pct: 72 },
    { domain: 'Numerical Ability', raw: 20, max: 25, pct: 80 },
    { domain: 'Verbal Reasoning', raw: 14, max: 25, pct: 56 },
    { domain: 'Abstract Reasoning', raw: 14, max: 20, pct: 70 },
    { domain: 'Logical Reasoning', raw: 19, max: 25, pct: 76 },
    { domain: 'Perceptual Speed & Accuracy', raw: 16, max: 20, pct: 80 },
  ]);

  const displayScores = $derived(scores.length > 0 ? scores : mockScores);
  const overallPct = $derived(displayScores.length ? Math.round(displayScores.reduce((a, s) => a + s.pct, 0) / displayScores.length) : 0);

  const mockMatchedRules = $state([
    { course_name: 'BSIT', domain_name: 'Logical Reasoning', range: '70 – 100', note: 'Strong logical reasoning.' },
    { course_name: 'BAP', domain_name: 'Numerical Ability', range: '75 – 100', note: 'Recommended for accountancy.' },
  ]);

  const displayMatchedRules = $derived(matched_rules?.length > 0 ? matched_rules : mockMatchedRules);
  const mockSummary = $state({
    status: 'pending',
    recommended_course_id: 1,
    recommended_course_name: 'BSIT',
    counselor_comments: 'Strong logical and numerical profile. Recommend BSIT.',
  });

  const displaySummary = $derived(consultation_summary?.status ? consultation_summary : mockSummary);
  const mockCourses = $state([
    { id: 1, name: 'BSIT' },
    { id: 2, name: 'BAP' },
    { id: 3, name: 'BSED' },
  ]);

  const displayCourses = $derived(courses?.length > 0 ? courses : mockCourses);
  let recommendedCourse = $state(displaySummary.recommended_course_id ?? 0);
  let comments = $state(displaySummary.counselor_comments ?? '');
  let releaseConfirmOpen = $state(false);
  let saving = $state(false);

  function saveSummary() {
    saving = true;
    router.put(`/consultation/applicants/${applicantId}/summary`, {
      recommended_course_id: recommendedCourse || null,
      counselor_comments: comments,
    }, { onFinish: () => (saving = false) });
  }

  function releaseConsultation() {
    router.post(`/consultation/applicants/${applicantId}/release`);
    releaseConfirmOpen = false;
  }
</script>

<svelte:head>
  <title>Consultation - {displayApplicant.name} - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <div class="flex items-center gap-4">
      <Link
        href="/consultation"
        class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1 min-h-[44px] items-center"
      >
        <ArrowLeft class="h-4 w-4" />
        Back to consultation
      </Link>
    </div>

    <!-- Applicant info -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <User class="h-5 w-5" />
          {displayApplicant.name}
        </CardTitle>
        <CardDescription>
          {displayApplicant.reference} · {displayApplicant.email}
        </CardDescription>
      </CardHeader>
      <CardContent class="flex flex-wrap gap-4">
        {#if displayApplication?.submitted_at}
          <div>
            <span class="text-xs text-muted-foreground">Application</span>
            <p class="font-medium">{displayApplication.status ?? '—'} · Submitted {displayApplication.submitted_at}</p>
          </div>
          {#if displayApplication.course_preferences?.length}
            <div>
              <span class="text-xs text-muted-foreground">Course preferences</span>
              <p class="font-medium">{displayApplication.course_preferences.join(', ')}</p>
            </div>
          {/if}
        {/if}
      </CardContent>
    </Card>

    <!-- Scores -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <ClipboardList class="h-5 w-5" />
          Exam scores
        </CardTitle>
        <CardDescription>Domain scores and overall.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="rounded-lg border border-border overflow-hidden min-w-0">
          <Table.Root class="w-full min-w-[400px]">
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="px-4 py-3">Domain</Table.Head>
                <Table.Head class="px-4 py-3 text-right">Score</Table.Head>
                <Table.Head class="px-4 py-3 text-right">%</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each displayScores as s}
                <Table.Row>
                  <Table.Cell class="px-4 py-3">{s.domain}</Table.Cell>
                  <Table.Cell class="px-4 py-3 text-right">{s.raw} / {s.max}</Table.Cell>
                  <Table.Cell class="px-4 py-3 text-right font-medium">{s.pct}%</Table.Cell>
                </Table.Row>
              {/each}
              <Table.Row class="bg-muted/30 font-medium">
                <Table.Cell class="px-4 py-3">Overall</Table.Cell>
                <Table.Cell class="px-4 py-3 text-right">—</Table.Cell>
                <Table.Cell class="px-4 py-3 text-right">{overallPct}%</Table.Cell>
              </Table.Row>
            </Table.Body>
          </Table.Root>
        </div>
      </CardContent>
    </Card>

    <!-- Matched rules -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <BookOpen class="h-5 w-5" />
          Matched decision rules
        </CardTitle>
        <CardDescription>Rules that match this applicant's scores.</CardDescription>
      </CardHeader>
      <CardContent>
        <ul class="space-y-3">
          {#each displayMatchedRules as rule}
            <li class="rounded-lg border border-border p-3">
              <div class="font-medium">{rule.course_name} ({rule.domain_name})</div>
              <div class="text-sm text-muted-foreground">Score range: {rule.range}</div>
              <p class="mt-1 text-sm">{rule.note}</p>
            </li>
          {/each}
        </ul>
      </CardContent>
    </Card>

    <!-- Consultation summary (editable) -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <MessageSquare class="h-5 w-5" />
          Consultation summary
        </CardTitle>
        <CardDescription>Recommendation and comments. Save before releasing.</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="space-y-2">
          <label for="recommended-course" class="text-sm font-medium block mb-1">Recommended course</label>
          <select
            id="recommended-course"
            bind:value={recommendedCourse}
            class="flex h-10 w-full max-w-xs rounded-md border border-input bg-background px-3 py-2 text-sm"
          >
            {#each displayCourses as c}
              <option value={c.id}>{c.name}</option>
            {/each}
          </select>
        </div>
        <div class="space-y-2">
          <label for="comments" class="text-sm font-medium block mb-1">Counselor comments</label>
          <textarea
            id="comments"
            bind:value={comments}
            rows="4"
            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            placeholder="Notes for the applicant..."
          ></textarea>
        </div>
        <div class="flex flex-wrap gap-3">
          <Button onclick={saveSummary} disabled={saving} class="min-h-[44px]">
            {saving ? 'Saving…' : 'Save summary'}
          </Button>
          <Button variant="destructive" class="min-h-[44px]" onclick={() => (releaseConfirmOpen = true)}>
            <Send class="h-4 w-4 mr-2" />
            Release to applicant
          </Button>
        </div>
      </CardContent>
    </Card>
  </div>

  <!-- Release confirm -->
  {#if releaseConfirmOpen}
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="release-title"
    >
      <div class="rounded-lg bg-card p-6 shadow-lg max-w-sm w-full">
        <h2 id="release-title" class="text-lg font-semibold">Release consultation?</h2>
        <p class="mt-2 text-sm text-muted-foreground">
          The applicant will be able to view their scores and this consultation. This action cannot be undone.
        </p>
        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" onclick={() => (releaseConfirmOpen = false)}>Cancel</Button>
          <Button variant="destructive" onclick={releaseConsultation}>Release</Button>
        </div>
      </div>
    </div>
  {/if}
</AuthenticatedLayout>
