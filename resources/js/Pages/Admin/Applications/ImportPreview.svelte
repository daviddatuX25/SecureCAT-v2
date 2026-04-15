<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm, page } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { FileSpreadsheet, Check, X, AlertTriangle, ArrowLeft, Save } from 'lucide-svelte';

  let {
    records = [],
    totalCount = 0,
    validCount = 0,
    academicYearId = 0,
    academicYears = [],
  } = $props();

  const breadcrumbs = [
    { label: 'Applications', href: '/admin/applications' },
    { label: 'Import', href: '/admin/applications/import' },
    { label: 'Preview' },
  ];

  const form = useForm({
    selected_ids: [],
    select_all: true,
  });

  let selectAll = $state(true);
  let selectedIds = $state(new Set());

  // Initialize with all valid records selected
  $effect(() => {
    if (selectAll) {
      selectedIds = new Set(records.filter(r => r.is_valid).map(r => r.id));
    }
  });

  function toggleRow(id) {
    const newSet = new Set(selectedIds);
    if (newSet.has(id)) {
      newSet.delete(id);
    } else {
      newSet.add(id);
    }
    selectedIds = newSet;
  }

  function toggleAll() {
    if (selectAll) {
      // Deselect all
      selectedIds = new Set();
      selectAll = false;
    } else {
      // Select all valid
      selectedIds = new Set(records.filter(r => r.is_valid).map(r => r.id));
      selectAll = true;
    }
  }

  function submitForm(e) {
    e.preventDefault();
    $form.transform((data) => ({
      ...data,
      selected_ids: Array.from(selectedIds),
    }));
    $form.post('/applications/import/confirm', { forceFormData: true });
  }

  let message = $state('');
  let error = $state('');

  $effect(() => {
    message = $page.props.flash?.message || '';
    error = $page.props.flash?.error || '';
  });

  const invalidCount = totalCount - validCount;
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6">
    <div>
      <Link href="/applications/import" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
        <ArrowLeft class="size-4" />
        Back to Import
      </Link>
    </div>

    <div>
      <h1 class="text-2xl font-semibold">Preview Import Data</h1>
      <p class="text-sm text-muted-foreground mt-1">
        Review parsed data before importing. Invalid rows are highlighted.
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

    <!-- Summary -->
    <div class="flex gap-4 text-sm">
      <div class="rounded bg-muted px-3 py-2">
        <span class="font-medium">{totalCount}</span> total rows
      </div>
      <div class="rounded bg-green-50 px-3 py-2 text-green-700">
        <Check class="inline size-4 mr-1" />
        <span class="font-medium">{validCount}</span> valid
      </div>
      <div class="rounded bg-red-50 px-3 py-2 text-red-700">
        <X class="inline size-4 mr-1" />
        <span class="font-medium">{invalidCount}</span> invalid
      </div>
    </div>

    <form onsubmit={submitForm} class="space-y-4">
      <!-- Table -->
      <div class="rounded-lg border border-border overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-muted">
            <tr>
              <th class="px-3 py-2 text-left w-10">
                <input
                  type="checkbox"
                  checked={selectAll}
                  onchange={toggleAll}
                  class="rounded"
                />
              </th>
              <th class="px-3 py-2 text-left w-16">Row</th>
              <th class="px-3 py-2 text-left">First Name</th>
              <th class="px-3 py-2 text-left">Last Name</th>
              <th class="px-3 py-2 text-left">Email</th>
              <th class="px-3 py-2 text-left">Status</th>
            </tr>
          </thead>
          <tbody>
            {#each records.slice(0, 50) as record}
              <tr class:bg-red-50={!record.is_valid} class:bg-white={record.is_valid}>
                <td class="px-3 py-2">
                  {#if record.is_valid}
                    <input
                      type="checkbox"
                      checked={selectedIds.has(record.id)}
                      onchange={() => toggleRow(record.id)}
                      class="rounded"
                    />
                  {:else}
                    <X class="size-4 text-red-500" />
                  {/if}
                </td>
                <td class="px-3 py-2 text-muted-foreground">{record.row}</td>
                <td class="px-3 py-2">{record.data.first_name || '—'}</td>
                <td class="px-3 py-2">{record.data.last_name || '—'}</td>
                <td class="px-3 py-2">{record.data.email || '—'}</td>
                <td class="px-3 py-2">
                  {#if record.is_valid}
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                      <Check class="size-3 mr-1" /> Valid
                    </span>
                  {:else}
                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                      <AlertTriangle class="size-3 mr-1" />
                      {record.errors[0]}
                    </span>
                  {/if}
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
        {#if records.length > 50}
          <div class="px-3 py-2 text-sm text-muted-foreground bg-muted">
            Showing 50 of {records.length} rows. Invalid rows will be skipped.
          </div>
        {/if}
      </div>

      <div class="flex gap-3">
        <Button type="submit" disabled={$form.processing || selectedIds.size === 0} class="min-h-[44px]">
          <Save class="mr-2 size-4" />
          {$form.processing ? 'Importing...' : `Import ${selectedIds.size} Selected`}
        </Button>
        <Link href="/applications/import">
          <Button type="button" variant="outline" class="min-h-[44px]">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>