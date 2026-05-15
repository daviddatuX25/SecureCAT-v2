<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm, page } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { FileUpload } from '@/Components/ui/file-upload';
  import { Upload } from 'lucide-svelte';
  import { GuidePanel, GuideSection, CopyableGroup, GuideNote } from '@/Components/Guide';

  let {
    academicYears = [],
    courses = [],
    requiredColumns = ['first_name', 'last_name', 'email'],
    optionalColumns = [],
  } = $props();

  const breadcrumbs = [
    { label: 'Applications', href: '/admin/applications' },
    { label: 'Import' },
  ];

  const columnLabels = {
    middle_name: 'middle name',
    suffix: 'suffix',
    birthdate: 'birthdate',
    sex: 'sex',
    phone: 'phone',
    address_line: 'address line',
    city: 'city',
    province: 'province',
    zip_code: 'zip code',
    course_preference_1: 'course preference 1',
    course_preference_2: 'course preference 2',
    course_preference_3: 'course preference 3',
    gwa: 'gwa',
  };

  const requiredItems = $derived(requiredColumns.map((c) => ({ value: c, label: columnLabels[c] ?? c.replace(/_/g, ' ') })));
  const optionalItems = $derived(optionalColumns.map((c) => ({ value: c, label: columnLabels[c] ?? c.replace(/_/g, ' ') })));
  const courseItems = $derived(
    courses.map((c) => ({ value: c.code, label: `${c.code} — ${c.name}` })),
  );

  const form = useForm({
    file: null,
    academic_year_id: '',
  });

  let selectedFile = $state(null);

  function submitForm(e) {
    e.preventDefault();
    if (!selectedFile) return;
    $form.transform((data) => ({
      ...data,
      file: selectedFile,
    }));
    // Post to preview route for 2-step flow
    $form.post('/admin/applications/import/preview', { forceFormData: true });
  }

  let message = $state('');
  let error = $state('');

  // Check for flash messages
  $effect(() => {
    message = $page.props.flash?.message || '';
    error = $page.props.flash?.error || '';
  });
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-2xl space-y-6">

    <div>
      <h1 class="text-2xl font-semibold">Bulk Import Applicants</h1>
      <p class="text-sm text-muted-foreground mt-1">
        Import applicant records via CSV file upload. The first row must contain column headers.
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
      <GuideSection title="Required Columns">
        <CopyableGroup items={requiredItems} />
      </GuideSection>

      <GuideSection title="Optional Columns">
        <CopyableGroup items={optionalItems} />
      </GuideSection>

      {#if courseItems.length > 0}
        <GuideSection title="Course Codes">
          <CopyableGroup items={courseItems} subtitle="Use these codes in course_preference columns" />
        </GuideSection>
      {/if}

      <GuideNote variant="tip" title="Tips">
        <ul class="list-disc pl-4 space-y-1 text-xs text-muted-foreground">
          <li>First row must contain column headers matching the names above</li>
          <li>Email addresses must be unique across all applicants</li>
          <li>Course preferences use course codes, not names</li>
        </ul>
      </GuideNote>
    </GuidePanel>

    <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="academic_year" class="text-sm font-medium leading-none">Academic Year</label>
        <select
          id="academic_year"
          bind:value={$form.academic_year_id}
          class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          required
        >
          <option value="">Select academic year...</option>
          {#each academicYears as year}
            <option value={year.id}>{year.academic_year} — Semester {year.semester}</option>
          {/each}
        </select>
        {#if $form.errors?.academic_year_id}
          <p class="text-sm text-destructive">{$form.errors.academic_year_id}</p>
        {/if}
      </div>

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
          {$form.processing ? 'Importing...' : 'Import CSV'}
        </Button>
        <Link href="/admin/applications">
          <Button type="button" variant="outline" class="min-h-[44px]">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>