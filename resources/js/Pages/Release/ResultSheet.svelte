<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { usePage } from '@inertiajs/svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { ArrowLeft } from 'lucide-svelte';

  let { sessionId = '1', applicantId = '1001', applicant = {}, scores = [], printed = false, templateHtml = null, templateError = null, paperSize = 'a4', orientation = 'portrait', logicalUnit = 'full' } = $props();

  const pageWidthMm = $derived(
    paperSize === 'legal' ? 216 : paperSize === 'letter' ? 216 : 210
  );

  const _page = usePage();
  const printDisabled = $derived(($_page?.props?.release_mode ?? 'online') === 'online');
  const sid = $derived(String(sessionId));

  const breadcrumbs = $derived([
    { label: 'Release', href: '/admin/release' },
    { label: 'Session #' + sid, href: `/admin/release/print/${sid}` },
    { label: 'Print' }
  ]);

  const overallPct = $derived(scores.length ? Math.round(scores.reduce((a, s) => a + s.pct, 0) / scores.length) : 0);

  let markedPrinted = $state(printed);
  $effect(() => {
    markedPrinted = printed;
  });

  function printSheet() {
    window.print();
  }

  function toggleMarkPrinted() {
    const next = !markedPrinted;
    markedPrinted = next;
    router.post(`/admin/release/print/${sid}/mark-printed`, {
      applicant_ids: [applicant.id],
      printed: next,
    }, { preserveScroll: true });
  }
</script>

<svelte:head>
  <title>Result sheet - {applicant.name} - SecureCAT</title>
  {@html `<style>@media print { @page { size: ${paperSize} ${orientation}; margin: 0; } }</style>`}
</svelte:head>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="print:hidden p-4 space-y-4">
    <Link href={"/admin/release/print/" + sid} class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
      <ArrowLeft class="h-4 w-4" />
      Back to print batch
    </Link>
    <div class="flex flex-wrap gap-3">
      <Button onclick={printSheet} disabled={printDisabled} title={printDisabled ? 'Switch to F2F or Both release mode in Settings to enable printing.' : undefined} class="min-h-[44px]">Print this sheet</Button>
      <Button variant="outline" onclick={toggleMarkPrinted} class="min-h-[44px]">
        {markedPrinted ? 'Unmark printed' : 'Mark as printed'}
      </Button>
      {#if !printDisabled && !templateError && templateHtml}
        <a href={`/admin/release/print/${sid}/applicants/${applicant.id}/pdf`} target="_blank" rel="noopener">
          <Button variant="secondary" class="min-h-[44px]">View PDF</Button>
        </a>
      {/if}
    </div>
    {#if printDisabled}
      <p class="text-xs text-muted-foreground">
        Printing is disabled in online-only release mode.
        <a href="/admin/settings" class="underline">Change in Settings</a>
      </p>
    {/if}
  </div>

  <div class="p-6 mx-auto print:p-4 print:max-w-none" style="max-width: {pageWidthMm}mm;">
    {#if templateError}
      <div class="rounded-lg border border-destructive/50 bg-destructive/10 p-6 text-destructive">
        <p>{templateError}</p>
        <Link href="/admin/release/result-templates" class="mt-4 inline-block text-sm underline">Go to Result templates</Link>
      </div>
    {:else if templateHtml}
      <div class="border border-foreground/20 rounded-lg p-6 print:border print:rounded-none print:p-6 result-sheet-content">
        {@html templateHtml}
      </div>
    {:else}
      <div class="rounded-lg border border-muted p-6 text-muted-foreground">
        <p>No template available.</p>
      </div>
    {/if}
  </div>
</AuthenticatedLayout>
