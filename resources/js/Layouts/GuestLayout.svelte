<script>
  import { Link } from '@inertiajs/svelte';

  let { children } = $props();
</script>

<svelte:head>
  <title>SecureCAT - Staff Login</title>
</svelte:head>

<div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-primary/5 via-background to-accent/5 px-4 py-12">
  <!-- Logo / Brand -->
  <Link href="/" class="mb-8 flex items-center gap-2 text-2xl font-bold tracking-tight text-foreground no-underline hover:text-primary transition-colors">
    <span class="text-primary">SecureCAT</span>
  </Link>

  <!-- Centered card -->
  <div class="w-full max-w-md">
    {@render children?.()}
  </div>

  <!-- Footer link -->
  <p class="mt-8 text-sm text-muted-foreground">
    <Link href="/" class="text-primary hover:underline">Back to home</Link>
  </p>
</div>
