<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  let { user, roles } = $props();

  const initialRoles = (user.roles ?? []).map((r) => r.name);
  let selectedRoles = $state([...initialRoles]);

  const form = useForm({
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
    roles: initialRoles,
  });

  function toggleRole(name) {
    if (selectedRoles.includes(name)) {
      selectedRoles = selectedRoles.filter((r) => r !== name);
    } else {
      selectedRoles = [...selectedRoles, name];
    }
  }

  function submitForm(e) {
    e.preventDefault();
    $form.transform((data) => ({ ...data, roles: selectedRoles }));
    $form.put(`/admin/users/${user.id}`);
  }
</script>

<svelte:head>
  <title>Edit User - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="max-w-lg space-y-6">
    <div class="flex items-center gap-4">
      <Link href="/admin/users" class="text-sm text-muted-foreground hover:text-foreground">Back to users</Link>
      <h1 class="text-2xl font-bold">Edit User</h1>
    </div>

    <form onsubmit={submitForm}
      class="space-y-4 rounded-lg border border-border bg-card p-6"
    >
      <div class="space-y-2">
        <label for="name" class="text-sm font-medium">Name</label>
        <Input id="name" bind:value={$form.name} required />
        {#if $form.errors?.name}
          <p class="text-sm text-destructive">{$form.errors.name}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="email" class="text-sm font-medium">Email</label>
        <Input id="email" type="email" bind:value={$form.email} required />
        {#if $form.errors?.email}
          <p class="text-sm text-destructive">{$form.errors.email}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="password" class="text-sm font-medium">New Password</label>
        <Input id="password" type="password" bind:value={$form.password} placeholder="Leave blank to keep current" />
        {#if $form.errors?.password}
          <p class="text-sm text-destructive">{$form.errors.password}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="password_confirmation" class="text-sm font-medium">Confirm New Password</label>
        <Input id="password_confirmation" type="password" bind:value={$form.password_confirmation} placeholder="Leave blank to keep current" />
      </div>

      <div class="space-y-2">
        <p class="text-sm font-medium">Roles</p>
        <p class="text-xs text-muted-foreground">Select one or more roles. You cannot remove your own Super Admin role.</p>
        <div class="flex flex-wrap gap-4 pt-2">
          {#each roles as r}
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={selectedRoles.includes(r.name)}
                onchange={() => toggleRole(r.name)}
                class="h-4 w-4 rounded border-input accent-primary"
              />
              <span class="text-sm">{r.display_name}</span>
            </label>
          {/each}
        </div>
        {#if $form.errors?.roles}
          <p class="text-sm text-destructive">{$form.errors.roles}</p>
        {/if}
      </div>

      <div class="flex gap-2 pt-4">
        <Button type="submit" disabled={$form.processing}>
          {$form.processing ? 'Saving...' : 'Save'}
        </Button>
        <Link href="/admin/users">
          <Button type="button" variant="outline">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
