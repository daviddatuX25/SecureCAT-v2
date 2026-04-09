<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { useForm, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import Switch from '@/Components/ui/switch/switch.svelte';
  import { Bot } from 'lucide-svelte';

  let { ai_exam_companion_enabled = false } = $props();

  const form = useForm({
    ai_exam_companion_enabled,
  });

  const page = usePage();
  const flash = $derived($page.props.flash ?? {});
  const breadcrumbs = [{ label: 'Settings' }];
  let saving = $state(false);

  $effect(() => {
    form.update((f) => ({
      ...f,
      ai_exam_companion_enabled,
    }));
  });

  function submitSettings(e) {
    e.preventDefault();
    saving = true;
    $form.transform((data) => ({
      ai_exam_companion_enabled: !!data.ai_exam_companion_enabled,
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

          </form>
  </div>
</AuthenticatedLayout>
