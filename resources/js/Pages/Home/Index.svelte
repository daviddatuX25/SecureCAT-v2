<script>
  import HomeLayout from '@/Layouts/HomeLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Card, CardContent } from '@/Components/ui/card';
  import ProcessFlow from '@/Components/blocks/ProcessFlow.svelte';

  let { activeSeason = null } = $props();
  
  const page = usePage();
  const auth = $derived($page.props.auth ?? {});
  const isAuthenticated = $derived(!!auth.user || !!auth.applicant);

  const departments = [
    {
      name: "College of Teacher Education",
      description: "Shape the minds of tomorrow. Join an esteemed tradition of progressive pedagogy and excellence in education.",
      icon: "M12 14l9-5-9-5-9 5 9 5z M12 14v7M5 10v7a7 7 0 0014 0v-7",
      contact: "(072) 123-4567",
      facebook: "fb.com/ispsc.cte"
    },
    {
      name: "College of Arts and Sciences",
      description: "Foster your critical thinking, deepen your creativity, and embark on a journey of scientific inquiry.",
      icon: "M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z",
      contact: "(072) 765-4321",
      facebook: "fb.com/ispsc.cas"
    },
    {
      name: "College of Business Management & Entrepreneurship",
      description: "Take the lead. We inspire the next generation of business leaders and innovative entrepreneurs.",
      icon: "M13 7h8m0 0v8m0-8l-8 8-4-4-6 6",
      contact: "(072) 555-8888",
      facebook: "fb.com/ispsc.cbme"
    }
  ];
</script>

<svelte:head>
  <title>Welcome - Ilocos Sur Polytechnic State College</title>
</svelte:head>

<HomeLayout>
  <!-- Hero Section -->
  <div class="relative overflow-hidden bg-background py-20 sm:py-32 border-b border-border/50">
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

  <ProcessFlow />

  <!-- Departments Section -->
  <div class="py-24 bg-card/10 relative border-t border-border/50">
    <!-- Subtle dotted background pattern -->
    <div class="absolute inset-0 z-0 opacity-[0.03]" style="background-image: radial-gradient(currentColor 1px, transparent 1px); background-size: 24px 24px;"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="text-center mb-20 relative">
        <Badge variant="outline" class="mb-4 bg-background">Academics</Badge>
        <h2 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-5xl">Colleges & Departments</h2>
        <p class="mt-4 text-xl text-muted-foreground max-w-2xl mx-auto">
          Find where you belong. Our colleges offer specialized, cutting-edge environments to help you master your chosen field.
        </p>
        <div class="mt-6 inline-flex items-center gap-2 bg-primary/10 text-primary px-4 py-2 rounded-full border border-primary/20 text-sm font-medium">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          All department offices are located at the Dormitory Building, Ilocos Sur Polytechnic State College - Tagudin Campus.
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-8">
        {#each departments as dept}
          <div class="h-full flex flex-col group">
            <div class="flex-1 flex flex-col relative rounded-3xl glass-panel border border-border/60 bg-card/60 backdrop-blur-xl transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl hover:shadow-primary/10 overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              
              <div class="p-8 flex-1 flex flex-col relative z-20">
                <div class="h-16 w-16 mb-8 rounded-2xl bg-gradient-to-br from-primary/20 to-primary/5 text-primary flex items-center justify-center border border-primary/10 group-hover:scale-110 group-hover:bg-primary group-hover:text-primary-foreground transform transition-all duration-500 shadow-sm">
                  <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d={dept.icon} />
                  </svg>
                </div>
                
                <h3 class="text-2xl font-bold text-foreground mb-4 leading-tight group-hover:text-primary transition-colors">{dept.name}</h3>
                <p class="text-muted-foreground text-base leading-relaxed mb-8 flex-1">{dept.description}</p>
                
                <!-- Contact Info Footer -->
                <div class="pt-6 mt-auto border-t border-border/50 divide-y divide-border/30">
                  <div class="flex items-center gap-3 py-3 text-sm text-foreground">
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                      <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <span class="font-medium">{dept.contact}</span>
                  </div>
                  <div class="flex items-center gap-3 py-3 text-sm transition-colors group-hover:text-primary">
                    <div class="w-8 h-8 rounded-full bg-[#1877F2]/10 flex items-center justify-center shrink-0">
                      <svg class="h-4 w-4 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </div>
                    <a href={`https://${dept.facebook}`} target="_blank" rel="noopener noreferrer" class="font-bold hover:underline">{dept.facebook}</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        {/each}
      </div>
    </div>
  </div>
</HomeLayout>
