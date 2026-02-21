<script>
  import { Link } from '@inertiajs/svelte';

  let { children } = $props();
</script>

<svelte:head>
  <title>Applicant Portal - SecureCAT</title>
</svelte:head>

<div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-primary/10 via-background to-primary/5 px-4 py-12">
  <Link href="/" class="mb-8 flex items-center gap-2 text-2xl font-bold tracking-tight text-foreground no-underline hover:text-primary transition-colors">
    <span class="text-primary">SecureCAT</span>
    <span class="text-muted-foreground text-base font-normal">Applicant Portal</span>
  </Link>

  <div class="w-full max-w-md">
    {@render children?.()}
  </div>

  <p class="mt-8 text-sm text-muted-foreground">
    <Link href="/" class="text-primary hover:underline">Back to home</Link>
  </p>
</div>
