<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { useForm, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import Switch from '@/Components/ui/switch/switch.svelte';
  import { Bot, Bell, Share2 } from 'lucide-svelte';

  let { ai_exam_companion_enabled = false, notify_on_publish = false, release_mode = 'online' } = $props();

  const form = useForm({
    ai_exam_companion_enabled,
    notify_on_publish,
    release_mode,
  });

  const page = usePage();
  const flash = $derived($page.props.flash ?? {});
  const breadcrumbs = [{ label: 'Settings' }];
  let saving = $state(false);

  let releaseOnline = $state(release_mode === 'online' || release_mode === 'both');
  let releasef2f    = $state(release_mode === 'f2f'    || release_mode === 'both');

  function computeReleaseMode(online, f2f) {
    if (online && f2f) return 'both';
    if (online) return 'online';
    if (f2f) return 'f2f';
    return null;
  }

  function handleReleaseOnlineChange(checked) {
    releaseOnline = checked;
    form.update((f) => ({ ...f, release_mode: computeReleaseMode(checked, releasef2f) }));
  }

  function handleReleaseF2fChange(checked) {
    releasef2f = checked;
    form.update((f) => ({ ...f, release_mode: computeReleaseMode(releaseOnline, checked) }));
  }

  const releaseModeInvalid = $derived(!releaseOnline && !releasef2f);

  $effect(() => {
    form.update((f) => ({
      ...f,
      ai_exam_companion_enabled,
      notify_on_publish,
      release_mode,
    }));
    releaseOnline = release_mode === 'online' || release_mode === 'both';
    releasef2f    = release_mode === 'f2f'    || release_mode === 'both';
  });

  function submitSettings(e) {
    e.preventDefault();
    saving = true;
    $form.transform((data) => ({
      ai_exam_companion_enabled: !!data.ai_exam_companion_enabled,
      notify_on_publish: !!data.notify_on_publish,
    }));
    $form.put('/admin/settings', {
      preserveScroll: true,
      onFinish: () => {
        saving = false;
      },
    });
  }
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div>
      <p class="mt-1 text-sm text-muted-foreground">System-wide feature toggles and configuration.</p>
    </div>

    {#if flash.success}
      <p class="text-sm text-green-600 dark:text-green-400">{flash.success}</p>
    {/if}

    <form onsubmit={submitSettings} class="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Bot class="h-5 w-5" />
            AI exam companion
          </CardTitle>
          <CardDescription>
            When enabled, applicants whose consultation has been released can use the chat advisor. Advice is grounded in their scores and institutional knowledge you upload.
          </CardDescription>
        </CardHeader>
        <CardContent class="flex items-center gap-4">
          <Switch
            checked={$form.ai_exam_companion_enabled}
            onCheckedChange={(checked) => form.update((f) => ({ ...f, ai_exam_companion_enabled: checked }))}
            aria-label="Enable AI exam companion"
          />
          <span class="text-sm font-medium">
            {$form.ai_exam_companion_enabled ? 'Enabled' : 'Disabled'}
          </span>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Bell class="h-5 w-5" />
            Exam schedule notifications
          </CardTitle>
          <CardDescription>
            When enabled, applicants receive an email when their exam session is published.
            Requires a queue worker in production (or QUEUE_CONNECTION=sync in .env for local dev).
          </CardDescription>
        </CardHeader>
        <CardContent class="flex items-center gap-4">
          <Switch
            checked={$form.notify_on_publish}
            onCheckedChange={(checked) => form.update((f) => ({ ...f, notify_on_publish: checked }))}
            aria-label="Enable exam schedule notifications"
          />
          <span class="text-sm font-medium">
            {$form.notify_on_publish ? 'Enabled' : 'Disabled'}
          </span>
        </CardContent>
      </Card>

          </form>
  </div>
</AuthenticatedLayout>
