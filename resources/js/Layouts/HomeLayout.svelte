<script>
  import { Link, usePage } from '@inertiajs/svelte';
  
  let { children } = $props();
  const page = usePage();
</script>

<div class="min-h-screen bg-background flex flex-col font-sans">
  <!-- Minimal Header -->
  <header class="border-b border-border/40 bg-card/50 backdrop-blur sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <div class="flex items-center gap-2">
          <!-- Logo could be an svg or img -->
          <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary text-primary-foreground font-bold shadow-sm">
            SC
          </div>
          <span class="font-bold text-lg tracking-tight text-foreground hidden sm:block">SecureCAT</span>
        </div>
        
        <nav class="flex items-center gap-4 sm:gap-6 text-sm font-medium">
          <Link href="/" class="text-muted-foreground hover:text-foreground transition-colors {page.url === '/' ? 'text-primary' : ''}">Home</Link>
          <Link href="/apply" class="text-muted-foreground hover:text-foreground transition-colors {page.url === '/apply' ? 'text-primary' : ''}">Application</Link>
          <Link href="/about" class="text-muted-foreground hover:text-foreground transition-colors {page.url === '/about' ? 'text-primary' : ''}">About</Link>
          <Link href="/login" class="px-5 py-2 rounded-full bg-primary text-primary-foreground hover:bg-primary/90 transition-colors shadow-sm font-semibold">
            Login
          </Link>
        </nav>
      </div>
    </div>
  </header>

  <!-- Page Content -->
  <main class="flex-grow">
    {@render children()}
  </main>

  <!-- Minimal Footer -->
  <footer class="bg-card border-t border-border mt-auto py-8">
    <div class="max-w-7xl mx-auto px-4 text-center">
      <p class="text-muted-foreground text-sm">
        &copy; {new Date().getFullYear()} Ilocos Sur Polytechnic State College - Tagudin Campus.<br/>
        <span class="text-muted-foreground/60">Powered by SecureCAT.</span>
      </p>
    </div>
  </footer>
</div>
