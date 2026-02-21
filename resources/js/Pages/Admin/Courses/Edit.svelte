<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  const { course, departments = [] } = $props();

  const form = (() => {
    const c = course;
    return useForm({
      department_id: String(c.department_id),
      name: c.name,
      code: c.code,
      quota: c.quota === null || c.quota === undefined ? '' : String(c.quota),
      score_cutoff: c.score_cutoff === null || c.score_cutoff === undefined ? '' : String(c.score_cutoff),
      is_active: c.is_active,
    });
  })();

  function submitForm(e) {
    e.preventDefault();
    $form.transform((data) => ({
      ...data,
      department_id: parseInt(data.department_id, 10) || null,
      quota: data.quota === '' ? null : parseInt(data.quota, 10),
      score_cutoff: data.score_cutoff === '' ? null : parseFloat(data.score_cutoff),
    }));
    $form.put(`/admin/courses/${course.id}`);
  }
</script>

<svelte:head>
  <title>Edit Course - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="max-w-lg space-y-6">
    <div class="flex items-center gap-4">
      <Link href="/admin/courses" class="text-sm text-muted-foreground hover:text-foreground">Back to courses</Link>
      <h1 class="text-2xl font-bold">Edit Course</h1>
    </div>

    <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="department_id" class="text-sm font-medium">Department</label>
        <select
          id="department_id"
          bind:value={$form.department_id}
          required
          class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        >
          <option value="">Select</option>
          {#each departments as d}
            <option value={String(d.id)}>{d.code} — {d.name}</option>
          {/each}
        </select>
        {#if $form.errors?.department_id}
          <p class="text-sm text-destructive">{$form.errors.department_id}</p>
        {/if}
      </div>

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

      <div class="grid gap-4 sm:grid-cols-2">
        <div class="space-y-2">
          <label for="quota" class="text-sm font-medium">Quota (optional)</label>
          <Input id="quota" type="number" min="1" bind:value={$form.quota} placeholder="Unlimited if blank" />
          {#if $form.errors?.quota}
            <p class="text-sm text-destructive">{$form.errors.quota}</p>
          {/if}
        </div>
        <div class="space-y-2">
          <label for="score_cutoff" class="text-sm font-medium">Score cutoff (optional)</label>
          <Input id="score_cutoff" type="number" min="0" step="0.01" bind:value={$form.score_cutoff} placeholder="e.g., 75.00" />
          {#if $form.errors?.score_cutoff}
            <p class="text-sm text-destructive">{$form.errors.score_cutoff}</p>
          {/if}
        </div>
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

