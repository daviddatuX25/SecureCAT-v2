<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import * as Table from '@/Components/ui/table';
  import * as ToggleGroup from '@/Components/ui/toggle-group';
  import * as Dialog from '@/Components/ui/dialog';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Input } from '@/Components/ui/input';
  import ScheduleAssistantPanel from '@/Components/ScheduleAssistantPanel.svelte';
  import InfoPopover from '@/Components/InfoPopover.svelte';
  import ViewModeToggle from '@/Components/ViewModeToggle.svelte';
  import { Plus, LayoutGrid, Table2, MonitorSmartphone, Eye, Pencil, ChevronDown, Filter, ClipboardList, Sparkles, DoorOpen, Send, Undo, X, Trash2 } from 'lucide-svelte';

  let { sessions, filters = {}, statuses = [], view = 'admin', schedule_assistant = null } = $props();

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
    router.get('/admin/exam-scheduling', {
      search: filterSearch || undefined,
      status: filterStatus || undefined,
      date_from: filterDateFrom || undefined,
      date_to: filterDateTo || undefined,
      page: 1,
    }, { preserveState: true });
  }

  let viewMode = $state('responsive');
  let deleteId = $state(null);

  const list = $derived(sessions?.data ?? []);

  function confirmDelete(id) {
    deleteId = id;
  }

  function cancelDelete() {
    deleteId = null;
  }

  function doDelete() {
    if (deleteId) {
      router.delete(`/admin/exam-scheduling/${deleteId}`, { onSuccess: () => (deleteId = null) });
    }
  }

  function getStateAction(session) {
    if (session.status === 'draft') return { label: 'Publish', icon: Send, method: 'post', href: `/admin/exam-scheduling/${session.id}/publish` };
    if (session.status === 'published') return { label: 'Unpublish', icon: Undo, method: 'post', href: `/admin/exam-scheduling/${session.id}/unpublish` };
    if (session.status === 'in_progress') return { label: 'Cancel', icon: X, method: 'post', href: `/admin/exam-scheduling/${session.id}/cancel` };
    return null;
  }
</script>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
    <div class="space-y-6 min-w-0">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-muted-foreground">View and manage exam sessions by date and status</p>
      </div>
      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        {#if !isProctorView && schedule_assistant}
          <Button
            variant="outline"
            class="min-h-[44px] gap-2"
            onclick={() => (assistantOpen = true)}
          >
            <Sparkles class="h-4 w-4" />
            <span class="hidden sm:inline">Schedule with AI</span>
          </Button>
        {/if}
        {#if !isProctorView}
          <Link href="/admin/exam-scheduling/create">
            <Button class="min-h-[44px] gap-2">
              <Plus class="h-4 w-4" />
              <span class="hidden sm:inline">Create Session</span>
            </Button>
          </Link>
          <Link href="/admin/rooms">
            <Button variant="outline" class="min-h-[44px] gap-2">
              <DoorOpen class="h-4 w-4" />
              <span class="hidden sm:inline">Manage Rooms</span>
            </Button>
          </Link>
        {/if}
      </div>
    </div>


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

    <div class="space-y-3">
      <!-- View toggle as sibling to table container -->
      <div class="flex justify-end">
        <ViewModeToggle bind:value={viewMode} />
      </div>

      <div class="min-w-0 {viewMode === 'cards' ? 'hidden' : ''}">
        <div class="w-full min-w-0 overflow-x-auto scrollbar-hide">
        <Table.Root class="w-full min-w-[640px] text-sm">
          <Table.Header class="bg-muted/50">
            <Table.Row>
              <Table.Head class="px-4 py-3">Date</Table.Head>
              <Table.Head class="px-4 py-3">Time</Table.Head>
              <Table.Head class="px-4 py-3">Room</Table.Head>
              <Table.Head class="px-4 py-3">Status</Table.Head>
              <Table.Head class="px-4 py-3">Proctors</Table.Head>
              <Table.Head class="text-center px-4 py-3">Actions</Table.Head>
            </Table.Row>
          </Table.Header>
          <Table.Body>
            {#each list as session}
              <Table.Row class="border-t border-border cursor-pointer hover:bg-muted/30" onclick={() => router.visit(`/admin/exam-scheduling/${session.id}`)}>
                <Table.Cell class="px-4 py-3">{formatDate(session.date)}</Table.Cell>
                <Table.Cell class="px-4 py-3">
                  {formatTime(session.start_time)}{#if session.end_time} – {formatTime(session.end_time)}{/if}
                </Table.Cell>
                <Table.Cell class="px-4 py-3">{session.room?.name ?? '—'}</Table.Cell>
                <Table.Cell class="px-4 py-3">
                  <Badge variant={statusVariant(session.status)}>{statusLabel(session.status)}</Badge>
                </Table.Cell>
                <Table.Cell>
                  {#if (session.proctors ?? []).length > 0}
                    {(session.proctors ?? []).map((p) => p.name).join(', ')}
                  {:else}
                    —
                  {/if}
                </Table.Cell>
                <Table.Cell class="text-center">
                    <div class="flex justify-center gap-2">
                      {#if !isProctorView && session.status === 'draft'}
                        <Link href={`/admin/exam-scheduling/${session.id}/edit`}>
                          <Button variant="ghost" size="sm" class="h-8 px-2 text-xs" onclick={(e) => e.stopPropagation()}>
                            <Pencil class="mr-1.5 h-3.5 w-3.5" />
                            Edit
                          </Button>
                        </Link>
                      {/if}
                      {#if !isProctorView && session.status !== 'completed'}
                        <Button
                          variant="ghost"
                          size="sm"
                          class="h-8 px-2 text-xs text-destructive"
                          onclick={() => confirmDelete(session.id)}
                        >
                          <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                          Delete
                        </Button>
                      {/if}
                    </div>
                </Table.Cell>
              </Table.Row>
            {:else}
              <Table.Row>
                <Table.Cell colspan={6} class="py-12 text-center text-muted-foreground">
                  {isProctorView ? 'No assigned sessions.' : 'No exam sessions yet. Create one to get started.'}
                </Table.Cell>
              </Table.Row>
            {/each}
          </Table.Body>
        </Table.Root>
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
                  <Link href={`/admin/exam-scheduling/${session.id}`} class="flex-1 min-w-0">
                    <Button variant="outline" size="sm" class="w-full min-h-[44px]">
                      <Eye class="h-4 w-4 mr-1.5" />
                      Manage
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
          <InfoPopover
            content="Chat with the assistant to refine your schedule. After you get a reply, click Generate Schedule to create a preview."
            label="Beta"
          />
          <Dialog.Description id="schedule-assistant-description" class="sr-only">
            Chat with the assistant to refine your schedule. After you get a reply, click Generate Schedule to create a preview.
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

  {#if deleteId}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
      <div class="rounded-lg bg-card p-6 shadow-lg max-w-sm w-full">
        <h2 class="text-lg font-semibold">Delete exam session?</h2>
        <p class="mt-2 text-sm text-muted-foreground">This action cannot be undone.</p>
        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" onclick={cancelDelete}>Cancel</Button>
          <Button variant="destructive" onclick={doDelete}>Delete</Button>
        </div>
      </div>
    </div>
  {/if}
</AuthenticatedLayout>
