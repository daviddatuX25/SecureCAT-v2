<script>
  import GuestLayout from '@/Layouts/GuestLayout.svelte';
  import { useForm, usePage, Link } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import * as Card from '@/Components/ui/card';

  const form = useForm({
    email: '',
    password: '',
    remember: false,
  });

  const page = usePage();
  const errors = $derived({ ...($page.props.errors ?? {}), ...($form.errors ?? {}) });
  const flash = $derived($page.props.flash ?? {});

  function handleSubmit(e) {
    e.preventDefault();
    $form.post('/login');
  }
</script>

<GuestLayout>
  <Card.Root class="shadow-lg">
    <Card.Header>
      <Card.Title class="text-xl">Staff login</Card.Title>
      <Card.Description>
        Sign in to manage applications, exam sessions, grading, and consultation.
      </Card.Description>
    </Card.Header>
    <Card.Content class="space-y-4">
      {#if flash.success}
        <p class="text-sm text-green-600 dark:text-green-400">{flash.success}</p>
      {/if}
      {#if flash.error}
        <p class="text-sm text-destructive">{flash.error}</p>
      {/if}

      <form onsubmit={handleSubmit} class="space-y-4">
        <div class="space-y-2">
          <label for="email" class="text-sm font-medium leading-none">Email</label>
          <Input
            id="email"
            type="email"
            bind:value={$form.email}
            placeholder="you@example.com"
            autocomplete="email"
            required
            aria-invalid={!!errors?.email}
            class="min-h-[44px]"
          />
          {#if errors?.email}
            <p class="text-sm text-destructive">
              {typeof errors.email === 'string' ? errors.email : errors.email[0]}
            </p>
          {/if}
        </div>

        <div class="space-y-2">
          <label for="password" class="text-sm font-medium leading-none">Password</label>
          <Input
            id="password"
            type="password"
            bind:value={$form.password}
            placeholder="••••••••"
            autocomplete="current-password"
            required
            aria-invalid={!!errors?.password}
            class="min-h-[44px]"
          />
          {#if errors?.password}
            <p class="text-sm text-destructive">
              {typeof errors.password === 'string' ? errors.password : errors.password[0]}
            </p>
          {/if}
        </div>

        <div class="flex items-center justify-between gap-2">
          <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
            <input
              type="checkbox"
              checked={$form.remember}
              onchange={(e) => form.setData('remember', e.currentTarget.checked)}
            />
            <span>Remember me on this device</span>
          </label>
        </div>

        <Button type="submit" class="w-full min-h-[44px]" disabled={$form.processing}>
          {$form.processing ? 'Signing in…' : 'Sign in'}
        </Button>
      </form>

      <p class="mt-2 text-xs text-muted-foreground">
        For demo data, use <span class="font-mono">admin@example.com</span> with the password you configured.
      </p>
    </Card.Content>
  </Card.Root>
</GuestLayout>

