<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  const breadcrumbs = [
    { label: 'Result Sheet Templates', href: '/admin/result-sheet-templates' },
    { label: 'Edit' },
  ];

  let {
    template,
    placeholders = [],
    domainPlaceholders = [],
    htmlScoresNote = '',
    htmlTemplateRules = '',
    docxPlaceholderNote = '',
    layoutOptions = { full: 'Full page', half_a4: 'Half-crosswise' },
  } = $props();
  const page = usePage();
  const csrfToken = $derived($page.props.csrf_token ?? '');

  const form = useForm({
    name: template?.name ?? '',
    mode: template?.mode ?? 'html',
    content: template?.content ?? '',
    docx: null,
    paper_size: template?.paper_size ?? 'a4',
    orientation: template?.orientation ?? 'portrait',
    logical_unit: template?.logical_unit ?? 'full',
    is_active: template?.is_active ?? true,
  });

  let previewHtml = $state('');
  let previewLoading = $state(false);
  let previewError = $state(null);
  let previewDebounce = null; // plain var: $state would make $effect depend on it → infinite loop on write

  function submitForm(e) {
    e.preventDefault();
    $form.put(`/admin/result-sheet-templates/${template.id}`);
  }

  function fetchPreview() {
    clearTimeout(previewDebounce);
    previewDebounce = setTimeout(() => {
      previewLoading = true;
      previewError = null;
      const fd = new FormData();
      fd.append('mode', $form.mode);
      fd.append('paper_size', $form.paper_size);
      fd.append('orientation', $form.orientation);
      fd.append('logical_unit', $form.logical_unit);

      if ($form.mode === 'html') {
        fd.append('content', $form.content || '');
      } else if ($form.docx) {
        fd.append('docx', $form.docx);
      } else if (template?.id && $form.mode === 'docx') {
        fd.append('template_id', template.id);
      } else {
        previewLoading = false;
        previewHtml = '<p class="text-muted-foreground p-4">Upload a DOCX file to preview.</p>';
        return;
      }

      fetch('/admin/result-sheet-templates/preview', {
        method: 'POST',
        body: fd,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
      })
        .then((r) => {
          if (!r.ok) {
            return r.text().then((text) => {
              let msg = 'Preview failed';
              try { msg = JSON.parse(text)?.error ?? msg; } catch {}
              throw new Error(msg + ` (${r.status})`);
            });
          }
          return r.json();
        })
        .then((data) => {
          previewHtml = data.html || '';
          previewError = null;
        })
        .catch((err) => {
          previewError = err.message || 'Preview failed';
          previewHtml = '';
        })
        .finally(() => (previewLoading = false));
    }, 400);
  }

  $effect(() => {
    if ($form.mode === 'html' && $form.content) fetchPreview();
    else if ($form.mode === 'docx' && $form.docx) fetchPreview();
    else if ($form.mode === 'docx' && template?.docx_path) fetchPreview();
    else if ($form.mode === 'docx') previewHtml = '<p class="text-muted-foreground p-4">Upload a DOCX file to replace, or save to use current.</p>';
    else previewHtml = '';
  });

  $effect(() => {
    $form.logical_unit;
    if ($form.mode === 'html' && $form.content) fetchPreview();
    else if ($form.mode === 'docx' && ($form.docx || template?.docx_path)) fetchPreview();
  });
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-6xl space-y-6">
    <p class="text-sm text-muted-foreground">Common placeholders: {placeholders.slice(0, 6).join(', ')}…</p>

    <div class="grid gap-6 lg:grid-cols-2">
      <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
        <div class="space-y-2">
          <label for="name" class="text-sm font-medium">Name</label>
          <Input id="name" bind:value={$form.name} placeholder="e.g., Default" required maxlength="100" />
          {#if $form.errors?.name}<p class="text-sm text-destructive">{$form.errors.name}</p>{/if}
        </div>

        <div>
          <label class="text-sm font-medium">Mode</label>
          <div class="mt-1 flex gap-4">
            <label class="flex items-center gap-2">
              <input type="radio" name="mode" value="html" bind:group={$form.mode} class="rounded" />
              <span class="text-sm">HTML</span>
            </label>
            <label class="flex items-center gap-2">
              <input type="radio" name="mode" value="docx" bind:group={$form.mode} class="rounded" />
              <span class="text-sm">DOCX upload</span>
            </label>
          </div>
        </div>

        {#if $form.mode === 'html'}
          <div>
            <label for="logical_unit" class="text-sm font-medium">Layout</label>
            <select id="logical_unit" bind:value={$form.logical_unit} class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm max-w-xs">
              {#each Object.entries(layoutOptions) as [k, v]}
                <option value={k}>{v}</option>
              {/each}
            </select>
          </div>
        {/if}

        {#if $form.mode === 'html'}
          <div class="space-y-2">
            <label for="content" class="text-sm font-medium">HTML + CSS (JavaScript not allowed)</label>
            <p class="text-xs text-muted-foreground">Enter custom HTML and CSS. Use placeholders like &#123;&#123;applicant_name&#125;&#125;. Scripts and event handlers are stripped for security.</p>
            {#if htmlTemplateRules}
              <p class="text-xs text-muted-foreground mt-1 rounded bg-muted/50 p-2 font-medium">Template rules: {htmlTemplateRules}</p>
            {/if}
            {#if htmlScoresNote}
              <p class="text-xs text-muted-foreground mt-1 rounded bg-muted/50 p-2">{@html htmlScoresNote}</p>
            {/if}
            <textarea
              id="content"
              bind:value={$form.content}
              required={$form.mode === 'html'}
              rows="12"
              class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm font-mono"
            ></textarea>
            {#if $form.errors?.content}<p class="text-sm text-destructive">{$form.errors.content}</p>{/if}
          </div>
        {:else}
          <div class="space-y-2">
            <label for="docx" class="text-sm font-medium">DOCX file</label>
            {#if docxPlaceholderNote}
              <p class="text-xs text-muted-foreground mt-0.5 mb-2">{docxPlaceholderNote}</p>
            {/if}
            {#if domainPlaceholders?.length > 0}
              <div class="mb-3 rounded border border-border/50 bg-muted/30 p-3 text-xs">
                <p class="font-medium mb-2">Per-domain placeholders (percentage and raw):</p>
                <ul class="space-y-1 font-mono">
                  {#each domainPlaceholders as dp}
                    <li><code class="rounded bg-muted px-1">{dp.example}</code> (%), <code class="rounded bg-muted px-1">{dp.exampleRaw}</code> (raw/max)</li>
                  {/each}
                </ul>
              </div>
            {/if}
            {#if template?.docx_path}
              <p class="text-xs text-muted-foreground">Current file exists. Upload a new file to replace.</p>
            {/if}
            <input
              id="docx"
              type="file"
              accept=".docx"
              class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
              onchange={(e) => form.set('docx', e.target.files?.[0] ?? null)}
            />
            {#if $form.errors?.docx}<p class="text-sm text-destructive">{$form.errors.docx}</p>{/if}
          </div>
        {/if}

        <div class="flex items-center gap-2">
          <input type="checkbox" id="is_active" bind:checked={$form.is_active} class="rounded" />
          <label for="is_active" class="text-sm">Active (use for printing)</label>
        </div>

        <div class="flex gap-2 pt-4">
          <Button type="submit" disabled={$form.processing}>{$form.processing ? 'Saving...' : 'Save'}</Button>
          <Link href="/admin/result-sheet-templates"><Button type="button" variant="outline">Cancel</Button></Link>
        </div>
      </form>

      <div class="rounded-lg border bg-card p-4">
        <h3 class="text-sm font-medium mb-2">Live preview</h3>
        {#if previewLoading}
          <p class="text-sm text-muted-foreground p-4">Loading…</p>
        {:else if previewError}
          <p class="text-sm text-destructive p-4">{previewError}</p>
        {:else if previewHtml}
          <div class="overflow-auto rounded border bg-white p-4 text-sm" style="max-height: 600px;">
            {@html previewHtml}
          </div>
        {:else}
          <p class="text-sm text-muted-foreground p-4">Edit content or upload DOCX to see preview.</p>
        {/if}
      </div>
    </div>
  </div>
</AuthenticatedLayout>
