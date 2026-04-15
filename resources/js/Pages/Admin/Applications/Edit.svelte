<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import { success, error } from '@/lib/toast';

  let { application, courses = [], appointments = [], active_season = null, statuses = [] } = $props();

  const form = useForm({
    first_name: application?.first_name || '',
    middle_name: application?.middle_name || '',
    last_name: application?.last_name || '',
    suffix: application?.suffix || '',
    birthdate: application?.birthdate || '',
    sex: application?.sex || '',
    email: application?.email || '',
    phone: application?.phone || '',
    address_line: application?.address_line || '',
    city: application?.city || '',
    province: application?.province || '',
    zip_code: application?.zip_code || '',
    course_preference_1: application?.course_preference_1 || '',
    course_preference_2: application?.course_preference_2 || '',
    course_preference_3: application?.course_preference_3 || '',
    appointment_id: application?.appointment_id || '',
    status: application?.status || 'pending',
    rejection_reason: application?.rejection_reason || '',
  });

  $form.onFinish = () => {
    if (!$form.errors || Object.keys($form.errors).length === 0) {
      success('Application updated');
    } else {
      error('Please fix the errors in the form');
    }
  };

  const coursesUnique = $derived(
    courses.filter((c, i, a) => a.findIndex((x) => x.id === c.id) === i)
  );
  const optionsFor2 = $derived.by(() =>
    coursesUnique.filter((c) => String(c.id) !== String($form.course_preference_1))
  );
  const optionsFor3 = $derived.by(() =>
    coursesUnique.filter(
      (c) =>
        String(c.id) !== String($form.course_preference_1) &&
        String(c.id) !== String($form.course_preference_2)
    )
  );

  $effect(() => {
    const p1 = $form.course_preference_1;
    const p2 = $form.course_preference_2;
    const p3 = $form.course_preference_3;
    const updates = {};
    if (p2 && String(p2) === String(p1)) {
      updates.course_preference_2 = '';
    }
    if (p3 && (String(p3) === String(p1) || String(p3) === String(p2))) {
      updates.course_preference_3 = '';
    }
    if (Object.keys(updates).length) {
      form.setData({ ...$form, ...updates });
    }
  });

  function submitForm(e) {
    e.preventDefault();
    $form.put(`/admin/applications/${application?.id}`);
  }

  const breadcrumbs = [{ label: 'Applications', href: '/admin/applications' }, { label: 'Edit' }];
</script>

