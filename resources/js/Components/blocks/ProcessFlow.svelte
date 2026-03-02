<script>
  import { ArrowRight } from 'lucide-svelte';
  
  let {
    phases = [
      {
        number: 1,
        name: 'Application & Appointment',
        description: 'Applicants book slots or walk in. Staff process applications and provision accounts.',
        office: 'Registrar',
        color: '#C8401A'
      },
      {
        number: 2,
        name: 'Exam Scheduling',
        description: 'AI-assisted schedule generation with room allocation and proctor assignment.',
        office: 'Registrar',
        color: '#1A5FC8'
      },
      {
        number: 3,
        name: 'Examination',
        description: 'Proctors log attendance and submissions with real-time session monitoring.',
        office: 'Guidance',
        color: '#1A9C55'
      },
      {
        number: 4,
        name: 'Marking & Grading',
        description: 'OMR scanning and manual grade input with score normalization.',
        office: 'Guidance',
        color: '#8B2AC8'
      },
      {
        number: 5,
        name: 'Results & Consultation',
        description: 'Test administrators review analytics, override recommendations, and release results.',
        office: 'Guidance',
        color: '#C87A1A'
      }
    ]
  } = $props();
</script>

<section class="py-16 md:py-24 px-4 bg-muted/30">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-bold mb-4">
        The Complete Process
      </h2>
      <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
        SecureCAT guides applicants through a structured 5-phase admission pipeline
      </p>
    </div>
    
    <!-- Desktop: Horizontal Timeline -->
    <div class="hidden lg:block">
      <div class="relative">
        <!-- Single connection line spanning all circles -->
        <div class="absolute top-8 left-0 right-0 h-1 bg-border z-0" style="left: 8%; right: 8%;"></div>
        
        <div class="flex justify-between items-start relative">
          {#each phases as phase, index}
            <div class="flex-1 flex flex-col items-center px-2">
              <!-- Phase Circle - positioned on the line -->
              <div 
                class="w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-xl relative z-10 shadow-lg"
                style="background-color: {phase.color}"
              >
                {phase.number}
              </div>
              
              <!-- Phase Card -->
              <div class="mt-6 text-center max-w-[200px]">
                <h3 class="font-bold text-base mb-2">
                  {phase.name}
                </h3>
                <p class="text-xs text-muted-foreground mb-2">
                  {phase.description}
                </p>
                <span 
                  class="inline-block text-xs font-medium px-3 py-1 rounded-full"
                  style="background-color: {phase.color}20; color: {phase.color}"
                >
                  {phase.office}
                </span>
              </div>
            </div>
          {/each}
        </div>
      </div>
    </div>
    
    <!-- Mobile/Tablet: Vertical Timeline -->
    <div class="lg:hidden space-y-8">
      {#each phases as phase, index}
        <div class="flex gap-4">
          <!-- Left: Phase Circle -->
          <div class="flex-shrink-0 flex flex-col items-center">
            <div 
              class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold shadow-lg"
              style="background-color: {phase.color}"
            >
              {phase.number}
            </div>
            {#if index < phases.length - 1}
              <div class="w-1 flex-1 bg-border mt-2 min-h-[60px]"></div>
            {/if}
          </div>
          
          <!-- Right: Phase Content -->
          <div class="flex-1 pb-4">
            <h3 class="font-bold text-lg mb-2">
              {phase.name}
            </h3>
            <p class="text-sm text-muted-foreground mb-3">
              {phase.description}
            </p>
            <span 
              class="inline-block text-xs font-medium px-3 py-1 rounded-full"
              style="background-color: {phase.color}20; color: {phase.color}"
            >
              {phase.office}
            </span>
          </div>
        </div>
      {/each}
    </div>
    
    <!-- Flow Direction Indicator -->
    <div class="mt-12 text-center">
      <div class="inline-flex items-center gap-3 px-6 py-3 bg-card rounded-full border shadow-sm">
        <span class="text-sm font-medium">Start</span>
        <ArrowRight class="w-4 h-4 text-muted-foreground" />
        <span class="text-sm text-muted-foreground">Application</span>
        <ArrowRight class="w-4 h-4 text-muted-foreground" />
        <span class="text-sm text-muted-foreground">Testing</span>
        <ArrowRight class="w-4 h-4 text-muted-foreground" />
        <span class="text-sm text-muted-foreground">Results</span>
        <ArrowRight class="w-4 h-4 text-muted-foreground" />
        <span class="text-sm font-medium text-primary">Consultation</span>
      </div>
    </div>
  </div>
</section>
