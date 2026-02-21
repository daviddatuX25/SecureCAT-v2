<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import { Input } from '@/Components/ui/input';
  import * as Table from '@/Components/ui/table';
  import { ArrowLeft, User, ChevronRight, QrCode, Search } from 'lucide-svelte';

  let { applicants = [] } = $props();
  let counselorNotes = $state('');
  const mockApplicants = $state([
    { id: 1, name: 'Maria Santos', reference: 'APP-2025-001', scores: [
      { domain: 'Spatial Awareness', pct: 72 },
      { domain: 'Numerical Ability', pct: 80 },
      { domain: 'Verbal Reasoning', pct: 56 },
      { domain: 'Abstract Reasoning', pct: 70 },
      { domain: 'Logical Reasoning', pct: 76 },
      { domain: 'Perceptual Speed & Accuracy', pct: 80 },
    ], overallPct: 72, systemRecommendation: 'BSIT' },
    { id: 2, name: 'James Chen', reference: 'APP-2025-002', scores: [
      { domain: 'Spatial Awareness', pct: 65 },
      { domain: 'Numerical Ability', pct: 88 },
      { domain: 'Verbal Reasoning', pct: 70 },
      { domain: 'Abstract Reasoning', pct: 75 },
      { domain: 'Logical Reasoning', pct: 72 },
      { domain: 'Perceptual Speed & Accuracy', pct: 90 },
    ], overallPct: 77, systemRecommendation: 'BAP' },
  ]);

  const displayApplicants = $derived(applicants.length > 0 ? applicants : mockApplicants);
  let currentIndex = $state(0);
  let searchQuery = $state('');
  let searchSuggestions = $state([]);

  const currentApplicant = $derived(displayApplicants[currentIndex] ?? null);

  // Mock: search by reference/name
  function onSearchInput() {
    const q = searchQuery.trim().toLowerCase();
    if (q.length < 2) {
      searchSuggestions = [];
      return;
    }
    searchSuggestions = displayApplicants.filter(
      (a) =>
        a.reference.toLowerCase().includes(q) || a.name.toLowerCase().includes(q)
    ).slice(0, 5);
  }

  function selectApplicant(index) {
    currentIndex = index;
    searchQuery = '';
    searchSuggestions = [];
    counselorNotes = '';
  }

  function nextApplicant() {
    if (currentIndex < displayApplicants.length - 1) {
      currentIndex++;
      counselorNotes = '';
    }
  }

  function prevApplicant() {
    if (currentIndex > 0) {
      currentIndex--;
      counselorNotes = '';
    }
  }

  function onQrDecode(reference) {
    const idx = displayApplicants.findIndex((a) => a.reference === reference);
    if (idx >= 0) selectApplicant(idx);
  }
</script>

<svelte:head>
  <title>Consultation day - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0 max-w-4xl mx-auto">
    <div class="flex items-center justify-between">
      <Link href="/consultation" class="text-sm text-muted-foreground hover:text-foreground inline-flex items-center gap-1 min-h-[44px] items-center">
        <ArrowLeft class="h-4 w-4" />
        Back to consultation
      </Link>
    </div>

    <!-- Quick lookup: QR scan or search -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <QrCode class="h-5 w-5" />
          Look up applicant
        </CardTitle>
        <CardDescription>Scan QR code (placeholder) or type reference/name to search.</CardDescription>
      </CardHeader>
      <CardContent class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            type="text"
            placeholder="Reference or name (e.g. APP-2025-001)"
            bind:value={searchQuery}
            oninput={onSearchInput}
            class="pl-9 min-h-[48px] text-base"
            autocomplete="off"
          />
          {#if searchSuggestions.length > 0}
            <div class="absolute top-full left-0 right-0 mt-1 bg-card border border-border rounded-lg shadow-lg z-10 py-2">
              {#each searchSuggestions as app, i}
                <button
                  type="button"
                  onclick={() => selectApplicant(applicants.indexOf(app))}
                  class="w-full px-4 py-2 text-left hover:bg-muted/50 text-sm"
                >
                  <span class="font-medium">{app.reference}</span> — {app.name}
                </button>
              {/each}
            </div>
          {/if}
        </div>
        <div class="text-sm text-muted-foreground flex items-center gap-2">
          <QrCode class="h-5 w-5" />
          QR scanner (integrate device camera for real implementation)
        </div>
      </CardContent>
    </Card>

    <!-- Current applicant view -->
    {#if currentApplicant}
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <User class="h-5 w-5" />
            {currentApplicant.name}
          </CardTitle>
          <CardDescription>{currentApplicant.reference}</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Scores -->
          <div>
            <h3 class="text-sm font-medium mb-2">Exam scores · Overall {currentApplicant.overallPct}%</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              {#each currentApplicant.scores as s}
                <div class="text-sm rounded border border-border px-2 py-1.5">
                  <span class="text-muted-foreground">{s.domain}:</span> {s.pct}%
                </div>
              {/each}
            </div>
          </div>

          <!-- System recommendation -->
          <div class="rounded-lg bg-primary/5 border border-primary/20 p-3">
            <p class="text-sm font-medium">System recommendation</p>
            <p class="text-lg font-semibold text-primary">{currentApplicant.systemRecommendation}</p>
          </div>

          <!-- Counselor notes -->
          <div>
            <label for="notes" class="text-sm font-medium block mb-1">Counselor notes / corrections</label>
            <textarea
              id="notes"
              bind:value={counselorNotes}
              rows="3"
              class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              placeholder="Add or correct recommendation..."
            ></textarea>
          </div>

          <!-- Next / Prev applicant -->
          <div class="flex justify-between pt-4 border-t border-border">
            <Button variant="outline" onclick={prevApplicant} disabled={currentIndex === 0}>
              Previous
            </Button>
            <Button onclick={nextApplicant} disabled={currentIndex === applicants.length - 1}>
              <ChevronRight class="h-4 w-4 mr-2" />
              Next applicant
            </Button>
          </div>
        </CardContent>
      </Card>
    {:else}
      <Card>
        <CardContent class="py-12 text-center text-muted-foreground">
          <User class="h-12 w-12 mx-auto mb-4 opacity-50" />
          <p>Scan QR code or search for an applicant to start consultation.</p>
        </CardContent>
      </Card>
    {/if}
  </div>
</AuthenticatedLayout>
