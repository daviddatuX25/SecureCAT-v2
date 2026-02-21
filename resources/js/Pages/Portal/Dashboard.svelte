<script>
  import { router } from '@inertiajs/svelte';
  import PortalLayout from '@/Layouts/PortalLayout.svelte';
  import * as Card from '@/Components/ui/card';
  import { Button } from '@/Components/ui/button';

  let {
    applicant = {},
    status_tracker = [],
    exam_schedule = null,
    score_release = null,
    consultation = { status: 'pending', summary: null },
    notifications = [],
  } = $props();

  function markRead(id) {
    router.post(`/portal/notifications/${id}/read`, {}, { preserveScroll: true, onSuccess: () => router.reload() });
  }
</script>

<PortalLayout>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Welcome, {applicant.name ?? 'Applicant'}</h1>
      <p class="text-muted-foreground">Reference: {applicant.reference_number ?? '—'}</p>
    </div>

    <Card.Root>
      <Card.Header>
        <Card.Title>Application status</Card.Title>
        <Card.Description>Your admission progress</Card.Description>
      </Card.Header>
      <Card.Content>
        {#if status_tracker.length > 0}
          <ul class="space-y-2">
            {#each status_tracker as stage}
              <li class="flex items-center gap-2">
                {#if stage.completed}
                  <span class="text-green-600 dark:text-green-400">✓</span>
                {:else}
                  <span class="text-muted-foreground">○</span>
                {/if}
                {stage.stage}
                {#if stage.timestamp}
                  <span class="text-sm text-muted-foreground">— {stage.timestamp}</span>
                {/if}
              </li>
            {/each}
          </ul>
        {:else}
          <p class="text-muted-foreground">Your status will appear here once your application is processed.</p>
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

    {#if notifications.length > 0}
      <Card.Root>
        <Card.Header>
          <Card.Title>Notifications</Card.Title>
          <Card.Description>Your recent notifications</Card.Description>
        </Card.Header>
        <Card.Content>
          <ul class="space-y-3">
            {#each notifications as notif}
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