<svelte:head>
  <title>Edit Application - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout {breadcrumbs}>
  <div class="mx-auto max-w-2xl space-y-6">
    {#if active_season}
      <div class="rounded-lg bg-muted/50 px-4 py-2 text-sm text-muted-foreground">
        Editing application for A.Y. {active_season.academic_year} – {active_season.semester_label}
      </div>
    {/if}

    {#if application?.submitted_at}
      <div class="grid gap-2 rounded-lg bg-muted/50 p-4 text-sm sm:grid-cols-2">
        <div>
          <span class="text-muted-foreground">Reference Number:</span>
          <span class="ml-2 font-medium">{application.reference_number}</span>
        </div>
        <div>
          <span class="text-muted-foreground">Submitted:</span>
          <span class="ml-2">{new Date(application.submitted_at).toLocaleString()}</span>
        </div>
      </div>
    {/if}

    <form onsubmit={submitForm} class="space-y-6 rounded-lg border border-border bg-card p-6">
      <div class="space-y-4">
        <h3 class="text-lg font-semibold">Personal Information</h3>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <label for="first_name" class="text-sm font-medium">First name *</label>
            <Input id="first_name" bind:value={$form.first_name} />
            {#if $form.errors?.first_name}
              <p class="text-sm text-destructive">{$form.errors.first_name}</p>
            {/if}
          </div>
          <div class="space-y-2">
            <label for="last_name" class="text-sm font-medium">Last name *</label>
            <Input id="last_name" bind:value={$form.last_name} />
            {#if $form.errors?.last_name}
              <p class="text-sm text-destructive">{$form.errors.last_name}</p>
            {/if}
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <label for="middle_name" class="text-sm font-medium">Middle name</label>
            <Input id="middle_name" bind:value={$form.middle_name} />
          </div>
          <div class="space-y-2">
            <label for="suffix" class="text-sm font-medium">Suffix</label>
            <Input id="suffix" bind:value={$form.suffix} placeholder="e.g., Jr., Sr." />
          </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <label for="birthdate" class="text-sm font-medium">Birthdate *</label>
            <Input id="birthdate" type="date" bind:value={$form.birthdate} />
            {#if $form.errors?.birthdate}
              <p class="text-sm text-destructive">{$form.errors.birthdate}</p>
            {/if}
          </div>
          <div class="space-y-2">
            <label for="sex" class="text-sm font-medium">Sex *</label>
            <select id="sex" bind:value={$form.sex} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option value="">Select</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
            {#if $form.errors?.sex}
              <p class="text-sm text-destructive">{$form.errors.sex}</p>
            {/if}
          </div>
        </div>
      </div>

      <div class="space-y-4">
        <h3 class="text-lg font-semibold">Contact Information</h3>
        <div class="space-y-2">
          <label for="email" class="text-sm font-medium">Email *</label>
          <Input id="email" type="email" bind:value={$form.email} />
          {#if $form.errors?.email}
            <p class="text-sm text-destructive">{$form.errors.email}</p>
          {/if}
        </div>
        <div class="space-y-2">
          <label for="phone" class="text-sm font-medium">Phone</label>
          <Input id="phone" type="tel" bind:value={$form.phone} placeholder="e.g., 09123456789" />
        </div>
      </div>

      <div class="space-y-4">
        <h3 class="text-lg font-semibold">Address</h3>
        <div class="space-y-2">
          <label for="address_line" class="text-sm font-medium">Street address</label>
          <Input id="address_line" bind:value={$form.address_line} placeholder="House No., Street, Barangay" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <label for="city" class="text-sm font-medium">City</label>
            <Input id="city" bind:value={$form.city} />
          </div>
          <div class="space-y-2">
            <label for="province" class="text-sm font-medium">Province</label>
            <Input id="province" bind:value={$form.province} />
          </div>
        </div>
        <div class="space-y-2">
          <label for="zip_code" class="text-sm font-medium">ZIP code</label>
          <Input id="zip_code" bind:value={$form.zip_code} maxlength="10" />
        </div>
      </div>

      <div class="space-y-4">
        <h3 class="text-lg font-semibold">Course Preferences</h3>
        <p class="text-xs text-muted-foreground">Select one or up to three different courses in order of preference.</p>
        <div class="space-y-2">
          <select id="course_preference_1" bind:value={$form.course_preference_1} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">1st choice</option>
            {#each coursesUnique as c}
              <option value={c.id}>{c.code} – {c.name}</option>
            {/each}
          </select>
          <select bind:value={$form.course_preference_2} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">2nd choice (optional)</option>
            {#each optionsFor2 as c}
              <option value={c.id}>{c.code} – {c.name}</option>
            {/each}
          </select>
          <select bind:value={$form.course_preference_3} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">3rd choice (optional)</option>
            {#each optionsFor3 as c}
              <option value={c.id}>{c.code} – {c.name}</option>
            {/each}
          </select>
          {#if $form.errors?.course_preference_1}
            <p class="text-sm text-destructive">{$form.errors.course_preference_1}</p>
          {/if}
        </div>
      </div>

      <div class="space-y-4">
        <h3 class="text-lg font-semibold">Application Status</h3>
        <select bind:value={$form.status} class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
          {#each statuses as s}
            <option value={s.value}>{s.label}</option>
          {/each}
        </select>
      </div>

      <div class="space-y-4">
        <h3 class="text-lg font-semibold">Rejection Reason</h3>
        <textarea
          bind:value={$form.rejection_reason}
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          rows="3"
          placeholder="Enter reason for dismissal (if applicable)"
        ></textarea>
      </div>

      <div class="flex justify-end gap-4 pt-4">
        <Button type="submit" disabled={$form.processing}>
          {$form.processing ? 'Saving...' : 'Save Changes'}
        </Button>
        <Link href="/admin/applications">
          <Button type="button" variant="outline">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>