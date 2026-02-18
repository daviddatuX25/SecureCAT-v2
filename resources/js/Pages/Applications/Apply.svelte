<script>
  import GuestLayout from '@/Layouts/GuestLayout.svelte';
  import { useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';
  import * as Card from '@/Components/ui/card';

  let { courses = [], appointments = [] } = $props();

  const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',
    birthdate: '',
    sex: '',
    email: '',
    phone: '',
    address_line: '',
    city: '',
    province: '',
    zip_code: '',
    course_preference_1: '',
    course_preference_2: '',
    course_preference_3: '',
    appointment_id: '',
  });

  function handleSubmit(e) {
    e.preventDefault();
    $form.transform((data) => ({
      ...data,
      course_preference_1: parseInt(data.course_preference_1, 10) || null,
      course_preference_2: parseInt(data.course_preference_2, 10) || null,
      course_preference_3: parseInt(data.course_preference_3, 10) || null,
      appointment_id: data.appointment_id ? parseInt(data.appointment_id, 10) : null,
    }));
    $form.post('/applications');
  }

  const courseOptions = $derived(courses.map((c) => ({ value: String(c.id), label: `${c.code} - ${c.name}` })));
  const pref2Options = $derived(courseOptions.filter((o) => o.value !== String($form.course_preference_1 || '')));
  const pref3Options = $derived(courseOptions.filter((o) => {
    const p1 = String($form.course_preference_1 || '');
    const p2 = String($form.course_preference_2 || '');
    return o.value !== p1 && o.value !== p2;
  }));
</script>

<svelte:head>
  <title>Apply - SecureCAT</title>
</svelte:head>

