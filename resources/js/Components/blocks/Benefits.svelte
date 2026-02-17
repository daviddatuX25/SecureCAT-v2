<script>
  import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
  import { Users, Sparkles, BarChart3, CheckCircle2, TrendingUp, FolderOpen } from 'lucide-svelte';
  
  let { 
    benefits = [
      {
        title: 'Eliminate Crowding',
        description: 'Appointment booking system prevents long wait times and crowding at the registrar counter during peak admission periods',
        icon: Users,
        phase: 'Phase 1'
      },
      {
        title: 'AI-Assisted Scheduling',
        description: 'Intelligent schedule generation resolves room conflicts, matches capacity to applicant count, and distributes proctors efficiently',
        icon: Sparkles,
        phase: 'Phase 2'
      },
      {
        title: 'Real-Time Monitoring',
        description: 'Live session status visibility for supervisors with timestamped attendance and submission logging to prevent disputes',
        icon: BarChart3,
        phase: 'Phase 3'
      },
      {
        title: 'Automated Grading',
        description: 'OMR integration for rapid answer sheet scanning with manual override capabilities and score normalization',
        icon: CheckCircle2,
        phase: 'Phase 4'
      },
      {
        title: 'Data-Driven Consultation',
        description: 'Heatmap analytics and decision support for counselors with historical benchmarking and enrollment context',
        icon: TrendingUp,
        phase: 'Phase 5'
      },
      {
        title: 'Unified Digital Records',
        description: 'Centralized, auditable records replacing fragmented paper-based systems with tamper-evident audit trails',
        icon: FolderOpen,
        phase: 'All Phases'
      }
    ]
  } = $props();
</script>

<section id="benefits" class="py-16 md:py-24 px-4 bg-muted/30">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-bold mb-4">
        Key Benefits
      </h2>
      <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
        SecureCAT addresses critical pain points in the admission process with modern digital solutions
      </p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {#each benefits as benefit}
        <Card class="hover:shadow-lg transition-shadow duration-300">
          <CardHeader>
            <div class="flex items-start gap-3">
              <div class="p-2 rounded-lg bg-primary/10 text-primary" aria-label={benefit.title}>
                <svelte:component this={benefit.icon} class="w-6 h-6" />
              </div>
              <div class="flex-1">
                <CardTitle class="text-xl mb-1">
                  {benefit.title}
                </CardTitle>
                <span class="text-xs font-medium text-primary">
                  {benefit.phase}
                </span>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-muted-foreground leading-relaxed">
              {benefit.description}
            </p>
          </CardContent>
        </Card>
      {/each}
    </div>
  </div>
</section>
