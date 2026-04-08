<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { usePage } from '@inertiajs/svelte';
  import * as ToggleGroup from '@/Components/ui/toggle-group';
  import * as Dialog from '@/Components/ui/dialog';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Input } from '@/Components/ui/input';
  import ScheduleAssistantPanel from '@/Components/ScheduleAssistantPanel.svelte';
  import { Plus, LayoutGrid, Table2, MonitorSmartphone, Eye, Pencil, ChevronDown, Filter, ClipboardList, Sparkles, DoorOpen } from 'lucide-svelte';

  let { sessions, filters = {}, statuses = [], view = 'admin', schedule_assistant = null } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const isProctorView = $derived(view === 'proctor');
  const breadcrumbs = $derived(
    view === 'proctor'
      ? [{ label: 'My Sessions' }]
      : [{ label: 'Exam Scheduling' }]
  );

  let assistantOpen = $state(false);

  $effect(() => {
    if (schedule_assistant && typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      if (params.get('open') === 'schedule-assistant') {
        assistantOpen = true;
        const url = new URL(window.location.href);
        url.searchParams.delete('open');
        window.history.replaceState({}, '', url.pathname + url.search);
      }
    }
  });

  function onScheduleApplied() {
    assistantOpen = false;
    router.reload();
  }

  let filterSearch = $state('');
  let filterStatus = $state('');
  let filterDateFrom = $state('');
  let filterDateTo = $state('');
  $effect(() => {
    filterSearch = filters.search ?? '';
    filterStatus = filters.status ?? '';
    filterDateFrom = filters.date_from ?? '';
    filterDateTo = filters.date_to ?? '';
  });

  function statusVariant(status) {
    if (status === 'draft') return 'muted';
    if (status === 'published') return 'success';
    if (status === 'in_progress') return 'warning';
    if (status === 'completed') return 'outline';
    if (status === 'cancelled') return 'danger';
    return 'outline';
  }

  function statusLabel(value) {
    const s = statuses.find((x) => x.value === value);
    return s ? s.label : value;
  }

  function formatDate(value) {
    if (value == null || value === '') return '—';
    const s = String(value);
    const part = s.split('T')[0];
    if (!part) return '—';
    const [y, m, d] = part.split('-').map(Number);
    const date = new Date(y, (m || 1) - 1, d || 1);
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).replace(',', '');
  }

  function formatTime(value) {
    if (value == null || value === '') return '—';
    const parts = String(value).split(':');
    const hours = parseInt(parts[0], 10) || 0;
    const mins = parseInt(parts[1], 10) || 0;
    const h = hours % 12 || 12;
    const ampm = hours < 12 ? 'AM' : 'PM';
    return `${h}:${String(mins).padStart(2, '0')} ${ampm}`;
  }

  let mobileFiltersDetails = $state(null);

  function applyFilters() {
    if (mobileFiltersDetails) mobileFiltersDetails.open = false;
    router.get('/admin/test-scheduling', {
      search: filterSearch || undefined,
      status: filterStatus || undefined,
      date_from: filterDateFrom || undefined,
      date_to: filterDateTo || undefined,
      page: 1,
    }, { preserveState: true });
  }

  let viewMode = $state('responsive');
  const list = $derived(sessions?.data ?? []);