<GuestLayout>
  <Card.Root class="shadow-lg">
    <Card.Header>
      <Card.Title class="text-xl">Application Form</Card.Title>
      <Card.Description>Complete all sections. Fields marked with * are required.</Card.Description>
    </Card.Header>
    <Card.Content>
      <form onsubmit={handleSubmit} class="space-y-6">
        <!-- Personal info -->
        <div class="space-y-4">
          <h3 class="text-sm font-semibold text-foreground">Personal Information</h3>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <label for="first_name" class="text-sm font-medium">First name *</label>
              <Input id="first_name" bind:value={$form.first_name} required maxlength="100" />
              {#if $form.errors?.first_name}
                <p class="text-sm text-destructive">{$form.errors.first_name}</p>
              {/if}
            </div>
            <div class="space-y-2">
              <label for="middle_name" class="text-sm font-medium">Middle name</label>
              <Input id="middle_name" bind:value={$form.middle_name} maxlength="100" />
            </div>
            <div class="space-y-2">
              <label for="last_name" class="text-sm font-medium">Last name *</label>
              <Input id="last_name" bind:value={$form.last_name} required maxlength="100" />
              {#if $form.errors?.last_name}
                <p class="text-sm text-destructive">{$form.errors.last_name}</p>
              {/if}
            </div>
            <div class="space-y-2">
              <label for="suffix" class="text-sm font-medium">Suffix</label>
              <Input id="suffix" bind:value={$form.suffix} placeholder="Jr., III" maxlength="20" />
            </div>
            <div class="space-y-2">
              <label for="birthdate" class="text-sm font-medium">Birthdate *</label>
              <Input id="birthdate" type="date" bind:value={$form.birthdate} required />
              {#if $form.errors?.birthdate}
                <p class="text-sm text-destructive">{$form.errors.birthdate}</p>
              {/if}
            </div>
            <div class="space-y-2">
              <label for="sex" class="text-sm font-medium">Sex *</label>
              <select
                id="sex"
                bind:value={$form.sex}
                required
                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
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

        <!-- Contact -->
        <div class="space-y-4 border-t border-border pt-6">
          <h3 class="text-sm font-semibold text-foreground">Contact Information</h3>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <label for="email" class="text-sm font-medium">Email *</label>
              <Input id="email" type="email" bind:value={$form.email} required />
              {#if $form.errors?.email}
                <p class="text-sm text-destructive">{$form.errors.email}</p>
              {/if}
            </div>
            <div class="space-y-2">
              <label for="phone" class="text-sm font-medium">Phone</label>
              <Input id="phone" type="tel" bind:value={$form.phone} maxlength="20" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <label for="address_line" class="text-sm font-medium">Address</label>
              <Input id="address_line" bind:value={$form.address_line} maxlength="255" />
            </div>
            <div class="space-y-2">
              <label for="city" class="text-sm font-medium">City</label>
              <Input id="city" bind:value={$form.city} maxlength="100" />
            </div>
            <div class="space-y-2">
              <label for="province" class="text-sm font-medium">Province</label>
              <Input id="province" bind:value={$form.province} maxlength="100" />
            </div>
            <div class="space-y-2">
              <label for="zip_code" class="text-sm font-medium">Zip code</label>
              <Input id="zip_code" bind:value={$form.zip_code} maxlength="10" />
            </div>
          </div>
        </div>

        <!-- Course preferences -->
        <div class="space-y-4 border-t border-border pt-6">
          <h3 class="text-sm font-semibold text-foreground">Course Preferences (ranked)</h3>
          <p class="text-xs text-muted-foreground">Select three different courses in order of preference.</p>
          <div class="grid gap-4 sm:grid-cols-3">
            <div class="space-y-2">
              <label for="course_preference_1" class="text-sm font-medium">1st preference *</label>
              <select
                id="course_preference_1"
                bind:value={$form.course_preference_1}
                required
                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option value="">Select</option>
                {#each courseOptions as opt}
                  <option value={opt.value}>{opt.label}</option>
                {/each}
              </select>
              {#if $form.errors?.course_preference_1}
                <p class="text-sm text-destructive">{$form.errors.course_preference_1}</p>
              {/if}
            </div>
            <div class="space-y-2">
              <label for="course_preference_2" class="text-sm font-medium">2nd preference *</label>
              <select
                id="course_preference_2"
                bind:value={$form.course_preference_2}
                required
                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option value="">Select</option>
                {#each pref2Options as opt}
                  <option value={opt.value}>{opt.label}</option>
                {/each}
              </select>
              {#if $form.errors?.course_preference_2}
                <p class="text-sm text-destructive">{$form.errors.course_preference_2}</p>
              {/if}
            </div>
            <div class="space-y-2">
              <label for="course_preference_3" class="text-sm font-medium">3rd preference *</label>
              <select
                id="course_preference_3"
                bind:value={$form.course_preference_3}
                required
                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              >
                <option value="">Select</option>
                {#each pref3Options as opt}
                  <option value={opt.value}>{opt.label}</option>
                {/each}
              </select>
              {#if $form.errors?.course_preference_3}
                <p class="text-sm text-destructive">{$form.errors.course_preference_3}</p>
              {/if}
            </div>
          </div>
        </div>

        <!-- Optional appointment -->
        {#if appointments.length > 0}
        <div class="space-y-4 border-t border-border pt-6">
          <h3 class="text-sm font-semibold text-foreground">Appointment (optional)</h3>
          <p class="text-xs text-muted-foreground">Book a slot for in-person submission.</p>
          <div class="space-y-2">
            <label for="appointment_id" class="text-sm font-medium">Preferred slot</label>
            <select
              id="appointment_id"
              bind:value={$form.appointment_id}
              class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            >
              <option value="">Walk-in (no appointment)</option>
              {#each appointments as apt}
                <option value={apt.id}>{apt.label} ({apt.booked_count}/{apt.max_slots})</option>
              {/each}
            </select>
          </div>
        </div>
        {/if}

        <div class="flex justify-end gap-2 pt-4">
          <Button type="submit" disabled={$form.processing}>
            {$form.processing ? 'Submitting...' : 'Submit Application'}
          </Button>
        </div>
      </form>
    </Card.Content>
  </Card.Root>
</GuestLayout>
