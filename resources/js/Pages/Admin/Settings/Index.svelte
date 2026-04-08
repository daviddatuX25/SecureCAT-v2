<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { useForm, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import Switch from '@/Components/ui/switch/switch.svelte';
  import { Bot, MessageSquare } from 'lucide-svelte';

  let { ai_exam_companion_enabled = false, ai_companion_persona = '', consultation_enabled = true } = $props();

  const form = useForm({
    ai_exam_companion_enabled,
    ai_companion_persona: ai_companion_persona ?? '',
    consultation_enabled,
  });

  const page = usePage();
  const flash = $derived($page.props.flash ?? {});
  const breadcrumbs = [{ label: 'Settings' }];
  let saving = $state(false);

  $effect(() => {
    form.update((f) => ({
      ...f,
      ai_exam_companion_enabled,
      ai_companion_persona: ai_companion_persona ?? '',
      consultation_enabled,
    }));
  });

  function submitSettings(e) {
    e.preventDefault();
    saving = true;
    $form.transform((data) => ({
      ai_exam_companion_enabled: !!data.ai_exam_companion_enabled,
      ai_companion_persona: String(data.ai_companion_persona ?? ''),
      consultation_enabled: !!data.consultation_enabled,
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
            <MessageSquare class="h-5 w-5" />
            Consultation
          </CardTitle>
          <CardDescription>
            When enabled, counselors can access the consultation dashboard in the sidebar. When disabled, the Consultation link is hidden.
          </CardDescription>
        </CardHeader>
        <CardContent class="flex items-center gap-4">
          <Switch
            checked={$form.consultation_enabled}
            onCheckedChange={(checked) => form.update((f) => ({ ...f, consultation_enabled: checked }))}
            aria-label="Enable consultation"
          />
          <span class="text-sm font-medium">
            {$form.consultation_enabled ? 'Enabled' : 'Disabled'}
          </span>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="text-base">AI companion persona</CardTitle>
          <CardDescription>
            System instructions for the AI (e.g. tone, guardrails). Used when applicants chat with the advisor. If empty, a safe default is used. Plain text only; no HTML.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-2">
          <textarea
            id="ai_companion_persona"
            bind:value={$form.ai_companion_persona}
            placeholder="e.g. You are an encouraging academic counselor. Base your advice only on the data provided. Do not invent statistics."
            rows="6"
            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm min-h-[120px]"
            maxlength="5000"
          />
          <p class="text-xs text-muted-foreground">Max 5000 characters. Stored as plain text.</p>
          {#if $form.errors?.ai_companion_persona}
            <p class="text-sm text-destructive">{$form.errors.ai_companion_persona}</p>
          {/if}
        </CardContent>
      </Card>

      <div>
        <Button type="submit" disabled={saving} class="min-h-[44px]">
          {saving ? 'Saving…' : 'Save settings'}
        </Button>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
