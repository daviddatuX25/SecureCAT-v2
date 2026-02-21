<script>
  import PortalGuestLayout from '@/Layouts/PortalGuestLayout.svelte';
  import { useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import * as Card from '@/Components/ui/card';

  let { token, email } = $props();

  const form = useForm({ password: '', password_confirmation: '' });

  function handleSubmit(e) {
    e.preventDefault();
    $form.post(`/portal/setup/${token}`);
  }
</script>

<PortalGuestLayout>
  <Card.Root class="shadow-lg">
    <Card.Header>
      <Card.Title class="text-xl">Set up your password</Card.Title>
      <Card.Description>Create a password for {email}. Use at least 8 characters with upper and lower case and a number.</Card.Description>
    </Card.Header>
    <Card.Content>
      <form onsubmit={handleSubmit} class="space-y-4">
        <div class="space-y-2">
          <label for="password" class="text-sm font-medium leading-none">Password</label>
          <Input id="password" type="password" bind:value={$form.password} placeholder="••••••••" autocomplete="new-password" required aria-invalid={!!$form.errors?.password} />
          {#if $form.errors?.password}
            <p class="text-sm text-destructive">{typeof $form.errors.password === 'string' ? $form.errors.password : $form.errors.password[0]}</p>
          {/if}
        </div>
        <div class="space-y-2">
          <label for="password_confirmation" class="text-sm font-medium leading-none">Confirm password</label>
          <Input id="password_confirmation" type="password" bind:value={$form.password_confirmation} placeholder="••••••••" autocomplete="new-password" required />
          {#if $form.errors?.password_confirmation}
            <p class="text-sm text-destructive">{typeof $form.errors.password_confirmation === 'string' ? $form.errors.password_confirmation : $form.errors.password_confirmation[0]}</p>
          {/if}
        </div>
        <Button type="submit" class="w-full min-h-[44px]" disabled={$form.processing}>
          {$form.processing ? 'Setting password...' : 'Set password'}
        </Button>
      </form>
    </Card.Content>
  </Card.Root>
</PortalGuestLayout>
