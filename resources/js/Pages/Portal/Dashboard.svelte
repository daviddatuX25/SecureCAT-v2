<script>
  import { Link, router } from '@inertiajs/svelte';
  import PortalLayout from '@/Layouts/PortalLayout.svelte';
  import * as Card from '@/Components/ui/card';
  import { Button } from '@/Components/ui/button';
  import ApplicationStatusTimeline from '@/Components/ApplicationStatusTimeline.svelte';

  let {
    applicant = {},
    status_tracker,
    exam_schedule = null,
    score_release = null,
    consultation = { status: 'pending', summary: null },
    ai_companion_enabled = false,
    notifications,
  } = $props();
  const safeStatusTracker = $derived(Array.isArray(status_tracker) ? status_tracker : []);
  const safeNotifications = $derived(Array.isArray(notifications) ? notifications : []);

  function markRead(id) {
    router.post(`/portal/notifications/${id}/read`, {}, { preserveScroll: true, onSuccess: () => router.reload() });
  }
</script>

<PortalLayout>
  <div class="space-y-6">
    <div class="rounded-xl bg-primary/10 border border-primary/20 px-4 py-4 sm:px-6">
      <h1 class="text-2xl font-semibold tracking-tight text-foreground">
        Welcome, {applicant.name ?? 'Applicant'}
      </h1>
      <p class="mt-1 text-sm text-muted-foreground font-mono">
        Reference: {applicant.reference_number ?? '—'}
      </p>
    </div>

    <Card.Root class="overflow-hidden">
      <Card.Header class="pb-2">
        <Card.Title class="text-lg">Application status</Card.Title>
        <Card.Description>Your admission progress</Card.Description>
      </Card.Header>
      <Card.Content class="pt-0">
        {#if safeStatusTracker.length > 0}
          <ApplicationStatusTimeline stages={safeStatusTracker} />
        {:else}
          <p class="text-muted-foreground py-4">
            Your progress will appear here once your application has been submitted and processed.
          </p>
        {/if}
      </Card.Content>
    </Card.Root>

    {#if exam_schedule?.assigned}
      <Card.Root>
        <Card.Header>
          <Card.Title>Exam schedule</Card.Title>
          <Card.Description>Your assigned exam session</Card.Description>
        </Card.Header>
        <Card.Content>
          <p>{exam_schedule.room}, {exam_schedule.building}</p>
          <p class="text-muted-foreground">{exam_schedule.date} at {exam_schedule.time}</p>
        </Card.Content>
      </Card.Root>
    {/if}

    {#if score_release?.date_set}
      <Card.Root>
        <Card.Header>
          <Card.Title>Score release</Card.Title>
          <Card.Description>Results will be available on {score_release.release_date}</Card.Description>
        </Card.Header>
      </Card.Root>
    {/if}

    {#if consultation.status === 'released' && consultation.summary}
      <Card.Root>
        <Card.Header>
          <Card.Title>Consultation</Card.Title>
          <Card.Description>Your consultation summary is available</Card.Description>
        </Card.Header>
        <Card.Content>
          <!-- Summary content when released -->
        </Card.Content>
      </Card.Root>
    {/if}

    {#if ai_companion_enabled && consultation.status === 'released'}
      <Card.Root>
        <Card.Header>
          <Card.Title>Chat with advisor</Card.Title>
          <Card.Description>Ask questions about your results and course fit. Advice is based on your scores and institutional data.</Card.Description>
        </Card.Header>
        <Card.Content>
          <Link href="/portal/ai-companion">
            <Button class="min-h-[44px]">Open chat</Button>
          </Link>
        </Card.Content>
      </Card.Root>
    {/if}

    {#if safeNotifications.length > 0}
      <Card.Root>
        <Card.Header>
          <Card.Title>Notifications</Card.Title>
          <Card.Description>Your recent notifications</Card.Description>
        </Card.Header>
        <Card.Content>
          <ul class="space-y-3">
            {#each safeNotifications as notif}
              <li class="flex items-start justify-between gap-3 {notif.read ? 'opacity-70' : ''}">
                <span class="text-sm min-w-0 flex-1">{notif.message}</span>
                {#if !notif.read}
                  <Button type="button" variant="outline" size="sm" class="shrink-0" onclick={() => markRead(notif.id)}>
                    Mark read
                  </Button>
                {/if}
              </li>
            {/each}
          </ul>
        </Card.Content>
      </Card.Root>
    {/if}
  </div>
</PortalLayout>
