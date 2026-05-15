<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm, page } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { FileUpload } from '@/Components/ui/file-upload';
  import { Upload, ArrowLeft } from 'lucide-svelte';
  import { GuidePanel, GuideSection, CopyableGroup, GuideNote } from '@/Components/Guide';

  let {
    enableNormalizedScores = false,
    aptitudeAreaCodes = [],
    previewUrl = '/admin/grading/import/preview',
  } = $props();

  const breadcrumbs = [
    { label: 'Grading', href: '/admin/grading' },
    { label: 'Import Scores' },
  ];

  const form = useForm({
    file: null,
  });

  let selectedFile = $state(null);

  function submitPreview(e) {
    e.preventDefault();
    if (!selectedFile) return;
    $form.transform((data) => ({
      ...data,
      file: selectedFile,
    }));
    $form.post(previewUrl, { forceFormData: true });
  }

  let message = $state('');
  let error = $state('');

  $effect(() => {
    message = $page.props.flash?.message || '';
    error = $page.props.flash?.error || '';
  });

  const scoreSuffix = enableNormalizedScores ? '(raw)' : '(normalized)';
  const requiredItems = [{ value: 'reference_number', label: 'reference_number' }];
  const aptitudeAreaItems = aptitudeAreaCodes.map((code) => ({ value: code }));
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-3xl space-y-6">
    <div>
      <Link href="/admin/grading" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
        <ArrowLeft class="size-4" />
        Back to Grading
      </Link>
    </div>

    <div>
      <h1 class="text-2xl font-semibold">Bulk Import Scores</h1>
      <p class="text-sm text-muted-foreground mt-1">
        Import applicant scores via spreadsheet upload. Columns use aptitude area codes.
      </p>
    </div>

    {#if message}
      <div class="rounded-md bg-green-50 border border-green-200 p-4 text-green-700">
        <pre class="whitespace-pre-wrap text-sm">{message}</pre>
      </div>
    {/if}

    {#if error}
      <div class="rounded-md bg-red-50 border border-red-200 p-4 text-red-700">
        <pre class="whitespace-pre-wrap text-sm">{error}</pre>
      </div>
    {/if}

    <GuidePanel title="Import Guide">
      <GuideSection title="Required Column">
        <CopyableGroup items={requiredItems} />
      </GuideSection>

      <GuideSection title="Score Columns" visible={aptitudeAreaCodes.length > 0}>
        <CopyableGroup
          items={aptitudeAreaItems}
          subtitle="Use these aptitude area codes as column headers. Values are {scoreSuffix}."
        />
      </GuideSection>

      <GuideNote variant={enableNormalizedScores ? 'warning' : 'info'} title={enableNormalizedScores ? 'Raw Score Mode' : 'Normalized Score Mode'}>
        {enableNormalizedScores
          ? 'Scores are stored as raw values. Enter the original scores from the exam.'
          : 'Scores are automatically normalized after import. Enter raw scores — the system will normalize them.'}
      </GuideNote>
    </GuidePanel>

    <form onsubmit={submitPreview} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="file" class="text-sm font-medium leading-none">Spreadsheet File</label>
        <FileUpload
          label="Upload file"
          accept=".csv,.xlsx,.xls,.txt"
          maxSize="10MB"
          bind:files={selectedFile}
        />
        <p class="text-xs text-muted-foreground">Supports CSV, XLSX, XLS. First row is used as headers.</p>
        {#if $form.errors?.file}
          <p class="text-sm text-destructive">{$form.errors.file}</p>
        {/if}
      </div>

      <div class="flex gap-3 pt-2">
        <Button type="submit" disabled={$form.processing} class="min-h-[44px]">
          <Upload class="mr-2 size-4" />
          {$form.processing ? 'Uploading...' : 'Preview Import'}
        </Button>
        <Link href="/admin/grading">
          <Button type="button" variant="outline" class="min-h-[44px]">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>