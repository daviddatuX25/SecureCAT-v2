<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import { Textarea } from '@/Components/ui/textarea';
  import { Switch } from '@/Components/ui/switch';
  import { success } from '@/lib/toast';
  import { Calculator, Table, Plus, Trash2, ClipboardPaste, Wand2, X } from 'lucide-svelte';

  const page = usePage();

  const form = useForm({
    name: '',
    code: '',
    description: '',
    max_items: 25,
    formula: '',
    scoring_method: 'formula',
    conversion_table: [],
    display_order: 0,
    is_active: true,
  });

  let showPasteOverlay = $state(false);
  let pasteText = $state('');
  let pasteError = $state('');

  let testScore = $state(10);
  let testResult = $state(null);
  let testError = $state('');

  $effect(() => {
    if ($form.scoring_method === 'formula') {
      $form.conversion_table = [];
    }
  });

  function addRow() {
    $form.conversion_table = [
      ...$form.conversion_table,
      { raw_score: '', percentile_output: '' },
    ];
  }

  function removeRow(index) {
    $form.conversion_table = $form.conversion_table.filter((_, i) => i !== index);
  }

  function updateRow(index, field, value) {
    const updated = [...$form.conversion_table];
    updated[index] = { ...updated[index], [field]: value };
    $form.conversion_table = updated;
  }

  function generateRows() {
    const max = parseInt($form.max_items, 10) || 0;
    if (max <= 0) return;
    const rows = [];
    for (let i = 0; i <= max; i++) {
      rows.push({ raw_score: i, percentile_output: '' });
    }
    $form.conversion_table = rows;
  }

  function openPaste() {
    showPasteOverlay = true;
    pasteText = '';
    pasteError = '';
  }

  function closePaste() {
    showPasteOverlay = false;
    pasteText = '';
    pasteError = '';
  }

  function applyPaste() {
    pasteError = '';
    if (!pasteText.trim()) {
      pasteError = 'Paste area is empty.';
      return;
    }
    const lines = pasteText.trim().split(/\r?\n/).filter(l => l.trim());
    const rows = [];
    for (const line of lines) {
      const parts = line.split(/\t/);
      if (parts.length >= 2) {
        const raw = parseInt(parts[0]?.trim(), 10);
        const out = parts[1]?.trim() ?? '';
        if (!isNaN(raw) && out) {
          rows.push({ raw_score: raw, percentile_output: out });
        }
      }
    }
    if (rows.length === 0) {
      pasteError = 'No valid rows found. Use tab-separated format: raw_score \t percentile_output';
      return;
    }
    // Merge with existing rows, overwrite duplicates
    const map = new Map();
    for (const r of $form.conversion_table) {
      map.set(r.raw_score, r);
    }
    for (const r of rows) {
      map.set(r.raw_score, r);
    }
    $form.conversion_table = Array.from(map.values()).sort((a, b) => a.raw_score - b.raw_score);
    closePaste();
  }

  async function testFormula() {
    testResult = null;
    testError = '';
    if (!$form.formula) {
      testError = 'Enter a formula first';
      return;
    }
    try {
      const res = await fetch('/admin/aptitude-areas/test-formula', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': $page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ formula: $form.formula, sample_raw_score: testScore, max_items: Number($form.max_items) }),
      });
      const data = await res.json();
      if (!res.ok || data.error) {
        testError = data.error || `Request failed (${res.status})`;
      } else {
        testResult = data.result;
      }
    } catch (e) {
      console.error('Test formula error:', e);
      testError = 'Request failed';
    }
  }

  $form.onFinish = () => {
    if (!$form.errors || Object.keys($form.errors).length === 0) {
      success('Aptitude area created');
    }
  };

  function submitForm(e) {
    e.preventDefault();
    $form.post('/admin/aptitude-areas');
  }

  const breadcrumbs = [
    { label: 'Setup', href: '/admin/setup' },
    { label: 'Aptitude Areas', href: '/admin/aptitude-areas' },
    { label: 'Create' },
  ];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-lg space-y-6">
    <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="name" class="text-sm font-medium">Name</label>
        <Input id="name" bind:value={$form.name} placeholder="e.g., Spatial Awareness" required maxlength="100" />
        {#if $form.errors?.name}
          <p class="text-sm text-destructive">{$form.errors.name}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="code" class="text-sm font-medium">Code</label>
        <Input id="code" bind:value={$form.code} placeholder="e.g., SA" required maxlength="20" />
        {#if $form.errors?.code}
          <p class="text-sm text-destructive">{$form.errors.code}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="description" class="text-sm font-medium">Description (optional)</label>
        <Textarea
          id="description"
          bind:value={$form.description}
          rows="2"
          class="flex min-h-[60px] w-full"
          placeholder="Brief description"
        />
        {#if $form.errors?.description}
          <p class="text-sm text-destructive">{$form.errors.description}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="max_items" class="text-sm font-medium">Max items (score ceiling)</label>
        <Input id="max_items" type="number" bind:value={$form.max_items} min="1" max="999" required />
        {#if $form.errors?.max_items}
          <p class="text-sm text-destructive">{$form.errors.max_items}</p>
        {/if}
      </div>

      <!-- Scoring Method Toggle -->
      <div class="space-y-2">
        <label class="text-sm font-medium">Scoring Method</label>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 rounded-md border border-border px-3 py-2 cursor-pointer hover:bg-muted/40">
            <input
              type="radio"
              name="scoring_method"
              value="formula"
              bind:group={$form.scoring_method}
              class="h-4 w-4"
            />
            <Calculator class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm">Formula</span>
          </label>
          <label class="flex items-center gap-2 rounded-md border border-border px-3 py-2 cursor-pointer hover:bg-muted/40">
            <input
              type="radio"
              name="scoring_method"
              value="conversion_table"
              bind:group={$form.scoring_method}
              class="h-4 w-4"
            />
            <Table class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm">Conversion Table</span>
          </label>
        </div>
        {#if $form.errors?.scoring_method}
          <p class="text-sm text-destructive">{$form.errors.scoring_method}</p>
        {/if}
      </div>

      {#if $form.scoring_method === 'formula'}
        <div class="space-y-2">
          <label for="formula" class="text-sm font-medium">Formula</label>
          <textarea
            id="formula"
            bind:value={$form.formula}
            rows="2"
            class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            placeholder="e.g., (x / max_items) * 100"
          ></textarea>
          <p class="text-xs text-muted-foreground">Variables: x (raw score), max_items, pi</p>
          {#if $form.errors?.formula}
            <p class="text-sm text-destructive">{$form.errors.formula}</p>
          {/if}
        </div>

        <div class="space-y-2 rounded-md border border-border bg-muted/30 p-4">
          <p class="text-sm font-medium">Test Formula</p>
          <div class="flex items-center gap-3">
            <div class="flex-1">
              <label class="text-xs text-muted-foreground">Sample raw score</label>
              <Input type="number" bind:value={testScore} min="0" />
            </div>
            <div class="flex-1">
              <label class="text-xs text-muted-foreground">Result</label>
              <div class="h-10 flex items-center text-sm">
                {#if testResult !== null}
                  <span class="font-medium text-green-700">{testResult}</span>
                {:else if testError}
                  <span class="text-red-600">{testError}</span>
                {:else}
                  <span class="text-muted-foreground">—</span>
                {/if}
              </div>
            </div>
          </div>
          <Button type="button" variant="outline" size="sm" onclick={testFormula}>Test</Button>
        </div>
      {:else}
        <!-- Conversion Table Grid -->
        <div class="space-y-3 rounded-md border border-border bg-muted/20 p-4">
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium">Conversion Table</p>
            <div class="flex gap-2">
              {#if $form.conversion_table.length === 0}
                <Button type="button" variant="outline" size="sm" onclick={generateRows}>
                  <Wand2 class="mr-1.5 h-3.5 w-3.5" />
                  Generate 0–{$form.max_items}
                </Button>
              {/if}
              <Button type="button" variant="outline" size="sm" onclick={openPaste}>
                <ClipboardPaste class="mr-1.5 h-3.5 w-3.5" />
                Paste
              </Button>
            </div>
          </div>

          {#if $form.conversion_table.length > 0}
            <div class="space-y-1">
              {#each $form.conversion_table as row, i}
                <div class="flex items-center gap-2">
                  <div class="flex-1">
                    <label class="text-xs text-muted-foreground">Raw Score</label>
                    <Input
                      type="number"
                      min="0"
                      value={row.raw_score}
                      oninput={(e) => updateRow(i, 'raw_score', parseInt(e.currentTarget.value, 10) || 0)}
                      class="w-24"
                    />
                  </div>
                  <div class="flex-[2]">
                    <label class="text-xs text-muted-foreground">Percentile Output</label>
                    <Input
                      type="text"
                      maxlength="20"
                      value={row.percentile_output}
                      oninput={(e) => updateRow(i, 'percentile_output', e.currentTarget.value)}
                      placeholder="e.g., 85th, 99+, N/A"
                      class="w-full"
                    />
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-8 w-8 p-0 text-destructive"
                    onclick={() => removeRow(i)}
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
              {/each}
            </div>
          {:else}
            <p class="text-sm text-muted-foreground">No rows yet. Use Generate or Paste to add rows.</p>
          {/if}

          <Button type="button" variant="outline" size="sm" onclick={addRow}>
            <Plus class="mr-1.5 h-3.5 w-3.5" />
            Add Row
          </Button>

          {#if $form.errors?.conversion_table}
            <p class="text-sm text-destructive">{$form.errors.conversion_table}</p>
          {/if}
        </div>
      {/if}

      <div class="space-y-2">
        <label for="display_order" class="text-sm font-medium">Display order</label>
        <Input id="display_order" type="number" bind:value={$form.display_order} min="0" />
        {#if $form.errors?.display_order}
          <p class="text-sm text-destructive">{$form.errors.display_order}</p>
        {/if}
      </div>

      <div class="flex items-center gap-3">
        <Switch
          id="is_active"
          checked={$form.is_active}
          onCheckedChange={(v) => ($form.is_active = v)}
        />
        <label for="is_active" class="text-sm font-medium">Active (included in grading and templates)</label>
      </div>
      {#if $form.errors?.is_active}
        <p class="text-sm text-destructive">{$form.errors.is_active}</p>
      {/if}

      <div class="flex gap-2 pt-4">
        <Button type="submit" disabled={$form.processing}>
          {$form.processing ? 'Creating...' : 'Create aptitude area'}
        </Button>
        <Link href="/admin/aptitude-areas">
          <Button type="button" variant="outline">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>

  {#if showPasteOverlay}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div class="w-full max-w-md space-y-4 rounded-lg border border-border bg-card p-6 shadow-lg">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium">Paste from Excel</h3>
          <Button variant="ghost" size="sm" class="h-8 w-8 p-0" onclick={closePaste}>
            <X class="h-4 w-4" />
          </Button>
        </div>
        <p class="text-sm text-muted-foreground">Paste tab-separated data. Each line: raw_score [tab] percentile_output</p>
        <Textarea
          bind:value={pasteText}
          rows="8"
          placeholder="0\t85th\n1\t85th\n2\t86th\n..."
          class="w-full"
        />
        {#if pasteError}
          <p class="text-sm text-destructive">{pasteError}</p>
        {/if}
        <div class="flex justify-end gap-2">
          <Button variant="outline" onclick={closePaste}>Cancel</Button>
          <Button onclick={applyPaste}>Apply</Button>
        </div>
      </div>
    </div>
  {/if}
</AuthenticatedLayout>
