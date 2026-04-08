<script>
  import HomeLayout from '@/Layouts/HomeLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import ProcessFlow from '@/Components/blocks/ProcessFlow.svelte';

  let { activeSeason = null } = $props();
  
  const page = usePage();
  const auth = $derived($page.props.auth ?? {});
  const isAuthenticated = $derived(!!auth.user || !!auth.applicant);
</script>

<svelte:head>
  <title>Welcome - Ilocos Sur Polytechnic State College</title>
</svelte:head>

<HomeLayout>
  <!-- Journey / Process Flow Section First -->
  <ProcessFlow />

  <!-- Hero / CTA Section Second -->
  <div class="relative overflow-hidden bg-background py-20 sm:py-32">
    <!-- Dynamic Glassmorphic Blobs -->
    <div class="absolute -top-[20%] -left-[10%] h-[600px] w-[600px] rounded-full bg-gradient-to-tr from-primary/20 to-blue-500/10 blur-3xl animate-in fade-in duration-1000" aria-hidden="true"></div>
    <div class="absolute top-[20%] -right-[10%] h-[500px] w-[500px] rounded-full bg-gradient-to-bl from-emerald-500/10 to-primary/10 blur-3xl animate-in fade-in duration-1000 delay-300" aria-hidden="true"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center flex flex-col items-center">
      
      {#if activeSeason}
        <Badge variant="outline" class="mb-10 px-4 py-1.5 text-sm font-semibold tracking-wide border-primary/30 bg-primary/5 text-primary shadow-sm animate-in fade-in slide-in-from-bottom-2 duration-500 inline-flex items-center">
          <span class="mr-2 shrink-0 h-2 w-2 rounded-full bg-primary animate-pulse"></span>
          Admissions are open for {activeSeason.name}
        </Badge>
      {/if}

      <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-foreground mb-6 text-balance drop-shadow-sm leading-tight">
        Take the First Step Toward Your <br class="hidden md:block"/>
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-emerald-600">Dream Career</span>
      </h1>
      
      <p class="mt-6 text-xl md:text-2xl leading-relaxed text-muted-foreground max-w-3xl mx-auto text-balance font-medium">
        Your journey begins here. Join a community of driven learners, discover exceptional academic programs, and apply securely today.
      </p>

      <div class="mt-16 w-full max-w-4xl mx-auto z-10">
        {#if isAuthenticated}
          <div class="glass-panel p-8 rounded-3xl border border-primary/20 bg-primary/5 w-full flex flex-col items-center justify-center animate-in zoom-in-95 duration-500">
            <h2 class="text-2xl font-bold text-foreground mb-3">Welcome back!</h2>
            <p class="text-muted-foreground mb-6">You are securely signed in. Continue managing your profile or check your admission status from your dashboard.</p>
            <Link href={auth.user ? '/dashboard' : '/portal'} class="w-full sm:w-auto">
              <Button size="lg" class="h-14 px-8 text-lg font-bold shadow-xl shadow-primary/20 hover:shadow-2xl transition-all rounded-full bg-gradient-to-r from-primary to-blue-600 hover:-translate-y-0.5 border-0">
                Go to Dashboard
              </Button>
            </Link>
          </div>
        {:else}
          <div class="flex flex-col sm:flex-row items-center justify-center gap-5 mt-4 animate-in fade-in slide-in-from-bottom-5 duration-700">
            <Link href="/apply" class="w-full sm:w-auto">
              <Button size="lg" class="w-full h-16 px-10 text-lg font-bold shadow-xl shadow-primary/20 hover:shadow-2xl hover:-translate-y-0.5 transition-all rounded-full bg-gradient-to-r from-primary to-emerald-600 hover:from-primary/90 hover:to-emerald-600/90 border-0">
                Join Us & Apply Now
              </Button>
            </Link>
          </div>
        {/if}
      </div>
    </div>
  </div>
</HomeLayout>
