<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  const { course } = $props();

  const form = useForm({
    name: course.name,
    code: course.code,
    is_active: course.is_active,
  });

  function submitForm(e) {
    e.preventDefault();
    $form.put(`/admin/courses/${course.id}`);
  }
const breadcrumbs = [{ label: 'Courses', href: '/admin/courses' }, { label: 'Edit Course' }];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-lg space-y-6">
    <div class="flex items-center gap-4">
      <Link href="/admin/courses" class="text-sm text-muted-foreground hover:text-foreground">Back to courses</Link>
    </div>

    <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="code" class="text-sm font-medium">Course code</label>
        <Input id="code" bind:value={$form.code} required maxlength="20" />
        {#if $form.errors?.code}
          <p class="text-sm text-destructive">{$form.errors.code}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="name" class="text-sm font-medium">Course name</label>
        <Input id="name" bind:value={$form.name} required />
        {#if $form.errors?.name}
          <p class="text-sm text-destructive">{$form.errors.name}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label class="flex items-center gap-2 cursor-pointer">
          <input
            type="checkbox"
            bind:checked={$form.is_active}
            class="h-4 w-4 rounded border-input accent-primary"
          />
          <span class="text-sm font-medium">Active</span>
        </label>
        <p class="text-xs text-muted-foreground">Inactive courses are hidden from the public application form.</p>
      </div>

      <div class="flex gap-2 pt-4">
        <Button type="submit" disabled={$form.processing}>
          {$form.processing ? 'Saving...' : 'Save'}
        </Button>
        <Link href="/admin/courses">
          <Button type="button" variant="outline">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
