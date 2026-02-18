<script>
  import GuestLayout from '@/Layouts/GuestLayout.svelte';
  import { useForm, usePage } from '@inertiajs/svelte';
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

  function handleSubmit(e) {
    e.preventDefault();
    $form.post('/login');
  }
</script>

<GuestLayout>
  <Card.Root class="shadow-lg">
    <Card.Header>
      <Card.Title class="text-xl">Staff Login</Card.Title>
      <Card.Description>Sign in with your staff credentials</Card.Description>
    </Card.Header>
    <Card.Content>
      <form onsubmit={handleSubmit} class="space-y-4">
        <div class="space-y-2">
          <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            Email
          </label>
          <Input
            id="email"
            type="email"
            bind:value={$form.email}
            placeholder="staff@example.com"
            autocomplete="email"
            required
            aria-invalid={!!errors?.email}
            aria-describedby={errors?.email ? 'email-error' : undefined}
          />
          {#if errors?.email}
            <p id="email-error" class="text-sm text-destructive">
              {typeof errors.email === 'string' ? errors.email : errors.email[0]}
            </p>
          {/if}
        </div>

        <div class="space-y-2">
          <label for="password" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            Password
          </label>
          <Input
            id="password"
            type="password"
            bind:value={$form.password}
            placeholder="••••••••"
            autocomplete="current-password"
            required
            aria-invalid={!!errors?.password}
            aria-describedby={errors?.password ? 'password-error' : undefined}
          />
          {#if errors?.password}
            <p id="password-error" class="text-sm text-destructive">
              {typeof errors.password === 'string' ? errors.password : errors.password[0]}
            </p>
          {/if}
        </div>

        <div class="flex items-center gap-2">
          <input
            id="remember"
            type="checkbox"
            checked={$form.remember}
            onchange={(e) => form.set('remember', e.currentTarget.checked)}
            class="h-4 w-4 rounded border-input accent-primary"
          />
          <label for="remember" class="text-sm font-medium leading-none cursor-pointer">
            Remember me
          </label>
        </div>

        <Button
          type="submit"
          class="w-full min-h-[44px]"
          disabled={$form.processing}
        >
          {$form.processing ? 'Signing in...' : 'Sign in'}
        </Button>
      </form>
    </Card.Content>
  </Card.Root>
</GuestLayout>
