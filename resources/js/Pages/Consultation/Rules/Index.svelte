<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import { Input } from '@/Components/ui/input';
  import * as Table from '@/Components/ui/table';
  import { ArrowLeft, Plus, Pencil, Trash2, Settings2 } from 'lucide-svelte';
  import { EXAM_PILLARS } from '@/lib/domains.js';

  let { rules = [], courses = [], domains = [] } = $props();
  const mockCourses = $state([
    { id: 1, name: 'BSIT', code: 'BSIT' },
    { id: 2, name: 'BAP', code: 'BAP' },
    { id: 3, name: 'BSED', code: 'BSED' },
  ]);

  const displayCourses = $derived(courses.length > 0 ? courses : mockCourses);
  const displayDomains = $derived(domains.length > 0 ? domains : EXAM_PILLARS);

  const mockRules = $state([
    { id: 1, course_id: 1, course_name: 'BSIT', domain_id: 5, domain_name: 'Logical Reasoning', min_score: 70, max_score: 100, note: 'Strong logical reasoning.', is_active: true },
    { id: 2, course_id: 1, course_name: 'BSIT', domain_id: 2, domain_name: 'Numerical Ability', min_score: 60, max_score: 100, note: 'Good numerical aptitude.', is_active: true },
    { id: 3, course_id: 2, course_name: 'BAP', domain_id: 2, domain_name: 'Numerical Ability', min_score: 75, max_score: 100, note: 'Recommended for accountancy.', is_active: true },
    { id: 4, course_id: 3, course_name: 'BSED', domain_id: 3, domain_name: 'Verbal Reasoning', min_score: 65, max_score: 100, note: 'Suitable for education.', is_active: true },
  ]);

  let filterCourse = $state('');
  let filterDomain = $state('');
  let modalOpen = $state(false);
  let editingRule = $state(null);
  let formCourse = $state('');
  let formDomain = $state('');
  let formMin = $state('');
  let formMax = $state('');
  let formNote = $state('');

  const displayRules = $derived(rules.length > 0 ? rules : mockRules);
  const filteredRules = $derived(
    displayRules.filter((r) => {
      if (filterCourse && r.course_id !== Number(filterCourse)) return false;
      if (filterDomain && r.domain_id !== Number(filterDomain)) return false;
      return true;
    })
  );

  function openCreate() {
    editingRule = null;
    formCourse = String(displayCourses[0]?.id ?? '');
    formDomain = String(displayDomains[0]?.id ?? '');
    formMin = '';
    formMax = '';
    formNote = '';
    modalOpen = true;
  }

  function openEdit(rule) {
    editingRule = rule;
    formCourse = String(rule.course_id);
    formDomain = rule.domain_id ? String(rule.domain_id) : '';
    formMin = String(rule.min_score);
    formMax = String(rule.max_score);
    formNote = rule.note ?? '';
    modalOpen = true;
  }

  function closeModal() {
    modalOpen = false;
    editingRule = null;
  }

  function saveRule() {
    if (editingRule) {
      router.put(`/consultation/rules/${editingRule.id}`, { course_id: formCourse, domain_id: formDomain || null, min_score: formMin, max_score: formMax, note: formNote });
    } else {
      router.post('/consultation/rules', { course_id: formCourse, domain_id: formDomain || null, min_score: formMin, max_score: formMax, note: formNote });
    }
    closeModal();
  }

  function deleteRule(id) {
    if (confirm('Delete this decision rule?')) router.delete(`/consultation/rules/${id}`);
  }
</script>