</script>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
    <div class="space-y-6 min-w-0">
    <div class="flex flex-wrap items-center gap-3">
        <ToggleGroup.Root
          type="single"
          bind:value={viewMode}
          variant="outline"
          size="sm"
          class="min-h-[44px] rounded-lg border border-border"
          aria-label="View layout"
        >
          <ToggleGroup.Item value="responsive" aria-label="Auto (responsive)" class="min-h-[44px]">
            <MonitorSmartphone class="h-4 w-4 md:mr-1.5" />
            <span class="hidden md:inline">Auto</span>
          </ToggleGroup.Item>
          <ToggleGroup.Item value="table" aria-label="Table view" class="min-h-[44px]">
            <Table2 class="h-4 w-4 md:mr-1.5" />
            <span class="hidden md:inline">Table</span>
          </ToggleGroup.Item>
          <ToggleGroup.Item value="cards" aria-label="Card view" class="min-h-[44px]">
            <LayoutGrid class="h-4 w-4 md:mr-1.5" />
            <span class="hidden md:inline">Cards</span>
          </ToggleGroup.Item>
        </ToggleGroup.Root>
        {#if !isProctorView && schedule_assistant}
          <Button
            variant="outline"
            class="min-h-[44px]"
            onclick={() => (assistantOpen = true)}
          >
            <Sparkles class="mr-2 h-4 w-4" />
            Schedule with AI
          </Button>
        {/if}
        {#if !isProctorView}
          <Link href="/admin/test-scheduling/create">
            <Button class="min-h-[44px]">
              <Plus class="mr-2 h-4 w-4" />
              Create Session
            </Button>
          </Link>
          <Link href="/admin/rooms">
            <Button variant="outline" class="min-h-[44px]">
              <DoorOpen class="mr-2 h-4 w-4" />
              Add Room
            </Button>
          </Link>
        {/if}
      </div>

    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">
        {success}
      </div>
    {/if}

    <!-- Filters: one row on desktop; on mobile search + collapsible "Filters" dropdown, dates always together, Apply always visible -->
    <div class="flex flex-col gap-3">
      <!-- Desktop: single row -->
      <div class="hidden md:flex flex-wrap items-center gap-3">
        <Input
          type="search"
          placeholder="Search room or building"
          bind:value={filterSearch}
          onkeydown={(e) => e.key === 'Enter' && applyFilters()}
          class="min-w-[160px] max-w-[220px] h-10"
        />
        <label for="filter-status-desk" class="sr-only">Status</label>
        <select
          id="filter-status-desk"
          class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm min-w-[140px]"
          bind:value={filterStatus}
        >
          <option value="">All statuses</option>
          {#each statuses as s}
            <option value={s.value}>{s.label}</option>
          {/each}
        </select>
        <div class="flex items-center gap-2">
          <label for="filter-date-from-desk" class="text-sm text-muted-foreground whitespace-nowrap">From</label>
          <input
            id="filter-date-from-desk"
            type="date"
            bind:value={filterDateFrom}
            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
          />
          <label for="filter-date-to-desk" class="text-sm text-muted-foreground whitespace-nowrap">To</label>
          <input
            id="filter-date-to-desk"
            type="date"
            bind:value={filterDateTo}
            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
          />
        </div>
        <Button onclick={applyFilters} class="min-h-[40px]">Apply</Button>
      </div>

      <!-- Mobile: search exposed, rest in collapsible dropdown, Apply always visible -->
      <div class="flex flex-wrap items-center gap-3 md:hidden">
        <Input
          type="search"
          placeholder="Search room or building"
          bind:value={filterSearch}
          onkeydown={(e) => e.key === 'Enter' && applyFilters()}
          class="min-h-[44px] flex-1 min-w-0"
        />
        <details class="relative group" bind:this={mobileFiltersDetails}>
          <summary class="list-none flex items-center gap-2 min-h-[44px] px-4 rounded-md border border-input bg-background text-sm font-medium cursor-pointer hover:bg-muted/50">
            <Filter class="h-4 w-4" />
            <span>Filters</span>
            <ChevronDown class="h-4 w-4 transition-transform group-open:rotate-180" />
          </summary>
          <div class="absolute right-0 top-full z-10 mt-1 w-[min(320px,calc(100vw-2rem))] rounded-lg border border-border bg-card p-4 shadow-lg flex flex-col gap-3">
            <div>
              <label for="filter-status-mob" class="text-sm font-medium block mb-1">Status</label>
              <select
                id="filter-status-mob"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                bind:value={filterStatus}
              >
                <option value="">All statuses</option>
                {#each statuses as s}
                  <option value={s.value}>{s.label}</option>
                {/each}
              </select>
            </div>
            <div>
              <span class="text-sm font-medium block mb-1">Date range</span>
              <div class="flex items-center gap-2">
                <input
                  id="filter-date-from-mob"
                  type="date"
                  bind:value={filterDateFrom}
                  class="flex h-10 flex-1 min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
                <span class="text-muted-foreground">–</span>
                <input
                  id="filter-date-to-mob"
                  type="date"
                  bind:value={filterDateTo}
                  class="flex h-10 flex-1 min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
              </div>
            </div>
          </div>
        </details>
        <Button onclick={applyFilters} class="min-h-[44px]">Apply</Button>
      </div>
    </div>

    <div class="glass-panel rounded-2xl overflow-hidden min-w-0 max-w-full p-6">
      <div
        class="w-full min-w-0 overflow-x-scroll overscroll-x-contain {viewMode === 'cards'
          ? 'hidden'
          : viewMode === 'table'
            ? 'block'
            : 'hidden md:block'}"
      >
        <table class="w-full min-w-[640px] text-sm">
          <thead class="bg-muted/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium">Date</th>
              <th class="px-4 py-3 text-left font-medium">Time</th>
              <th class="px-4 py-3 text-left font-medium">Room</th>
              <th class="px-4 py-3 text-left font-medium">Status</th>
              <th class="px-4 py-3 text-left font-medium">Proctors</th>
              <th class="px-4 py-3 text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {#each list as session}
              <tr class="border-t border-border hover:bg-muted/30">
                <td class="px-4 py-3">{formatDate(session.date)}</td>
                <td class="px-4 py-3">
                  {formatTime(session.start_time)}{#if session.end_time} – {formatTime(session.end_time)}{/if}
                </td>
                <td class="px-4 py-3">{session.room?.name ?? '—'}</td>
                <td class="px-4 py-3">
                  <Badge variant={statusVariant(session.status)}>{statusLabel(session.status)}</Badge>
                </td>
                <td class="px-4 py-3">
                  {#if (session.proctors ?? []).length > 0}
                    {(session.proctors ?? []).map((p) => p.name).join(', ')}
                  {:else}
                    —
                  {/if}
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-2">
                    <Link href={`/admin/test-scheduling/${session.id}`}>
                      <Button variant="ghost" size="icon" aria-label="View">
                        <Eye class="h-4 w-4" />
                      </Button>
                    </Link>
                    {#if isProctorView && (session.status === 'published' || session.status === 'in_progress')}
                      <Link href={`/proctor/sessions/${session.id}`}>
                        <Button variant="ghost" size="icon" aria-label="Open roster">
                          <ClipboardList class="h-4 w-4" />
                        </Button>
                      </Link>
                    {/if}
                    {#if !isProctorView && session.status !== 'completed'}
                      <Link href={`/admin/test-scheduling/${session.id}/edit`}>
                        <Button variant="ghost" size="icon" aria-label="Edit">
                          <Pencil class="h-4 w-4" />
                        </Button>
                      </Link>
                    {/if}
                  </div>
                </td>
              </tr>
            {:else}
              <tr>
                <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                  {isProctorView ? 'No assigned sessions.' : 'No exam sessions yet. Create one to get started.'}
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>

      <div
        class="{viewMode === 'table'
          ? 'hidden'
          : viewMode === 'cards'
            ? 'block'
            : 'block md:hidden'} p-4"
      >
        {#if list.length > 0}
          <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
            {#each list as session}
              <li class="flex flex-col gap-3 rounded-lg border border-border bg-card p-4">
                <div class="flex items-start justify-between gap-2">
                  <h3 class="font-semibold">{formatDate(session.date)} {formatTime(session.start_time)}</h3>
                  <Badge variant={statusVariant(session.status)}>{statusLabel(session.status)}</Badge>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                  <dt class="text-muted-foreground">Room</dt>
                  <dd>{session.room?.name ?? '—'}</dd>
                  <dt class="text-muted-foreground">Time</dt>
                  <dd>{formatTime(session.start_time)}{#if session.end_time} – {formatTime(session.end_time)}{/if}</dd>
                  <dt class="text-muted-foreground">Proctors</dt>
                  <dd class="col-span-1">
                    {(session.proctors ?? []).length > 0 ? (session.proctors ?? []).map((p) => p.name).join(', ') : '—'}
                  </dd>
                </dl>
                <div class="mt-auto flex flex-wrap gap-2 pt-2">
                  <Link href={`/admin/test-scheduling/${session.id}`} class="flex-1 min-w-0">
                    <Button variant="outline" size="sm" class="w-full min-h-[44px]">
                      <Eye class="h-4 w-4 mr-1.5" />
                      View
                    </Button>
                  </Link>
                  {#if isProctorView && (session.status === 'published' || session.status === 'in_progress')}
                    <Link href={`/proctor/sessions/${session.id}`}>
                      <Button variant="outline" size="sm" class="min-h-[44px]">
                        <ClipboardList class="h-4 w-4 mr-1.5" />
                        Roster
                      </Button>
                    </Link>
                  {/if}
                  {#if !isProctorView && session.status !== 'completed'}
                    <Link href={`/admin/test-scheduling/${session.id}/edit`}>
                      <Button variant="outline" size="sm" class="min-h-[44px]">
                        <Pencil class="h-4 w-4 mr-1.5" />
                        Edit
                      </Button>
                    </Link>
                  {/if}
                </div>
              </li>
            {/each}
          </ul>
        {:else}
          <p class="py-12 text-center text-muted-foreground">{isProctorView ? 'No assigned sessions.' : 'No exam sessions yet. Create one to get started.'}</p>
        {/if}
      </div>

      {#if sessions?.last_page > 1}
        <div class="flex items-center justify-between border-t border-border px-4 py-2">
          <p class="text-sm text-muted-foreground">
            Page {sessions.current_page} of {sessions.last_page}
          </p>
          <div class="flex gap-2">
            {#if sessions.prev_page_url}
              <Link href={sessions.prev_page_url}>
                <Button variant="outline" size="sm">Previous</Button>
              </Link>
            {/if}
            {#if sessions.next_page_url}
              <Link href={sessions.next_page_url}>
                <Button variant="outline" size="sm">Next</Button>
              </Link>
            {/if}
          </div>
        </div>
      {/if}
    </div>
  </div>

  {#if schedule_assistant}
    <Dialog.Root bind:open={assistantOpen}>
      <Dialog.Content class="w-[95vw] max-w-6xl max-h-[90vh] overflow-y-auto" aria-describedby="schedule-assistant-description">
        <Dialog.Header>
          <Dialog.Title>AI Exam Scheduler</Dialog.Title>
          <Dialog.Description id="schedule-assistant-description">
            Describe your scheduling needs (e.g. morning slots, dates, number of rooms). The assistant suggests a plan; generate and apply to create exam sessions and assign applicants.
          </Dialog.Description>
        </Dialog.Header>
        <div class="mt-4">
          <ScheduleAssistantPanel
            applicant_count={schedule_assistant.applicant_count}
            rooms={schedule_assistant.rooms}
            draft_sessions={schedule_assistant.draft_sessions}
            messages={schedule_assistant.messages}
            openrouter_configured={schedule_assistant.openrouter_configured}
            csrf_token={schedule_assistant.csrf_token}
            onApplied={onScheduleApplied}
          />
        </div>
      </Dialog.Content>
    </Dialog.Root>
  {/if}
</AuthenticatedLayout>
