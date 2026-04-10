<script>
  import { router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import * as Card from '@/Components/ui/card';
  import * as Table from '@/Components/ui/table';
  import { MessageSquare, Send, Sparkles, Calendar, CheckCircle2 } from 'lucide-svelte';

  let {
    applicant_count = 0,
    rooms = [],
    draft_sessions = [],
    messages: initialMessages = [],
    openrouter_configured = false,
    csrf_token = '',
    onApplied = null,
  } = $props();

  let messages = $state([...(initialMessages ?? [])]);
  let input = $state('');
  let loading = $state(false);
  let generating = $state(false);
  let applying = $state(false);
  let error = $state('');
  let structuredSchedule = $state(null);
  let applyError = $state('');
  /** True only after we receive an assistant reply during this session (not from loaded history). */
  let hasReplyThisSession = $state(false);

  const roomMap = $derived(
    Object.fromEntries((rooms ?? []).map((r) => [r.id, r]))
  );
  const draftMap = $derived(
    Object.fromEntries((draft_sessions ?? []).map((s) => [s.id, s]))
  );

  /**
   * Normalise a raw session object from the AI to the canonical field set.
   * Tries multiple known aliases so the UI is robust to AI hallucinations.
   */
  function normalizeSession(s) {
    return {
      exam_session_id: s.exam_session_id ?? s.session_id ?? null,
      room_id:        s.room_id   ?? s.roomId   ?? s.room?.id   ?? null,
      room_name:      s.room_name ?? s.room?.name ?? null,
      date:           s.date      ?? s.session_date ?? s.exam_date ?? null,
      start_time:     s.start_time ?? s.startTime ?? s.time_start ?? null,
      end_time:       s.end_time   ?? s.endTime   ?? s.time_end   ?? null,
      applicant_ids:  s.applicant_ids ?? s.applicantIds ?? s.applicants ?? [],
    };
  }

  function getScheduleRows(schedule) {
    if (!schedule?.sessions?.length) return [];
    return schedule.sessions.map((raw) => {
      const s = normalizeSession(raw);
      if (s.exam_session_id) {
        const draft = draftMap[s.exam_session_id];
        return {
          type: 'Existing draft',
          room: draft?.room?.name ?? '—',
          date: draft?.date ?? '—',
          time: draft ? [draft.start_time, draft.end_time].filter(Boolean).join('–') : '—',
          applicant_count: (s.applicant_ids ?? []).length,
        };
      }
      return {
        type: 'New',
        room: roomMap[s.room_id]?.name ?? s.room_name ?? `Room ${s.room_id ?? '?'}`,
        date: s.date ?? '—',
        time: [s.start_time, s.end_time].filter(Boolean).join('–') || '—',
        applicant_count: (s.applicant_ids ?? []).length,
      };
    });
  }

  const previewRows = $derived.by(() => getScheduleRows(structuredSchedule));

  /** Generate unlocks only after we get an assistant reply in this session (finalized plan in chat). */
  const canGenerate = $derived(openrouter_configured && hasReplyThisSession);

  const generateButtonTitle = $derived(
    !openrouter_configured
      ? 'Configure OPENROUTER_API_KEY in .env to enable'
      : !hasReplyThisSession
        ? 'Send a message and get a reply from the assistant to unlock'
        : 'Ask the AI to output a structured schedule'
  );

  async function send(requestStructured = false) {
    const text = requestStructured
      ? 'Generate the schedule now. Output the structured schedule.'
      : input.trim();
    if (!text && !requestStructured) return;
    if (loading || generating) return;

    if (!requestStructured) input = '';
    error = '';
    applyError = '';
    if (!requestStructured) {
      messages = [...messages, { role: 'user', content: text }];
    }
    loading = true;
    if (requestStructured) generating = true;

    try {
      const res = await fetch('/admin/test-scheduling/schedule-assistant/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf_token,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          message: text,
          request_structured: requestStructured,
        }),
        credentials: 'same-origin',
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        error = data.message ?? 'Something went wrong. Please try again.';
        if (!requestStructured) {
          messages = messages.filter((m) => m.role !== 'user' || m.content !== text);
        }
        return;
      }

      if (data.reply || data.structured_schedule) {
        hasReplyThisSession = true;
        messages = [
          ...messages,
          {
            role: 'assistant',
            content: data.reply ?? null,
            schedule: data.structured_schedule ?? undefined,
          },
        ];
      }
      if (data.structured_schedule) {
        structuredSchedule = data.structured_schedule;
      }
    } catch (e) {
      error = 'Network error. Please try again.';
      if (!requestStructured) {
        messages = messages.filter((m) => m.role !== 'user' || m.content !== text);
      }
    } finally {
      loading = false;
      generating = false;
    }
  }

  function handleKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      send(false);
    }
  }

  async function applySchedule() {
    if (!structuredSchedule?.sessions?.length || applying) return;
    applying = true;
    applyError = '';

    try {
      const res = await fetch('/admin/test-scheduling/schedule-assistant/apply-schedule', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf_token,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ sessions: structuredSchedule.sessions }),
        credentials: 'same-origin',
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        applyError = data.message ?? 'Failed to apply schedule.';
        return;
      }

      if (typeof onApplied === 'function') {
        onApplied();
      } else if (data.redirect_url) {
        router.visit(data.redirect_url, { preserveState: false });
      } else {
        router.visit('/admin/test-scheduling', { preserveState: false });
      }
    } catch (e) {
      applyError = 'Network error. Please try again.';
    } finally {
      applying = false;
    }
  }