<svelte:head>
  <title>Decision rules - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-4">
        <Link
          href="/consultation"
          class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1 min-h-[44px] items-center"
        >
          <ArrowLeft class="h-4 w-4" />
          Back to consultation
        </Link>
      </div>
      <Button onclick={openCreate} class="min-h-[44px]">
        <Plus class="h-4 w-4 mr-2" />
        Create rule
      </Button>
    </div>

    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Settings2 class="h-5 w-5" />
          Decision rules
        </CardTitle>
        <CardDescription>Score-range rules per course (BSIT, BAP, BSED) and per exam pillar. Used to match applicants and suggest recommendations.</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <!-- Filters -->
        <div class="flex flex-wrap items-end gap-3">
          <div class="space-y-2">
            <label for="filter-course" class="text-xs font-medium block mb-1">Course</label>
            <select
              id="filter-course"
              bind:value={filterCourse}
              class="flex h-10 w-[180px] rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
              <option value="">All courses</option>
              {#each courses as c}
                <option value={c.id}>{c.name}</option>
              {/each}
            </select>
          </div>
          <div class="space-y-2">
            <label for="filter-domain" class="text-xs font-medium block mb-1">Exam pillar</label>
            <select
              id="filter-domain"
              bind:value={filterDomain}
              class="flex h-10 w-[180px] rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
              <option value="">All pillars</option>
              {#each domains as d}
                <option value={d.id}>{d.name}</option>
              {/each}
            </select>
          </div>
        </div>

        <div class="rounded-lg border border-border overflow-hidden min-w-0">
          <Table.Root class="w-full min-w-[640px]">
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="px-4 py-3">Course</Table.Head>
                <Table.Head class="px-4 py-3">Exam pillar</Table.Head>
                <Table.Head class="px-4 py-3">Score range</Table.Head>
                <Table.Head class="px-4 py-3">Note</Table.Head>
                <Table.Head class="px-4 py-3">Status</Table.Head>
                <Table.Head class="px-4 py-3 text-right">Actions</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each filteredRules as rule}
                <Table.Row>
                  <Table.Cell class="px-4 py-3">{rule.course_name}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{rule.domain_name}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{rule.min_score} – {rule.max_score}</Table.Cell>
                  <Table.Cell class="px-4 py-3 max-w-[200px] truncate text-muted-foreground">{rule.note}</Table.Cell>
                  <Table.Cell class="px-4 py-3">
                    <Badge variant={rule.is_active ? 'success' : 'muted'}>{rule.is_active ? 'Active' : 'Inactive'}</Badge>
                  </Table.Cell>
                  <Table.Cell class="px-4 py-3 text-right">
                    <div class="flex justify-end gap-2">
                      <Button variant="ghost" size="icon" aria-label="Edit" onclick={() => openEdit(rule)}>
                        <Pencil class="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="icon" aria-label="Delete" class="text-destructive hover:text-destructive" onclick={() => deleteRule(rule.id)}>
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </Table.Cell>
                </Table.Row>
              {:else}
                <Table.Row>
                  <Table.Cell colspan={6} class="px-4 py-12 text-center text-muted-foreground">
                    No rules match the filters. Create a rule to get started.
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>
      </CardContent>
    </Card>
  </div>

  <!-- Create/Edit modal -->
  {#if modalOpen}
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="rule-modal-title"
    >
      <div class="rounded-lg bg-card p-6 shadow-lg max-w-md w-full space-y-4">
        <h2 id="rule-modal-title" class="text-lg font-semibold">{editingRule ? 'Edit rule' : 'Create rule'}</h2>
        <div class="space-y-2">
          <label for="rule-course" class="text-sm font-medium block mb-1">Course</label>
          <select
            id="rule-course"
            bind:value={formCourse}
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          >
            {#each courses as c}
              <option value={c.id}>{c.name}</option>
            {/each}
          </select>
        </div>
        <div class="space-y-2">
          <label for="rule-domain" class="text-sm font-medium block mb-1">Exam pillar</label>
          <select
            id="rule-domain"
            bind:value={formDomain}
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          >
            {#each domains as d}
              <option value={d.id}>{d.name}</option>
            {/each}
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-2">
            <label for="rule-min" class="text-sm font-medium block mb-1">Min score</label>
            <Input id="rule-min" type="number" min="0" max="100" step="0.01" bind:value={formMin} />
          </div>
          <div class="space-y-2">
            <label for="rule-max" class="text-sm font-medium block mb-1">Max score</label>
            <Input id="rule-max" type="number" min="0" max="100" step="0.01" bind:value={formMax} />
          </div>
        </div>
        <div class="space-y-2">
          <label for="rule-note" class="text-sm font-medium block mb-1">Note</label>
          <textarea
            id="rule-note"
            bind:value={formNote}
            rows="3"
            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            placeholder="Counselor note for this range"
          ></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button variant="outline" onclick={closeModal}>Cancel</Button>
          <Button onclick={saveRule}>{editingRule ? 'Update' : 'Create'}</Button>
        </div>
      </div>
    </div>
  {/if}
</AuthenticatedLayout>
