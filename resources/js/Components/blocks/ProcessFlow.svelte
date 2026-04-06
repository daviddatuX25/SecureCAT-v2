<script>
  import { ArrowRight } from 'lucide-svelte';
  
  let {
    phases = [
      {
        number: 1,
        name: 'Application',
        description: 'Apply online, then submit your physical requirements at the Registrar to secure a schedule.',
        office: 'Registrar',
        color: '#C8401A'
      },
      {
        number: 2,
        name: 'Slot Confirmation',
        description: 'Receive your verified exam schedule, complete with room assignment and testing guidelines.',
        office: 'Registrar',
        color: '#1A5FC8'
      },
      {
        number: 3,
        name: 'Examination Day',
        description: 'Take your secure admission test in our designated testing centers with trained proctors.',
        office: 'Guidance',
        color: '#1A9C55'
      },
      {
        number: 4,
        name: 'Evaluation',
        description: 'Scores are computed accurately with strict oversight from test administrators to ensure exam security.',
        office: 'Guidance',
        color: '#8B2AC8'
      },
      {
        number: 5,
        name: 'Results & Consultation',
        description: 'Wait for the scheduled release to personally receive academic advice and claim your official result sheet.',
        office: 'Guidance',
        color: '#C87A1A'
      }
    ]
  } = $props();
</script>

<section class="py-16 md:py-24 px-4 bg-muted/30">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <h2 class="text-3xl md:text-4xl font-bold mb-4">
        The Admission Journey
      </h2>
      <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
        Your pathway to joining our academic community is simple and straightforward.
      </p>
    </div>
    
    <!-- Desktop: Horizontal Timeline -->
    <div class="hidden lg:block">
      <div class="relative">
        <!-- Single connection line spanning all circles -->
        <div class="absolute top-8 left-0 right-0 h-1 bg-border z-0" style="left: 8%; right: 8%;"></div>
        
        <div class="flex justify-between items-start relative">
          {#each phases as phase, index}
            <div class="flex-1 flex flex-col items-center px-2 group animate-in zoom-in-95 duration-500" style="animation-delay: {index * 150}ms; animation-fill-mode: both;">
              <!-- Phase Circle - positioned on the line -->
              <div 
                class="w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-xl relative z-10 shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:-translate-y-1"
                style="background-color: {phase.color}"
              >
                {phase.number}
              </div>
              
              <!-- Phase Card -->
              <div class="mt-6 text-center max-w-[200px] transition-all duration-300 group-hover:translate-y-1">
                <h3 class="font-bold text-base mb-2 group-hover:text-primary transition-colors">
                  {phase.name}
                </h3>
                <p class="text-xs text-muted-foreground mb-2">
                  {phase.description}
                </p>
                <span 
                  class="inline-block text-xs font-medium px-3 py-1 rounded-full border border-transparent group-hover:border-current transition-colors"
                  style="background-color: {phase.color}15; color: {phase.color}"
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
        <div class="flex gap-4 group animate-in fade-in slide-in-from-right-4 duration-500" style="animation-delay: {index * 150}ms; animation-fill-mode: both;">
          <!-- Left: Phase Circle -->
          <div class="flex-shrink-0 flex flex-col items-center">
            <div 
              class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:-translate-y-1"
              style="background-color: {phase.color}"
            >
              {phase.number}
            </div>
            {#if index < phases.length - 1}
              <div class="w-1 flex-1 bg-border mt-2 min-h-[60px] group-hover:bg-primary/20 transition-colors"></div>
            {/if}
          </div>
          
          <!-- Right: Phase Content -->
          <div class="flex-1 pb-4 transition-all duration-300 group-hover:translate-x-1">
            <h3 class="font-bold text-lg mb-2 group-hover:text-primary transition-colors">
              {phase.name}
            </h3>
            <p class="text-sm text-muted-foreground mb-3">
              {phase.description}
            </p>
            <span 
              class="inline-block text-xs font-medium px-3 py-1 rounded-full"
              style="background-color: {phase.color}15; color: {phase.color}"
            >
              {phase.office}
            </span>
          </div>
        </div>
      {/each}
    </div>
  </div>
</section>