</script>

<div class="space-y-6">
  <div class="rounded-lg border border-border bg-muted/30 p-4 space-y-4">
    <div class="flex items-center gap-2">
      <Sparkles class="w-5 h-5 text-primary" />
      <h2 class="text-lg font-bold text-foreground">Schedule with AI</h2>
    </div>
    <p class="text-sm text-muted-foreground">
      Describe your scheduling needs (e.g. morning slots only, 9–12, next week). The assistant will suggest options.
      When ready, click <strong>Generate schedule</strong> to get a structured plan, then <strong>Apply schedule</strong> to create sessions and assign applicants.
    </p>
    <div class="flex flex-wrap gap-4 text-sm">
      <span class="flex items-center gap-1.5">
        <Calendar class="w-4 h-4 text-muted-foreground" />
        <strong>{applicant_count}</strong> applicants to schedule
      </span>
      <span class="text-muted-foreground">{rooms.length} active rooms</span>
      {#if draft_sessions.length > 0}
        <span class="text-muted-foreground">{draft_sessions.length} draft session(s)</span>
      {/if}
    </div>
  </div>

  {#if !openrouter_configured}
    <Card.Root class="border-amber-500/50 bg-amber-500/10">
      <Card.Content class="pt-6">
        <p class="text-sm text-foreground">
          <strong>Generate schedule</strong> requires <code class="rounded bg-muted px-1">OPENROUTER_API_KEY</code> in your <code class="rounded bg-muted px-1">.env</code>.
          You can still send messages below; configure the key to enable AI replies and schedule generation.
        </p>
      </Card.Content>
    </Card.Root>
  {/if}

  <Card.Root variant="glass">
    <Card.Header>
      <Card.Title class="flex items-center gap-2">
        <MessageSquare class="h-5 w-5" />
        Conversation
      </Card.Title>
      <Card.Description>
        Chat with the assistant to refine your schedule. After you get a reply, <strong>Generate schedule</strong> unlocks so you can get a structured plan. The generated schedule appears here in the chat and in the table below. Then use <strong>Apply schedule</strong> to create sessions.
      </Card.Description>
    </Card.Header>
    <Card.Content class="space-y-4">
      {#if error}
        <p class="text-sm text-destructive rounded-md bg-destructive/10 px-3 py-2">{error}</p>
      {/if}

      <div class="rounded-lg border border-border bg-muted/30 min-h-[200px] max-h-[360px] overflow-y-auto p-4 space-y-3">
        {#if messages.length === 0}
          <p class="text-sm text-muted-foreground">
            e.g. &quot;I want morning slots only, 9–12, next week&quot; or &quot;Spread applicants across 3 rooms on Monday and Tuesday.&quot;
          </p>
        {:else}
          {#each messages as msg}
            <div class="flex flex-col gap-1 {msg.role === 'user' ? 'items-end' : 'items-start'}">
              <span class="text-xs text-muted-foreground">{msg.role === 'user' ? 'You' : 'Assistant'}</span>
              {#if msg.role === 'user'}
                <div class="rounded-lg px-3 py-2 text-sm max-w-[85%] bg-primary text-primary-foreground">
                  {msg.content}
                </div>
              {:else}
                {#if msg.content}
                  <div class="rounded-lg px-3 py-2 text-sm max-w-[85%] bg-muted">
                    {msg.content}
                  </div>
                {/if}
                {#if msg.schedule?.sessions?.length}
                  <div class="rounded-lg border border-border bg-background overflow-hidden max-w-[85%] mt-1">
                    <p class="text-xs font-medium text-muted-foreground px-3 py-2 border-b border-border">Generated schedule</p>
                    <Table.Root>
                      <Table.Header class="bg-muted/50">
                        <Table.Row>
                          <Table.Head class="px-3 py-2 text-xs">Type</Table.Head>
                          <Table.Head class="px-3 py-2 text-xs">Room</Table.Head>
                          <Table.Head class="px-3 py-2 text-xs">Date</Table.Head>
                          <Table.Head class="px-3 py-2 text-xs">Time</Table.Head>
                          <Table.Head class="px-3 py-2 text-xs">#</Table.Head>
                        </Table.Row>
                      </Table.Header>
                      <Table.Body>
                        {#each getScheduleRows(msg.schedule) as row}
                          <Table.Row>
                            <Table.Cell class="px-3 py-2 text-xs">{row.type}</Table.Cell>
                            <Table.Cell class="px-3 py-2 text-xs">{row.room}</Table.Cell>
                            <Table.Cell class="px-3 py-2 text-xs">{row.date}</Table.Cell>
                            <Table.Cell class="px-3 py-2 text-xs">{row.time}</Table.Cell>
                            <Table.Cell class="px-3 py-2 text-xs">{row.applicant_count}</Table.Cell>
                          </Table.Row>
                        {/each}
                      </Table.Body>
                    </Table.Root>
                  </div>
                {/if}
              {/if}
            </div>
          {/each}
        {/if}
      </div>

      <div class="flex flex-col gap-2 md:flex-row md:flex-wrap">
        <div class="flex gap-2 flex-1 min-w-0 w-full">
          <textarea
            bind:value={input}
            onkeydown={handleKeydown}
            placeholder="Type your message..."
            rows="2"
            class="flex-1 min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[44px] resize-none"
            maxlength="4000"
            disabled={loading}
          ></textarea>
          <Button type="button" onclick={() => send(false)} disabled={loading || !input.trim()} class="min-h-[44px] shrink-0" title="Send message">
            {#if loading}
              Sending…
            {:else}
              <Send class="h-4 w-4" />
            {/if}
          </Button>
        </div>
        <Button
          type="button"
          variant="secondary"
          onclick={() => send(true)}
          disabled={!canGenerate || loading || generating}
          class="min-h-[44px] w-full md:w-auto shrink-0"
          title={generateButtonTitle}
        >
          {#if generating}
            Generating…
          {:else}
            <Sparkles class="h-4 w-4 mr-2 inline" />
            Generate schedule
          {/if}
        </Button>
      </div>
    </Card.Content>
  </Card.Root>

  {#if structuredSchedule?.sessions?.length > 0}
    <Card.Root variant="glass">
      <Card.Header>
        <Card.Title>Preview</Card.Title>
        <Card.Description>
          Review the generated schedule. Click Apply to create exam sessions and assign applicants.
        </Card.Description>
      </Card.Header>
      <Card.Content class="space-y-4">
        {#if applyError}
          <p class="text-sm text-destructive rounded-md bg-destructive/10 px-3 py-2">{applyError}</p>
        {/if}
        <div class="rounded-lg border border-border overflow-hidden">
          <Table.Root>
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="px-4 py-3">Type</Table.Head>
                <Table.Head class="px-4 py-3">Room</Table.Head>
                <Table.Head class="px-4 py-3">Date</Table.Head>
                <Table.Head class="px-4 py-3">Time</Table.Head>
                <Table.Head class="px-4 py-3">Applicants</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each previewRows as row}
                <Table.Row>
                  <Table.Cell class="px-4 py-3">{row.type}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{row.room}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{row.date}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{row.time}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{row.applicant_count}</Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>
        <Button onclick={applySchedule} disabled={applying} class="min-h-[44px]">
          {#if applying}
            Applying…
          {:else}
            <CheckCircle2 class="h-4 w-4 mr-2" />
            Apply schedule
          {/if}
        </Button>
      </Card.Content>
    </Card.Root>
  {/if}
</div>
