<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router } from '@inertiajs/svelte';
  import { usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { ArrowLeft, Check, X, FileDown, Mail } from 'lucide-svelte';

  let { application, courses = [] } = $props();

  const page = usePage();
  const flash = $derived($page.props.flash ?? {});
  const success = $derived(flash.success ?? null);
  const error = $derived(flash.error ?? null);

  const fullName = $derived(
    [application?.first_name, application?.middle_name, application?.last_name, application?.suffix]
      .filter(Boolean)
      .join(' ')
  );

  const isPending = $derived(application?.status === 'pending');
  const isAccepted = $derived(application?.status === 'accepted');

  let rejectModalOpen = $state(false);
  let rejectReason = $state('');

  function statusVariant(status) {
    if (status === 'pending') return 'warning';
    if (status === 'accepted') return 'success';
    return 'danger';
  }

  function handleAccept() {
    if (!application?.id || !isPending) return;
    router.put(`/applications/${application.id}/accept`);
  }

  function openRejectModal() {
    rejectReason = '';
    rejectModalOpen = true;
  }

  function cancelReject() {
    rejectModalOpen = false;
    rejectReason = '';
  }

  function handleReject() {
    if (!application?.id || !rejectReason.trim()) return;
    router.put(`/applications/${application.id}/reject`, { reason: rejectReason.trim() }, {
      onSuccess: () => (rejectModalOpen = false),
    });
  }

  const admissionSlipUrl = $derived(application?.id ? `/applications/${application.id}/admission-slip` : null);

  function handleResendSetupEmail() {
    if (!application?.id || !isAccepted) return;
    router.post(`/applications/${application.id}/resend-setup-email`);
  }
</script>

<svelte:head>
  <title>{application?.reference_number ?? 'Application'} - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6">
    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">
        {success}
      </div>
    {/if}
    {#if error}
      <div class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">
        {error}
      </div>
    {/if}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <Link href="/applications">
          <Button variant="ghost" size="icon" aria-label="Back to list">
            <ArrowLeft class="h-4 w-4" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold font-mono">{application?.reference_number}</h1>
          <p class="mt-1 text-sm text-muted-foreground">{fullName}</p>
        </div>
      </div>
      <div class="flex gap-2">
        {#if isPending}
          <Button variant="outline" class="min-h-[44px]" onclick={handleAccept}>
            <Check class="mr-2 h-4 w-4" />
            Accept
          </Button>
          <Button variant="outline" class="min-h-[44px]" onclick={openRejectModal}>
            <X class="mr-2 h-4 w-4" />
            Reject
          </Button>
        {/if}
        {#if isAccepted}
          <Button variant="outline" class="min-h-[44px]" onclick={handleResendSetupEmail}>
            <Mail class="mr-2 h-4 w-4" />
            Resend setup email
          </Button>
          {#if admissionSlipUrl}
            <a href={admissionSlipUrl} target="_blank" rel="noopener noreferrer">
              <Button variant="outline" class="min-h-[44px]">
                <FileDown class="mr-2 h-4 w-4" />
                Download slip
              </Button>
            </a>
          {/if}
        {/if}
      </div>
    </div>

    {#if application}
      <div class="grid gap-6 md:grid-cols-2">
        <section class="rounded-lg border border-border p-4">
          <h2 class="text-lg font-semibold mb-4">Personal information</h2>
          <dl class="grid gap-3 text-sm">
            <div>
              <dt class="text-muted-foreground">Full name</dt>
              <dd class="font-medium">{fullName}</dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Birthdate</dt>
              <dd>{application.birthdate} (Age {application.age})</dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Sex</dt>
              <dd class="capitalize">{application.sex}</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-lg border border-border p-4">
          <h2 class="text-lg font-semibold mb-4">Contact</h2>
          <dl class="grid gap-3 text-sm">
            <div>
              <dt class="text-muted-foreground">Email</dt>
              <dd><a href="mailto:{application.email}" class="text-primary hover:underline">{application.email}</a></dd>
            </div>
            {#if application.phone}
              <div>
                <dt class="text-muted-foreground">Phone</dt>
                <dd>{application.phone}</dd>
              </div>
            {/if}
            {#if application.address_line || application.city}
              <div>
                <dt class="text-muted-foreground">Address</dt>
                <dd>
                  {[application.address_line, application.city, application.province, application.zip_code].filter(Boolean).join(', ') || '—'}
                </dd>
              </div>
            {/if}
          </dl>
        </section>

        <section class="rounded-lg border border-border p-4 md:col-span-2">
          <h2 class="text-lg font-semibold mb-4">Course preferences</h2>
          <ol class="list-decimal list-inside space-y-2 text-sm">
            <li>{application.course_preference_1_label ?? '—'}</li>
            <li>{application.course_preference_2_label ?? '—'}</li>
            <li>{application.course_preference_3_label ?? '—'}</li>
          </ol>
        </section>

        <section class="rounded-lg border border-border p-4 md:col-span-2">
          <h2 class="text-lg font-semibold mb-4">Status</h2>
          <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div>
              <dt class="text-muted-foreground">Status</dt>
              <dd>
                <Badge variant={statusVariant(application.status)} class="capitalize">{application.status}</Badge>
              </dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Submitted at</dt>
              <dd>{application.submitted_at ? new Date(application.submitted_at).toLocaleString() : '—'}</dd>
            </div>
            {#if application.appointment_label}
              <div>
                <dt class="text-muted-foreground">Appointment</dt>
                <dd>{application.appointment_label}</dd>
              </div>
            {/if}
            {#if application.processed_at}
              <div>
                <dt class="text-muted-foreground">Processed at</dt>
                <dd>{new Date(application.processed_at).toLocaleString()}</dd>
              </div>
            {/if}
            {#if application.rejection_reason}
              <div class="sm:col-span-2">
                <dt class="text-muted-foreground">Rejection reason</dt>
                <dd class="mt-1 rounded bg-muted/50 p-2 text-muted-foreground">{application.rejection_reason}</dd>
              </div>
            {/if}
          </dl>
        </section>
      </div>
    {:else}
      <p class="text-muted-foreground">Application not found.</p>
    {/if}
  </div>

  {#if rejectModalOpen}
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="reject-title"
    >
      <div class="rounded-lg bg-card p-6 shadow-lg max-w-md w-full">
        <h2 id="reject-title" class="text-lg font-semibold">Reject application?</h2>
        <p class="mt-2 text-sm text-muted-foreground">
          Please provide a reason for rejection. This will be visible to the applicant.
        </p>
        <div class="mt-4">
          <label for="reject-reason" class="block text-sm font-medium mb-2">Reason (required)</label>
          <textarea
            id="reject-reason"
            bind:value={rejectReason}
            rows="4"
            maxlength="500"
            placeholder="Enter rejection reason..."
            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none"
          ></textarea>
        </div>
        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" onclick={cancelReject}>Cancel</Button>
          <Button variant="destructive" onclick={handleReject} disabled={!rejectReason.trim()}>
            Reject
          </Button>
        </div>
      </div>
    </div>
  {/if}
</AuthenticatedLayout>
