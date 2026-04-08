<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  let { season } = $props();

  const form = useForm({
    academic_year: season.academic_year,
    semester: season.semester,
    application_start_date: season.application_start_date ?? '',
    application_end_date: season.application_end_date ?? '',
  });

  function submitForm(e) {
    e.preventDefault();
    $form.put(`/admin/seasons/${season.id}`);
  }
const breadcrumbs = [{ label: 'Academic Years', href: '/admin/seasons' }, { label: 'Edit Season' }];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-lg space-y-6">
    <div class="flex items-center gap-4">
      <Link href="/admin/seasons" class="text-sm text-muted-foreground hover:text-foreground">Back to seasons</Link>
    </div>

    <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="academic_year" class="text-sm font-medium">Academic year</label>
        <Input
          id="academic_year"
          bind:value={$form.academic_year}
          placeholder="e.g., 2025-2026"
          required
          maxlength="20"
        />
        {#if $form.errors?.academic_year}
          <p class="text-sm text-destructive">{$form.errors.academic_year}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="semester" class="text-sm font-medium">Semester</label>
        <Input
          id="semester"
          bind:value={$form.semester}
          placeholder="e.g., 1 or First Semester"
          required
          maxlength="50"
        />
        {#if $form.errors?.semester}
          <p class="text-sm text-destructive">{$form.errors.semester}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="application_start_date" class="text-sm font-medium">Application window start</label>
        <Input
          id="application_start_date"
          type="date"
          bind:value={$form.application_start_date}
        />
        {#if $form.errors?.application_start_date}
          <p class="text-sm text-destructive">{$form.errors.application_start_date}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="application_end_date" class="text-sm font-medium">Application window end</label>
        <Input
          id="application_end_date"
          type="date"
          bind:value={$form.application_end_date}
        />
        {#if $form.errors?.application_end_date}
          <p class="text-sm text-destructive">{$form.errors.application_end_date}</p>
        {/if}
      </div>

      {#if season.is_active}
        <p class="text-sm text-muted-foreground">This season is currently active. Use &quot;Set active&quot; on another season from the list to change.</p>
      {/if}

      <div class="flex gap-2 pt-4">
        <Button type="submit" disabled={$form.processing}>
          {$form.processing ? 'Saving...' : 'Save'}
        </Button>
        <Link href="/admin/seasons">
          <Button type="button" variant="outline">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
