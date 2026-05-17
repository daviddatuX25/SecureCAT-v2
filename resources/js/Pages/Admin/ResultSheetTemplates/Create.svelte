<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import { FileUpload } from '@/Components/ui/file-upload';
  import Switch from '@/Components/ui/switch/switch.svelte';
  import { Textarea } from '@/Components/ui/textarea';
  import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
  import * as Select from '@/Components/ui/select';
  import { success } from '@/lib/toast';
  import { FileCode, FileText } from 'lucide-svelte';
  import { GuidePanel, GuideSection, CopyableGroup, GuideNote } from '@/Components/Guide';
  import { Badge } from '@/Components/ui/badge';

  const breadcrumbs = [
    { label: 'Release Management', href: '/admin/release' },
    { label: 'Result Sheet Templates', href: '/admin/release/result-templates' },
    { label: 'Create' },
  ];

  let {
    placeholdersApplicant1 = [],
    placeholdersApplicant2 = [],
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
    watermark_text: null,
  });

  let isCrosswise = $derived($form.logical_unit !== 'full');

  const applicant1Items = $derived(placeholdersApplicant1.map((ph) => ({ value: ph })));
  const applicant2Items = $derived(placeholdersApplicant2.map((ph) => ({ value: ph })));
  const domainItems = $derived(
    domainPlaceholders.flatMap((dp) => {
      const items = [{ value: dp.example, label: dp.slug }];
      if (isCrosswise) {
        items.push({ value: dp.example.replace('}}', '_2}}'), label: `${dp.slug}_2` });
      }
      return items;
    }),
  );

  // $state for reactivity tracking only — Svelte 5 wraps objects in Proxy,
  // which breaks FormData.append (internal slots require a real File).
  let docxFile = $state(null);
  let rawDocxFile = null; // plain var holds the actual File object

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

  let validationResult = $state(null); // { valid, found, missing, extra }
  let validationLoading = $state(false);

  function fetchValidation() {
    if ($form.mode !== 'docx') { validationResult = null; return; }
    if (!rawDocxFile) { validationResult = null; return; }
    validationLoading = true;
    const fd = new FormData();
    fd.append('logical_unit', $form.logical_unit);
    fd.append('docx', rawDocxFile);
    fetch('/admin/release/result-templates/validate-docx', {
      method: 'POST',
      body: fd,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      },
    })
      .then((r) => r.ok ? r.json() : null)
      .then((data) => { if (data) validationResult = data; })
      .catch(() => { validationResult = null; })
      .finally(() => (validationLoading = false));
  }

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

  $effect(() => {
    docxFile;
    $form.mode;
    $form.logical_unit;
    fetchValidation();
  });
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-6xl space-y-6">
    <GuidePanel title="Placeholder Reference & Templates Guide">
      <GuideSection title="Applicant 1 Placeholders">
        <CopyableGroup items={applicant1Items} />
      </GuideSection>

      <GuideSection title="Applicant 2 Placeholders" visible={isCrosswise}>
        <CopyableGroup items={applicant2Items} />
      </GuideSection>

      <GuideSection title="Domain Tags" visible={domainPlaceholders.length > 0}>
        <CopyableGroup
          items={domainItems}
          subtitle={isCrosswise ? 'Click to copy. Both applicant 1 and applicant 2 variants are shown.' : 'Click to copy'}
        />
      </GuideSection>

      {#if $form.mode === 'html'}
        <GuideSection title="HTML Rules" visible={!!htmlTemplateRules}>
          <p class="text-xs text-muted-foreground">{htmlTemplateRules}</p>
        </GuideSection>
        <GuideSection title="Scores Table" visible={!!htmlScoresNote}>
          <p class="text-xs text-muted-foreground">{@html htmlScoresNote}</p>
        </GuideSection>
      {:else}
        <GuideSection title="DOCX Notes" visible={!!docxPlaceholderNote}>
          <p class="text-xs text-muted-foreground">{docxPlaceholderNote}</p>
        </GuideSection>
      {/if}
    </GuidePanel>

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
            <label class="text-sm font-medium">Layout</label>
            <Select.Root type="single" bind:value={$form.logical_unit}>
              <Select.Trigger class="mt-1 w-full max-w-xs">
                {layoutOptions[$form.logical_unit] ?? 'Select layout'}
              </Select.Trigger>
              <Select.Content>
                {#each Object.entries(layoutOptions) as [k, v]}
                  <Select.Item value={k} label={v}>{v}</Select.Item>
                {/each}
              </Select.Content>
            </Select.Root>
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
            {#if validationLoading}
              <p class="text-xs text-muted-foreground mt-1">Checking placeholders…</p>
            {:else if validationResult}
              {#if validationResult.valid}
                <Badge variant="success" class="mt-1">All required placeholders found ({validationResult.found.length})</Badge>
              {:else}
                <Badge variant="warning" class="mt-1">{validationResult.missing.length} missing placeholder(s)</Badge>
                <div class="flex flex-wrap gap-1 mt-1">
                  {#each validationResult.missing as ph}
                    <Badge variant="outline" class="text-xs text-amber-600">{ph}</Badge>
                  {/each}
                </div>
              {/if}
              {#if validationResult.extra?.length}
                <details class="mt-1 text-xs text-muted-foreground">
                  <summary class="cursor-pointer">{validationResult.extra.length} unknown placeholder(s)</summary>
                  <div class="flex flex-wrap gap-1 mt-1">
                    {#each validationResult.extra as ph}
                      <Badge variant="muted" class="text-xs">{ph}</Badge>
                    {/each}
                  </div>
                </details>
              {/if}
            {/if}
          </div>
        {/if}

        <div class="flex items-center gap-3">
          <Switch
            checked={$form.is_active}
            onCheckedChange={(checked) => $form.is_active = checked}
          />
          <label for="is_active" class="text-sm">Active</label>
        </div>

        <div>
          <label for="watermark_text" class="text-sm font-medium">Watermark text (optional)</label>
          <p class="text-xs text-muted-foreground mt-0.5">Leave blank for no watermark. Shown diagonally on each PDF page (e.g. DRAFT, FINAL).</p>
          <Input
            id="watermark_text"
            bind:value={$form.watermark_text}
            placeholder="DRAFT"
            maxlength="50"
            class="mt-1 max-w-xs"
          />
          {#if $form.errors?.watermark_text}<p class="text-sm text-destructive mt-1">{$form.errors.watermark_text}</p>{/if}
        </div>

        <div class="flex gap-2 pt-4">
          <Button type="submit" disabled={$form.processing}>{$form.processing ? 'Creating...' : 'Create'}</Button>
          <Link href="/admin/release/result-templates"><Button type="button" variant="outline">Cancel</Button></Link>
        </div>
      </form>

      <div class="rounded-lg border bg-card p-4">
        <h3 class="text-sm font-medium mb-2">Live preview</h3>
        {#if $form.mode === 'docx'}
          <GuideNote variant="warning" title="Preview is approximate">
            The DOCX preview is converted to HTML, which may differ from the final PDF output.
            Always verify the printed result.
          </GuideNote>
        {/if}
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