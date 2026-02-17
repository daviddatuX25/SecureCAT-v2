<script>
  import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card';
  
  let {
    perspectives = [
      {
        office: 'Registrar Office',
        description: 'Managing applicant intake and exam logistics',
        phases: [
          {
            number: 1,
            name: 'Application & Appointment',
            color: '#C8401A',
            features: [
              'Online appointment booking',
              'Walk-in application capture',
              'Application lookup and search',
              'Automatic account provisioning on acceptance'
            ]
          },
          {
            number: 2,
            name: 'Exam Scheduling',
            color: '#1A5FC8',
            features: [
              'AI-assisted schedule generation',
              'Room-capacity matching',
              'Proctor allocation and conflict checking',
              'Score release date configuration',
              'Schedule publication with auto-notifications'
            ]
          }
        ]
      },
      {
        office: 'Guidance Office',
        description: 'Managing exam execution and result counseling',
        phases: [
          {
            number: 3,
            name: 'Examination',
            color: '#1A9C55',
            features: [
              'Real-time attendance logging',
              'Timestamped submission records',
              'Live session monitoring dashboard',
              'Proctor signature confirmation'
            ]
          },
          {
            number: 4,
            name: 'Marking & Grading',
            color: '#8B2AC8',
            features: [
              'OMR auto-scanning (Beta)',
              'Manual grade input interface',
              'Score normalization and conversion',
              'Audit trail for all score changes'
            ]
          },
          {
            number: 5,
            name: 'Results & Consultation',
            color: '#C87A1A',
            features: [
              'Real-time course distribution heatmap',
              'AI-powered decision support',
              'Counselor override and comments',
              'Gated release to applicants',
              'Historical performance benchmarking'
            ]
          }
        ]
      }
    ]
  } = $props();
</script>

<section class="py-16 md:py-24 px-4 bg-background">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-bold mb-4">
        Office Perspectives
      </h2>
      <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
        How SecureCAT empowers both the Registrar Office and Guidance Office teams
      </p>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      {#each perspectives as perspective}
        <div class="space-y-6">
          <div class="text-center lg:text-left">
            <h3 class="text-2xl font-bold mb-2">
              {perspective.office}
            </h3>
            <p class="text-muted-foreground">
              {perspective.description}
            </p>
          </div>
          
          <div class="space-y-4">
            {#each perspective.phases as phase}
              <Card class="border-l-4 hover:shadow-md transition-shadow duration-300" style="border-left-color: {phase.color}">
                <CardHeader>
                  <div class="flex items-center gap-3">
                    <div 
                      class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                      style="background-color: {phase.color}"
                    >
                      {phase.number}
                    </div>
                    <CardTitle class="text-lg">
                      {phase.name}
                    </CardTitle>
                  </div>
                </CardHeader>
                <CardContent>
                  <ul class="space-y-2">
                    {#each phase.features as feature}
                      <li class="flex items-start gap-2 text-sm">
                        <span class="text-primary mt-0.5">•</span>
                        <span class="text-muted-foreground">{feature}</span>
                      </li>
                    {/each}
                  </ul>
                </CardContent>
              </Card>
            {/each}
          </div>
        </div>
      {/each}
    </div>
  </div>
</section>
