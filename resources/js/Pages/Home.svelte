<script>
  import { Link } from '@inertiajs/svelte';
  import Hero from '@/Components/blocks/Hero.svelte';
  import Benefits from '@/Components/blocks/Benefits.svelte';
  import PerspectiveSection from '@/Components/blocks/PerspectiveSection.svelte';
  import ProcessFlow from '@/Components/blocks/ProcessFlow.svelte';
  import CallToAction from '@/Components/blocks/CallToAction.svelte';
  
  // Data passed from controller
  let { systemName = 'SecureCAT' } = $props();
</script>

<svelte:head>
  <title>{systemName} - Computerized Admission & Testing</title>
  <meta name="description" content="Digitizing the complete student admission lifecycle from application to consultation" />
</svelte:head>

<main class="min-h-screen">
  <Hero 
    title="SecureCAT: Computerized Admission & Testing"
    subtitle="Digitizing the complete student admission lifecycle from application to consultation"
  />
  
  <Benefits />
  
  <PerspectiveSection />
  
  <ProcessFlow />
  
  <CallToAction 
    title="Ready to streamline your admission process?"
    description="Join the digital transformation of student admissions with SecureCAT"
  />
</main>

<footer class="bg-card border-t border-border py-8 px-4">
  <div class="max-w-7xl mx-auto text-center text-sm text-muted-foreground">
    <p class="mb-2">
      <span class="font-semibold text-foreground">SecureCAT</span> - Computerized Admission & Testing System
    </p>
    <p class="mb-2">
      Information Assurance and Security Academic Project
    </p>
    <p>
      <Link href="/apply" class="text-primary hover:underline">Apply online</Link>
      {' | '}
      <Link href="/login" class="text-primary hover:underline">Staff login</Link>
    </p>
  </div>
</footer>
