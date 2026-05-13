<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import Switch from '@/Components/ui/switch/switch.svelte';
  import { Bot, Bell, Share2, FileCheck } from 'lucide-svelte';

  let { ai_exam_companion_enabled = false, notify_on_publish = false, release_mode = 'online', allow_direct_assessment = true } = $props();

  const form = useForm({
    ai_exam_companion_enabled,
    notify_on_publish,
    release_mode,
    allow_direct_assessment,
  });

  const breadcrumbs = [{ label: 'Settings' }];
  let saving = $state(false);

  function handleReleaseModeToggle() {
    const next = form.data.release_mode === 'online' ? 'f2f' : 'online';
    form.update((f) => ({ ...f, release_mode: next }));
  }

  $effect(() => {
    form.update((f) => ({
      ...f,
      ai_exam_companion_enabled,
      notify_on_publish,
      release_mode,
      allow_direct_assessment,
    }));
  });

  function submitSettings(e) {
    e.preventDefault();
    saving = true;
    $form.transform((data) => ({
      ai_exam_companion_enabled: !!data.ai_exam_companion_enabled,
      notify_on_publish: !!data.notify_on_publish,
      release_mode: data.release_mode,
      allow_direct_assessment: !!data.allow_direct_assessment,
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

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Share2 class="h-5 w-5" />
            Result release mode
          </CardTitle>
          <CardDescription>
            Controls how exam results are delivered to applicants. At least one mode must be enabled.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div
            class="inline-flex rounded-lg border border-border p-1 gap-1 cursor-pointer select-none"
            role="radiogroup"
            aria-label="Release mode"
            onclick={handleReleaseModeToggle}
          >
            <button
              type="button"
              class="px-4 py-2 rounded-md text-sm font-medium transition-colors {$form.release_mode === 'online' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50'}"
              aria-pressed={$form.release_mode === 'online'}
            >
              Online
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-md text-sm font-medium transition-colors {$form.release_mode === 'f2f' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50'}"
              aria-pressed={$form.release_mode === 'f2f'}
            >
              F2F
            </button>
          </div>
          <p class="mt-3 text-xs text-muted-foreground">
            {#if $form.release_mode === 'online'}
              Results visible in portal with email delivery
            {:else}
              Results handed in person — portal view disabled for applicants
            {/if}
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <FileCheck class="h-5 w-5" />
            Direct Assessment
          </CardTitle>
          <CardDescription>
            When enabled, staff can create direct assessment sessions to encode scores immediately without scheduling a physical exam session. Useful for walk-in applicants or offline score entry.
          </CardDescription>
        </CardHeader>
        <CardContent class="flex items-center gap-4">
          <Switch
            checked={$form.allow_direct_assessment}
            onCheckedChange={(checked) => form.update((f) => ({ ...f, allow_direct_assessment: checked }))}
            aria-label="Enable direct assessment"
          />
          <span class="text-sm font-medium">
            {$form.allow_direct_assessment ? 'Enabled' : 'Disabled'}
          </span>
        </CardContent>
      </Card>

      <div class="flex justify-end">
        <Button type="submit" disabled={saving} class="min-h-[44px]">
          {saving ? 'Saving...' : 'Save'}
        </Button>
      </div>

          </form>
  </div>
</AuthenticatedLayout>
