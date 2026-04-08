<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, router, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
  import { ArrowLeft, CheckCircle, XCircle, Mail, FileX2 } from 'lucide-svelte';

  let { application, courses = [], within_application_window = false, application_window_label = null } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const error = $derived($page.props.flash?.error ?? null);

  function statusVariant(status) {
    if (status === 'pending') return 'warning';
    if (status === 'accepted') return 'success';
    if (status === 'dismissed') return 'danger';
    if (status === 'incomplete_documents') return 'warning';
    return 'muted';
  }

  function statusLabel(status) {
    const labels = {
      pending: 'Pending',
      accepted: 'Accepted',
      dismissed: 'Dismissed',
      incomplete_documents: 'Incomplete Documents',
    };
    return labels[status] ?? status;
  }

  const fullName = $derived(
    [application?.first_name, application?.middle_name, application?.last_name, application?.suffix]
      .filter(Boolean)
      .join(' ') || '—'
  );

  let dismissReason = $state('');
  let showDismissModal = $state(false);

  function accept() {
    router.put(`/applications/${application.id}/accept`);
  }

  function openDismiss() {
    showDismissModal = true;
  }

  function cancelDismiss() {
    showDismissModal = false;
    dismissReason = '';
  }

  function submitDismiss() {
    router.put(`/applications/${application.id}/dismiss`, { reason: dismissReason.trim() || undefined });
    showDismissModal = false;
    dismissReason = '';
  }

  function setIncompleteDocuments() {
    router.put(`/applications/${application.id}/incomplete-documents`);
  }

  function resendSetupEmail() {
    router.post(`/applications/${application.id}/resend-setup-email`);
  }

  const courseLabel = (id) => {
    const c = courses.find((x) => x.id === id);
    return c ? `${c.code} – ${c.name}` : '—';
  };

  const canAccept = $derived(
    within_application_window &&
    application &&
    ['pending', 'dismissed', 'incomplete_documents'].includes(application.status)
  );
  const canDismiss = $derived(
    within_application_window && application && ['pending', 'incomplete_documents'].includes(application.status)
  );
  const canSetIncomplete = $derived(
    within_application_window && application && ['pending', 'dismissed'].includes(application.status)
  );

  const breadcrumbs = $derived([
    { label: 'Applications', href: '/applications' },
    { label: application?.reference_number ?? 'Application' }
  ]);
</script>

<AuthenticatedLayout breadcrumbs={breadcrumbs}>
  <div class="space-y-6 min-w-0">
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

    <!-- Status + action bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border bg-card px-4 py-3">
      <div class="flex flex-wrap items-center gap-3">
        <span class="text-sm text-muted-foreground">Status:</span>
        <Badge variant={statusVariant(application?.status)}>{statusLabel(application?.status)}</Badge>
        {#if application_window_label}
          <span class="text-xs text-muted-foreground">{application_window_label}</span>
        {/if}
      </div>
      <div class="flex flex-wrap gap-2">
        {#if canAccept}
          <Button onclick={accept} size="sm" class="min-h-[40px]">
            <CheckCircle class="mr-1.5 h-4 w-4" />
            Accept
          </Button>
        {/if}
        {#if canDismiss}
          <Button
            onclick={openDismiss}
            variant="outline"
            size="sm"
            class="min-h-[40px] border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300"
          >
            <XCircle class="mr-1.5 h-4 w-4" />
            Dismiss
          </Button>
        {/if}
        {#if canSetIncomplete}
          <Button onclick={setIncompleteDocuments} variant="outline" size="sm" class="min-h-[40px]">
            <FileX2 class="mr-1.5 h-4 w-4" />
            Incomplete Docs
          </Button>
        {/if}
        {#if application?.status === 'accepted'}
          <Button onclick={resendSetupEmail} variant="outline" size="sm" class="min-h-[40px]">
            <Mail class="mr-1.5 h-4 w-4" />
            Resend Setup Email
          </Button>
        {/if}
      </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>Personal details</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 text-sm">
          <p><span class="text-muted-foreground">Name:</span> {fullName}</p>
          <p><span class="text-muted-foreground">Birthdate:</span> {application?.birthdate ?? '—'}</p>
          <p><span class="text-muted-foreground">Sex:</span> {application?.sex ?? '—'}</p>
          <p><span class="text-muted-foreground">Email:</span> {application?.email ?? '—'}</p>
          <p><span class="text-muted-foreground">Phone:</span> {application?.phone ?? '—'}</p>
          <p><span class="text-muted-foreground">Address:</span> {[application?.address_line, application?.city, application?.province, application?.zip_code].filter(Boolean).join(', ') || '—'}</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Course preferences</CardTitle>
        </CardHeader>
        <CardContent class="space-y-2 text-sm">
          <p><span class="text-muted-foreground">1st:</span> {courseLabel(application?.course_preference_1)}</p>
          {#if application?.course_preference_2 != null}
            <p><span class="text-muted-foreground">2nd:</span> {courseLabel(application.course_preference_2)}</p>
          {/if}
          {#if application?.course_preference_3 != null}
            <p><span class="text-muted-foreground">3rd:</span> {courseLabel(application.course_preference_3)}</p>
          {/if}
          {#if application?.appointment_label}
            <p class="pt-2"><span class="text-muted-foreground">Appointment:</span> {application.appointment_label}</p>
          {/if}
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>Timeline</CardTitle>
      </CardHeader>
      <CardContent class="space-y-2 text-sm">
        <p><span class="text-muted-foreground">Submitted:</span> {application?.submitted_at ? new Date(application.submitted_at).toLocaleString() : '—'}</p>
        {#if application?.processed_at}
          <p><span class="text-muted-foreground">Processed:</span> {new Date(application.processed_at).toLocaleString()}</p>
        {/if}
        {#if application?.status === 'dismissed' && application?.rejection_reason}
          <p><span class="text-muted-foreground">Dismissal reason:</span> {application.rejection_reason}</p>
        {/if}
      </CardContent>
    </Card>
  </div>

  {#if showDismissModal}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="dismiss-title">
      <div class="w-full max-w-md rounded-lg border border-border bg-card p-6 shadow-lg">
        <h2 id="dismiss-title" class="text-lg font-semibold">Dismiss application</h2>
        <p class="mt-2 text-sm text-muted-foreground">Optionally provide a reason for dismissal.</p>
        <textarea
          bind:value={dismissReason}
          placeholder="Reason for dismissal (optional)"
          rows="3"
          class="mt-3 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
        ></textarea>
        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" onclick={cancelDismiss}>Cancel</Button>
          <Button
            variant="outline"
            class="border-red-300 bg-red-50/80 text-red-800 hover:bg-red-100/80 hover:border-red-400 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200 dark:hover:bg-red-900/40"
            onclick={submitDismiss}
          >
            Dismiss
          </Button>
        </div>
      </div>
    </div>
  {/if}
</AuthenticatedLayout>
