<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { usePage } from '@inertiajs/svelte';
  import * as Select from '@/Components/ui/select';
  import { Button } from '@/Components/ui/button';
  import { router } from '@inertiajs/svelte';
  import {
    FileText, BarChart3, GraduationCap, Users, XCircle,
    Target, Calendar, MessageSquare, Send, Download, Loader2, AlertCircle,
  } from 'lucide-svelte';

  let { academicYears = [], activeAcademicYearId, reports = [], counts = {} } = $props();

  const page = usePage();
  const roles = $derived(($page.props.auth?.user?.roles ?? []).map(r => r.name));
  const isSuperAdmin = $derived(roles.includes('super_admin'));
  const isRegistrar = $derived(roles.includes('registrar_administrator'));
  const isTestAdmin = $derived(roles.includes('test_administrator'));

  const breadcrumbs = [{ label: 'Reports' }];

  const iconMap = {
    'file-text': FileText,
    'bar-chart-3': BarChart3,
    'graduation-cap': GraduationCap,
    'users': Users,
    'x-circle': XCircle,
    'target': Target,
    'calendar': Calendar,
    'message-square': MessageSquare,
    'send': Send,
  };

  let selectedAyId = $state(String(activeAcademicYearId ?? ''));
  let exportingType = $state(null);
  let exportError = $state(null);

  // Domain-gated visibility
  function canSeeReport(report) {
    if (isSuperAdmin) return true;
    if (report.domain === 'registrar' && isRegistrar) return true;
    if (report.domain === 'guidance' && isTestAdmin) return true;
    return false;
  }

  const visibleReports = $derived(reports.filter(canSeeReport));
  const registrarReports = $derived(visibleReports.filter(r => r.domain === 'registrar'));
  const guidanceReports = $derived(visibleReports.filter(r => r.domain === 'guidance'));

  function onAcademicYearChange(value) {
    if (!value) return;
    selectedAyId = value;
    router.get('/admin/reports', { academic_year_id: value }, { preserveState: true, preserveScroll: true });
  }

  async function doExport(type) {
    exportError = null;
    exportingType = type;
    try {
      const params = new URLSearchParams({ academic_year_id: selectedAyId });
      const res = await fetch(`/admin/reports/export/${type}?${params.toString()}`, { credentials: 'same-origin' });
      if (!res.ok) {
        if (res.status === 403) {
          exportError = 'You do not have permission to access this report.';
        } else {
          exportError = `Export failed (${res.status}). Please try again.`;
        }
        return;
      }
      const blob = await res.blob();
      const disposition = res.headers.get('Content-Disposition') ?? '';
      const filenameMatch = disposition.match(/filename="(.+?)"/);
      const filename = filenameMatch ? filenameMatch[1] : `${type}.xlsx`;
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      a.click();
      URL.revokeObjectURL(url);
    } catch (err) {
      exportError = 'Network error. Please check your connection and try again.';
    } finally {
      exportingType = null;
    }
  }

  const selectedAyLabel = $derived(
    academicYears.find(ay => String(ay.id) === selectedAyId)?.label ?? 'Select academic year'
  );
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-8 min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <p class="text-sm text-muted-foreground">Generate and download spreadsheet reports for the selected academic year.</p>
      </div>
      <div class="flex items-center gap-3">
        <Select.Root type="single" value={selectedAyId} onValueChange={onAcademicYearChange}>
          <Select.Trigger class="w-[260px] min-h-[44px]">
            {#if selectedAyId}
              {selectedAyLabel}
            {:else}
              <span class="text-muted-foreground">Select academic year</span>
            {/if}
          </Select.Trigger>
          <Select.Content>
            {#each academicYears as ay}
              <Select.Item value={String(ay.id)} label={ay.label}>
                {ay.label}
                {#if ay.is_active}
                  <span class="ml-2 text-xs text-primary font-semibold">(Active)</span>
                {/if}
              </Select.Item>
            {/each}
          </Select.Content>
        </Select.Root>
      </div>
    </div>

    <!-- Error banner -->
    {#if exportError}
      <div class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive flex items-center gap-2">
        <AlertCircle class="h-4 w-4 shrink-0" />
        {exportError}
        <button type="button" class="ml-auto text-destructive/70 hover:text-destructive" onclick={() => exportError = null}>✕</button>
      </div>
    {/if}

    <!-- Summary stats bar -->
    {#if counts.total_applications !== undefined}
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-xl border border-border bg-card p-4">
          <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Total Applications</p>
          <p class="text-2xl font-bold text-foreground mt-1">{counts.total_applications?.toLocaleString() ?? 0}</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-4">
          <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Exam Sessions</p>
          <p class="text-2xl font-bold text-foreground mt-1">{counts.exam_sessions?.toLocaleString() ?? 0}</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-4">
          <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Released</p>
          <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{counts.released_count?.toLocaleString() ?? 0}</p>
        </div>
        <div class="rounded-xl border border-border bg-card p-4">
          <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Dismissed</p>
          <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{counts.dismissed_count?.toLocaleString() ?? 0}</p>
        </div>
      </div>
    {/if}

    <!-- Admissions Reports -->
    {#if registrarReports.length > 0}
      <section>
        <h2 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Admissions</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {#each registrarReports as report (report.type)}
            {@const Icon = iconMap[report.icon] ?? FileText}
            <div class="group relative rounded-xl border border-border bg-card hover:border-primary/30 hover:shadow-md hover:shadow-primary/5 transition-all duration-300 flex flex-col">
              <div class="p-5 flex-1">
                <div class="flex items-start gap-3 mb-3">
                  <div class="rounded-lg bg-primary/10 p-2.5 text-primary shrink-0 group-hover:bg-primary/20 transition-colors">
                    <Icon class="h-5 w-5" />
                  </div>
                  <div class="min-w-0">
                    <h3 class="font-semibold text-foreground text-sm leading-tight">{report.title}</h3>
                  </div>
                </div>
                <p class="text-xs text-muted-foreground leading-relaxed">{report.description}</p>
              </div>
              <div class="px-5 pb-4">
                <Button
                  variant="outline"
                  size="sm"
                  class="w-full min-h-[40px] gap-2 group-hover:border-primary/40 group-hover:text-primary transition-colors"
                  onclick={() => doExport(report.type)}
                  disabled={exportingType !== null || !selectedAyId}
                >
                  {#if exportingType === report.type}
                    <Loader2 class="h-4 w-4 animate-spin" />
                    Generating…
                  {:else}
                    <Download class="h-4 w-4" />
                    Download .xlsx
                  {/if}
                </Button>
              </div>
            </div>
          {/each}
        </div>
      </section>
    {/if}

    <!-- Assessment Reports -->
    {#if guidanceReports.length > 0}
      <section>
        <h2 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Assessment</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {#each guidanceReports as report (report.type)}
            {@const Icon = iconMap[report.icon] ?? FileText}
            <div class="group relative rounded-xl border border-border bg-card hover:border-primary/30 hover:shadow-md hover:shadow-primary/5 transition-all duration-300 flex flex-col">
              <div class="p-5 flex-1">
                <div class="flex items-start gap-3 mb-3">
                  <div class="rounded-lg bg-amber-500/10 p-2.5 text-amber-600 dark:text-amber-400 shrink-0 group-hover:bg-amber-500/20 transition-colors">
                    <Icon class="h-5 w-5" />
                  </div>
                  <div class="min-w-0">
                    <h3 class="font-semibold text-foreground text-sm leading-tight">{report.title}</h3>
                  </div>
                </div>
                <p class="text-xs text-muted-foreground leading-relaxed">{report.description}</p>
              </div>
              <div class="px-5 pb-4">
                <Button
                  variant="outline"
                  size="sm"
                  class="w-full min-h-[40px] gap-2 group-hover:border-amber-500/40 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors"
                  onclick={() => doExport(report.type)}
                  disabled={exportingType !== null || !selectedAyId}
                >
                  {#if exportingType === report.type}
                    <Loader2 class="h-4 w-4 animate-spin" />
                    Generating…
                  {:else}
                    <Download class="h-4 w-4" />
                    Download .xlsx
                  {/if}
                </Button>
              </div>
            </div>
          {/each}
        </div>
      </section>
    {/if}

    <!-- Empty state -->
    {#if visibleReports.length === 0}
      <div class="rounded-xl border border-dashed border-border p-12 text-center">
        <FileText class="h-10 w-10 mx-auto text-muted-foreground/50 mb-3" />
        <p class="text-muted-foreground">No reports available for your role.</p>
      </div>
    {/if}
  </div>
</AuthenticatedLayout>
