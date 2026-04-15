<script>
  import PortalGuestLayout from '@/Layouts/PortalGuestLayout.svelte';
  import { useForm, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import * as Card from '@/Components/ui/card';
  import { Link } from '@inertiajs/svelte';

  const form = useForm({ email: '', password: '' });
  const page = usePage();
  const errors = $derived({ ...($page.props.errors ?? {}), ...($form.errors ?? {}) });


  function handleSubmit(e) {
    e.preventDefault();
    $form.post('/portal/login');
  }
</script>

<PortalGuestLayout>
  <Card.Root class="shadow-lg">
    <Card.Header>
      <Card.Title class="text-xl">Applicant Portal</Card.Title>
      <Card.Description>Sign in with the email you used when you applied</Card.Description>
    </Card.Header>
    <Card.Content>
      <form onsubmit={handleSubmit} class="space-y-4">
        <div class="space-y-2">
          <label for="email" class="text-sm font-medium leading-none">Email</label>
          <Input id="email" type="email" bind:value={$form.email} placeholder="you@example.com" autocomplete="email" required aria-invalid={!!errors?.email} />
          {#if errors?.email}
            <p class="text-sm text-destructive">{typeof errors.email === 'string' ? errors.email : errors.email[0]}</p>
          {/if}
        </div>
        <div class="space-y-2">
          <label for="password" class="text-sm font-medium leading-none">Password</label>
          <Input id="password" type="password" bind:value={$form.password} placeholder="••••••••" autocomplete="current-password" required aria-invalid={!!errors?.password} />
          {#if errors?.password}
            <p class="text-sm text-destructive">{typeof errors.password === 'string' ? errors.password : errors.password[0]}</p>
          {/if}
        </div>
        <Button type="submit" class="w-full min-h-[44px]" disabled={$form.processing}>
          {$form.processing ? 'Signing in...' : 'Sign in'}
        </Button>
      </form>
      <p class="mt-4 text-center text-sm text-muted-foreground">
        <Link href="/portal/forgot-password" class="text-primary hover:underline">Forgot password?</Link>
      </p>
    </Card.Content>
  </Card.Root>
</PortalGuestLayout>
