<script>
  import GuestLayout from '@/Layouts/GuestLayout.svelte';
  import { Link } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { CheckCircle } from 'lucide-svelte';

  let { reference_number, appointment_details } = $props();
</script>

<svelte:head>
  <title>Application Submitted - SecureCAT</title>
</svelte:head>

<GuestLayout>
  <div class="rounded-lg border border-border bg-card p-8 text-center shadow-lg">
    <CheckCircle class="mx-auto h-16 w-16 text-green-600" />
    <h1 class="mt-4 text-2xl font-bold">Application Submitted</h1>
    <p class="mt-2 text-muted-foreground">
      Your reference number is <strong class="font-mono text-foreground">{reference_number}</strong>
    </p>
    {#if appointment_details}
      <p class="mt-4 text-sm text-muted-foreground">
        Appointment: {appointment_details}
      </p>
    {/if}
    <p class="mt-6 text-sm text-muted-foreground">
      Please save this reference number. You will receive further instructions via email.
    </p>
    <div class="mt-8 flex justify-center gap-4">
      <Link href="/">
        <Button>Back to Home</Button>
      </Link>
    </div>
  </div>
</GuestLayout>
