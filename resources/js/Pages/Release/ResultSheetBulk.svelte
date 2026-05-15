<script>
  import { onMount } from 'svelte';
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { usePage } from '@inertiajs/svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { ArrowLeft } from 'lucide-svelte';

  let {
    sessionId = '1',
    applicantIds = [],
    applicants = [],
    sheetsHtml = [],
    templateError = null,
    paperSize: initialPaperSize = 'a4',
    orientation = 'portrait',
    logicalUnit = 'full',
    paperOptions = { a4: 'A4', letter: 'Letter' },
  } = $props();
  const sid = $derived(String(sessionId));

  const _page = usePage();
  const printDisabled = $derived(($_page?.props?.release_mode ?? 'online') === 'online');

  const breadcrumbs = $derived(
    sessionId
      ? [
          { label: 'Release', href: '/admin/release' },
          { label: 'Session #' + sid, href: `/admin/release/print/${sid}` },
          { label: 'Print' }
        ]
      : [
          { label: 'Release', href: '/admin/release' },
          { label: 'Print' }
        ]
  );

  let markedAllPrinted = $state(false);
  let paperSize = $state(initialPaperSize);
  let scalePercent = $state(100);

  const MM_TO_PX = 96 / 25.4;
  const HALF_HEIGHT_MM = 148.5;
  const SAFETY_FACTOR = 0.99;

  const isHalf = $derived(['half_a4', 'half_legal', 'half_letter'].includes(logicalUnit));
  const pageWidthMm = $derived(paperSize === 'legal' ? 216 : paperSize === 'letter' ? 215.9 : 210);
  const pageHeightMm = $derived(paperSize === 'letter' ? 279.4 : 297);
  const userScale = $derived(scalePercent / 100);

  function fitToBounds() {
    const scale = Math.max(0.1, Math.min(1, userScale));
    if (isHalf) {
      const targetPx = HALF_HEIGHT_MM * MM_TO_PX * SAFETY_FACTOR;
      document.querySelectorAll('.half-layout-page .print-template--half').forEach((half) => {
        const h = half.scrollHeight;
        if (h > targetPx && h > 0) {
          half.style.zoom = String((targetPx / h) * scale);
        } else {
          half.style.zoom = scale < 1 ? String(scale) : '';
        }
      });
    } else {
      const targetPx = pageHeightMm * MM_TO_PX * SAFETY_FACTOR;
      document.querySelectorAll('.result-sheet-content:not(.half-layout-page) .print-template').forEach((tmpl) => {
        const h = tmpl.scrollHeight;
        if (h > targetPx && h > 0) {
          tmpl.style.zoom = String((targetPx / h) * scale);
        } else {
          tmpl.style.zoom = scale < 1 ? String(scale) : '';
        }
      });
    }
  }

  function runFit() {
    requestAnimationFrame(() => requestAnimationFrame(fitToBounds));
  }

  function printAll() {
    window.print();
  }

  function toggleMarkAllPrinted() {
    markedAllPrinted = !markedAllPrinted;
    router.post(`/admin/release/print/${sid}/mark-printed`, {
      applicant_ids: applicants.map((a) => a.id),
      printed: markedAllPrinted,
    }, { preserveScroll: true });
  }

  onMount(() => {
    runFit();
    document.fonts?.ready?.then(runFit);
  });

  $effect(() => {
    sheetsHtml;
    logicalUnit;
    paperSize;
    scalePercent;
    runFit();
  });
</script>

<svelte:head>
  <title>Print bulk - {applicants.length} result sheets - SecureCAT</title>
  {@html `<style>@media print { @page { size: ${paperSize} ${orientation}; margin: 0; } }</style>`}
</svelte:head>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="print:hidden p-4 space-y-4">
    {#if sessionId}
      <Link href={"/admin/release/print/" + sid} class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
        <ArrowLeft class="h-4 w-4" />
        Back to print batch
      </Link>
    {:else}
      <Link href="/admin/release" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
        <ArrowLeft class="h-4 w-4" />
        Back to release
      </Link>
    {/if}
    <div class="flex flex-wrap gap-3 items-center">
      {#if sheetsHtml.length > 0 && !templateError}
        <div class="flex items-center gap-2">
          <label for="paper-size" class="text-sm font-medium">Paper</label>
          <select
            id="paper-size"
            bind:value={paperSize}
            class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
          >
            {#each Object.entries(paperOptions) as [k, v]}
              <option value={k}>{v}</option>
            {/each}
          </select>
        </div>
        <div class="flex items-center gap-2">
          <label for="scale" class="text-sm font-medium">Scale</label>
          <select
            id="scale"
            bind:value={scalePercent}
            class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
          >
            {#each [100, 95, 90, 85, 80] as pct}
              <option value={pct}>{pct}%</option>
            {/each}
          </select>
        </div>
      {/if}
      <Button
        variant="outline"
        onclick={printAll}
        class="min-h-[44px]"
        disabled={printDisabled || sheetsHtml.length === 0 || !!templateError}
        title={printDisabled ? 'Switch to F2F or Both release mode in Settings to enable printing.' : undefined}
      >
        Print all {applicants.length} sheets
      </Button>
      {#if sessionId}
        <Button variant="outline" onclick={toggleMarkAllPrinted} class="min-h-[44px]">
          {markedAllPrinted ? 'Unmark all printed' : 'Mark all as printed'}
        </Button>
      {/if}
    </div>
  </div>

  <div
    class="p-6 mx-auto space-y-8 print:p-0 print:max-w-none print:space-y-0"
    style="max-width: {pageWidthMm}mm;"
  >
    {#if templateError}
      <div class="rounded-lg border border-destructive/50 bg-destructive/10 p-6 text-destructive">
        <p>{templateError}</p>
        <Link href="/admin/release/result-templates" class="mt-4 inline-block text-sm underline">Go to Result templates</Link>
      </div>
    {:else if sheetsHtml.length > 0}
      {#each sheetsHtml as html}
        <div
          class="border border-foreground/20 rounded-lg p-6 result-sheet-content bg-white {isHalf ? 'half-layout-page' : ''}"
        >
          {@html html}
        </div>
      {/each}
    {:else}
      <div class="rounded-lg border border-muted p-6 text-muted-foreground">
        <p>No template or applicants selected.</p>
      </div>
    {/if}
  </div>
</AuthenticatedLayout>

<style>
  .half-layout-page :global(> .print-template--dual) {
    height: 297mm;
  }
  .half-layout-page :global(.print-template--half) {
    overflow: hidden;
  }
  @media print {
    .result-sheet-content {
      page-break-after: always;
      border: none;
      border-radius: 0;
      box-shadow: none;
    }
    .result-sheet-content:last-child {
      page-break-after: auto;
    }
  }
</style>