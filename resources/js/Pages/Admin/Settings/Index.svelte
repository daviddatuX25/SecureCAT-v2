<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { useForm } from '@inertiajs/svelte';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import Switch from '@/Components/ui/switch/switch.svelte';
  import { Bot, Bell, Share2, FileCheck, Calculator } from 'lucide-svelte';
  import { success as showSuccess, error as showError } from '@/lib/toast';

  let { ai_exam_companion_enabled = false, notify_on_publish = false, release_mode = 'online', allow_direct_assessment = true, enable_normalized_scores = false } = $props();

  const form = useForm({
    ai_exam_companion_enabled,
    notify_on_publish,
    release_mode,
    allow_direct_assessment,
    enable_normalized_scores,
  });

  const breadcrumbs = [{ label: 'Settings' }];

  let saveTimeout = $state(null);

  function autoSave() {
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
      $form.put('/admin/settings', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          // Flash message from backend handles success toast
        },
        onError: (errors) => {
          const first = Object.values(errors)[0];
          showError(first ?? 'Failed to save settings.');
        },
      });
    }, 300);
  }

  function toggleField(field, value) {
    form.update((f) => ({ ...f, [field]: value }));
    autoSave();
  }
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div class="flex items-center justify-between">
      <p class="mt-1 text-sm text-muted-foreground">System-wide feature toggles and configuration.</p>
      {#if $form.processing}
        <span class="text-sm text-muted-foreground animate-pulse">Saving...</span>
      {/if}
    </div>

    <div class="space-y-6">
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
            onCheckedChange={(checked) => toggleField('ai_exam_companion_enabled', checked)}
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
            onCheckedChange={(checked) => toggleField('notify_on_publish', checked)}
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
          >
            <button
              type="button"
              role="radio"
              aria-checked={$form.release_mode === 'online'}
              class="px-4 py-2 rounded-md text-sm font-medium transition-colors {$form.release_mode === 'online' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50'}"
              onclick={() => toggleField('release_mode', 'online')}
            >
              Online
            </button>
            <button
              type="button"
              role="radio"
              aria-checked={$form.release_mode === 'f2f'}
              class="px-4 py-2 rounded-md text-sm font-medium transition-colors {$form.release_mode === 'f2f' ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-muted/50'}"
              onclick={() => toggleField('release_mode', 'f2f')}
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
            onCheckedChange={(checked) => toggleField('allow_direct_assessment', checked)}
            aria-label="Enable direct assessment"
          />
          <span class="text-sm font-medium">
            {$form.allow_direct_assessment ? 'Enabled' : 'Disabled'}
          </span>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Calculator class="h-5 w-5" />
            Normalized Score Computation
          </CardTitle>
          <CardDescription>
            When enabled, bulk import expects raw scores and auto-computes normalized scores using aptitude area formulas.
          </CardDescription>
        </CardHeader>
        <CardContent class="flex items-center gap-4">
          <Switch
            checked={$form.enable_normalized_scores}
            onCheckedChange={(checked) => toggleField('enable_normalized_scores', checked)}
            aria-label="Enable normalized score computation"
          />
          <span class="text-sm font-medium">
            {$form.enable_normalized_scores ? 'Enabled' : 'Disabled'}
          </span>
        </CardContent>
      </Card>
    </div>
  </div>
</AuthenticatedLayout>