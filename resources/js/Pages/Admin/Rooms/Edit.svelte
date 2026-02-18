<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  let { room } = $props();

  const defaultFacilities = { projector: false, ac: false, whiteboard: false };
  let facilities = $state({ ...defaultFacilities, ...(room.facilities ?? {}) });

  const form = useForm({
    name: room.name,
    building: room.building,
    floor: room.floor ?? '',
    capacity: String(room.capacity),
    is_active: room.is_active,
  });

  function submitForm(e) {
    e.preventDefault();
    $form.transform((data) => ({
      ...data,
      capacity: parseInt(data.capacity, 10),
      facilities,
    }));
    $form.put(`/admin/rooms/${room.id}`);
  }
</script>

<svelte:head>
  <title>Edit Room - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="max-w-lg space-y-6">
    <div class="flex items-center gap-4">
      <Link href="/admin/rooms" class="text-sm text-muted-foreground hover:text-foreground">Back to rooms</Link>
      <h1 class="text-2xl font-bold">Edit Room</h1>
    </div>

    <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="name" class="text-sm font-medium">Room name</label>
        <Input id="name" bind:value={$form.name} placeholder="e.g., Room 101" required />
        {#if $form.errors?.name}
          <p class="text-sm text-destructive">{$form.errors.name}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="building" class="text-sm font-medium">Building</label>
        <Input id="building" bind:value={$form.building} placeholder="e.g., ITBR" required />
        {#if $form.errors?.building}
          <p class="text-sm text-destructive">{$form.errors.building}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="floor" class="text-sm font-medium">Floor (optional)</label>
        <Input id="floor" bind:value={$form.floor} placeholder="e.g., 2nd Floor" />
        {#if $form.errors?.floor}
          <p class="text-sm text-destructive">{$form.errors.floor}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="capacity" class="text-sm font-medium">Capacity</label>
        <Input
          id="capacity"
          type="number"
          min="1"
          bind:value={$form.capacity}
          placeholder="Max examinees"
          required
        />
        {#if $form.errors?.capacity}
          <p class="text-sm text-destructive">{$form.errors.capacity}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <p class="text-sm font-medium">Facilities</p>
        <div class="flex flex-wrap gap-4 pt-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={facilities.projector}
              onchange={(e) => (facilities = { ...facilities, projector: e.target.checked })}
              class="h-4 w-4 rounded border-input accent-primary"
            />
            <span class="text-sm">Projector</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={facilities.ac}
              onchange={(e) => (facilities = { ...facilities, ac: e.target.checked })}
              class="h-4 w-4 rounded border-input accent-primary"
            />
            <span class="text-sm">Air conditioning</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={facilities.whiteboard}
              onchange={(e) => (facilities = { ...facilities, whiteboard: e.target.checked })}
              class="h-4 w-4 rounded border-input accent-primary"
            />
            <span class="text-sm">Whiteboard</span>
          </label>
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
        <p class="text-xs text-muted-foreground">Inactive rooms are hidden from scheduling.</p>
      </div>

      <div class="flex gap-2 pt-4">
        <Button type="submit" disabled={$form.processing}>
          {$form.processing ? 'Saving...' : 'Save'}
        </Button>
        <Link href="/admin/rooms">
          <Button type="button" variant="outline">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
