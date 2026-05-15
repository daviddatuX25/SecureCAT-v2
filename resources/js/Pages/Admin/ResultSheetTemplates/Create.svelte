<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import { FileUpload } from '@/Components/ui/file-upload';
  import Switch from '@/Components/ui/switch/switch.svelte';
  import { Textarea } from '@/Components/ui/textarea';
  import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
  import { success } from '@/lib/toast';
  import { FileCode, FileText, ChevronDown, ChevronUp, HelpCircle, Copy } from 'lucide-svelte';

  const breadcrumbs = [
    { label: 'Release Management', href: '/admin/release' },
    { label: 'Result Sheet Templates', href: '/admin/release/result-templates' },
    { label: 'Create' },
  ];

  let {
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
    name: '',
    mode: 'html',
    content: '',
    docx: null,
    paper_size: 'a4',
    orientation: 'portrait',
    logical_unit: 'full',
    is_active: true,
  });

  // $state for reactivity tracking only — Svelte 5 wraps objects in Proxy,
  // which breaks FormData.append (internal slots require a real File).
  let docxFile = $state(null);
  let rawDocxFile = null; // plain var holds the actual File object
  let helpOpen = $state(false);

  $form.onFinish = () => {
    if (!$form.errors || Object.keys($form.errors).length === 0) {
      success('Result sheet template created');
    }
  };

  function handleDocxFile(e) {
    const file = e?.files?.[0];
    rawDocxFile = file;  // raw File for FormData / Inertia
    docxFile = file;     // triggers $effect reactivity
  }

  let previewHtml = $state('');
  let previewLoading = $state(false);
  let previewError = $state(null);
  let previewDebounce = null; // plain var: $state would make $effect depend on it → infinite loop on write

  function submitForm(e) {
    e.preventDefault();
    $form.transform((data) => ({ ...data, docx: rawDocxFile }));
    $form.post('/admin/release/result-templates', { forceFormData: true });
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

      if ($form.mode === 'html' && $form.content?.trim()) {
        fd.append('content', $form.content);
      } else if (rawDocxFile) {
        fd.append('docx', rawDocxFile);
      } else {
        previewLoading = false;
        previewHtml = '<p class="text-muted-foreground p-4">Upload a DOCX file to preview.</p>';
        return;
      }

      fetch('/admin/release/result-templates/preview', {
        method: 'POST',
        body: fd,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
      })
        .then(async (r) => {
          if (!r.ok) {
            const text = await r.text();
            let msg = 'Preview failed';
            try { msg = JSON.parse(text)?.error ?? JSON.parse(text)?.message ?? msg; } catch {}
            throw new Error(msg + ` (${r.status})`);
          }
          const json = await r.json();
          return json;
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
    $form.mode;
    docxFile;
    $form.content;
    if ($form.mode === 'html' && $form.content) fetchPreview();
    else if ($form.mode === 'docx' && docxFile) fetchPreview();
    else if ($form.mode === 'docx') previewHtml = '<p class="text-muted-foreground p-4">Upload a DOCX file to preview.</p>';
    else previewHtml = '';
  });

  $effect(() => {
    $form.logical_unit;
    $form.paper_size;
    $form.orientation;
    if ($form.mode === 'html' && $form.content) fetchPreview();
    else if ($form.mode === 'docx' && docxFile) fetchPreview();
  });
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-6xl space-y-6">
    <!-- Consolidated Placeholder Help Section -->
    <div class="rounded-lg border bg-card">
      <button
        type="button"
        onclick={() => helpOpen = !helpOpen}
        class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-muted/50"
      >
        <div class="flex items-center gap-2">
          <HelpCircle class="size-4 text-muted-foreground" />
          <span class="text-sm font-medium">Placeholder Reference & Templates Guide</span>
        </div>
        {#if helpOpen}
          <ChevronUp class="size-4 text-muted-foreground" />
        {:else}
          <ChevronDown class="size-4 text-muted-foreground" />
        {/if}
      </button>

      {#if helpOpen}
        <div class="border-t px-4 py-4 space-y-4 text-sm">
          <!-- Common Placeholders -->
          <div>
            <h4 class="font-medium mb-2">Common Placeholders</h4>
            <p class="text-xs text-muted-foreground mb-2">Click to copy</p>
            <div class="flex flex-wrap gap-1.5">
              {#each placeholders as ph}
                <button
                  type="button"
                  title="Click to copy {ph}"
                  onclick={() => { navigator.clipboard.writeText(ph); }}
                  class="inline-flex items-center gap-1 rounded-full bg-secondary px-2.5 py-1 text-xs font-medium hover:bg-secondary/80 transition-colors"
                >
                  <span class="text-foreground">{ph.replace(/[{}]/g, '')}</span>
                  <Copy class="size-3 text-muted-foreground" />
                </button>
              {/each}
            </div>
          </div>

          <!-- Domain Placeholders (DOCX only) -->
          {#if domainPlaceholders.length > 0}
            <div>
              <h4 class="font-medium mb-2">Domain Tags</h4>
              <p class="text-xs text-muted-foreground mb-2">Click to copy. Add <code class="bg-muted px-1">_2</code> for applicant 2.</p>
              <div class="flex flex-wrap gap-1.5">
                {#each domainPlaceholders as dp}
                  <button
                    type="button"
                    title="Click to copy {dp.example}"
                    onclick={() => { navigator.clipboard.writeText(dp.example); }}
                    class="inline-flex items-center gap-1 rounded-full bg-secondary px-2.5 py-1 text-xs font-medium hover:bg-secondary/80 transition-colors"
                  >
                    <span class="text-foreground">{dp.slug}</span>
                    <Copy class="size-3 text-muted-foreground" />
                  </button>
                {/each}
              </div>
            </div>
          {/if}

          <!-- Mode-specific rules -->
          {#if $form.mode === 'html'}
            {#if htmlTemplateRules}
              <div>
                <h4 class="font-medium mb-2">HTML Rules</h4>
                <p class="text-xs text-muted-foreground">{htmlTemplateRules}</p>
              </div>
            {/if}
            {#if htmlScoresNote}
              <div>
                <h4 class="font-medium mb-2">Scores Table</h4>
                <p class="text-xs text-muted-foreground">{@html htmlScoresNote}</p>
              </div>
            {/if}
          {:else}
            {#if docxPlaceholderNote}
              <div>
                <h4 class="font-medium mb-2">DOCX Notes</h4>
                <p class="text-xs text-muted-foreground">{docxPlaceholderNote}</p>
              </div>
            {/if}
          {/if}
        </div>
      {/if}
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <form onsubmit={submitForm} class="space-y-4 rounded-lg border bg-card p-6">
        <div>
          <label for="name" class="text-sm font-medium">Name</label>
          <Input id="name" bind:value={$form.name} placeholder="Default" required class="mt-1" />
          {#if $form.errors?.name}<p class="text-sm text-destructive mt-1">{$form.errors.name}</p>{/if}
        </div>

        <!-- Mode Switch -->
        <div class="flex items-center gap-3">
          <label class="text-sm font-medium">Mode</label>
          <ToggleGroup bind:value={$form.mode} type="single" variant="default" class="bg-muted p-1 rounded-md">
            <ToggleGroupItem value="html" class="gap-1.5 px-3 py-1.5">
              <FileCode class="size-4" />
              <span class="text-sm">HTML</span>
            </ToggleGroupItem>
            <ToggleGroupItem value="docx" class="gap-1.5 px-3 py-1.5">
              <FileText class="size-4" />
              <span class="text-sm">DOCX</span>
            </ToggleGroupItem>
          </ToggleGroup>
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
          <div>
            <label for="content" class="text-sm font-medium">HTML + CSS (JavaScript not allowed)</label>
            <p class="text-xs text-muted-foreground mt-0.5">Enter custom HTML and CSS. Use placeholders like &#123;&#123;applicant_name&#125;&#125;. Scripts and event handlers are stripped for security.</p>
            <Textarea
              id="content"
              bind:value={$form.content}
              required={$form.mode === 'html'}
              rows="16"
              placeholder="<div style=&quot;padding: 1rem;&quot;><p>Hello <strong>&#123;&#123;applicant_name&#125;&#125;</strong></p></div>"
              class="mt-2 flex w-full font-mono"
            />
            {#if $form.errors?.content}<p class="text-sm text-destructive mt-1">{$form.errors.content}</p>{/if}
          </div>
        {:else}
          <div>
            <label for="docx" class="text-sm font-medium">DOCX file</label>
            <FileUpload
              label="Upload DOCX template"
              accept=".docx"
              maxSize="5MB"
              onfiles={handleDocxFile}
            />
            {#if $form.errors?.docx}<p class="text-sm text-destructive mt-1">{$form.errors.docx}</p>{/if}
          </div>
        {/if}

        <div class="flex items-center gap-3">
          <Switch
            checked={$form.is_active}
            onCheckedChange={(checked) => $form.is_active = checked}
          />
          <label for="is_active" class="text-sm">Active</label>
        </div>

        <div class="flex gap-2 pt-4">
          <Button type="submit" disabled={$form.processing}>{$form.processing ? 'Creating...' : 'Create'}</Button>
          <Link href="/admin/release/result-templates"><Button type="button" variant="outline">Cancel</Button></Link>
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