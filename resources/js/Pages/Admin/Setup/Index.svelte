<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import {
    CalendarRange,
    BookOpen,
    DoorOpen,
    Brain,
    FileText,
    Shield,
    Bot,
    Settings,
    Ticket,
  } from 'lucide-svelte';

  let { allowDirectAssessment = false, aiCompanionEnabled = false } = $props();

  const breadcrumbs = [{ label: 'Setup' }];
  const page = usePage();
  const roles = $derived(
    $page.props.auth?.user?.roles?.map((r) => r.name) ?? []
  );

  function hasRole(r) {
    return roles.includes(r);
  }

  const setupCards = $derived(
    [
      {
        href: '/admin/academic-years',
        label: 'Academic Years',
        description: 'Manage academic year periods and application windows.',
        icon: CalendarRange,
        roles: ['super_admin', 'registrar_administrator'],
      },
      {
        href: '/admin/courses',
        label: 'Programs & Courses',
        description: 'Configure available courses and program offerings.',
        icon: BookOpen,
        roles: ['super_admin', 'registrar_administrator'],
      },
      {
        href: '/admin/rooms',
        label: 'Rooms & Facilities',
        description: 'Manage assessment rooms and seating capacity.',
        icon: DoorOpen,
        roles: ['super_admin', 'registrar_administrator'],
      },
      {
        href: '/admin/aptitude-areas',
        label: 'Aptitude Areas',
        description:
          'Define scoring domains, weights, and computation formulas.',
        icon: Brain,
        roles: ['super_admin', 'test_administrator'],
      },
      {
        href: '/admin/release/result-templates',
        label: 'Result Sheet Templates',
        description: 'Design and manage result sheet print templates.',
        icon: FileText,
        roles: ['super_admin', 'test_administrator'],
      },
      {
        href: '/admin/admission-slip-templates',
        label: 'Admission Slip Templates',
        description: 'Configure admission slip layout and content.',
        icon: Ticket,
        roles: ['super_admin'],
      },
      {
        href: '/admin/privacy-policies',
        label: 'Privacy Policies',
        description:
          'Manage privacy policy versions shown on the application form.',
        icon: Shield,
        roles: ['super_admin', 'registrar_administrator'],
      },
      {
        href: '/admin/ai-companion',
        label: 'AI Companion',
        description:
          'Configure AI advisor persona, knowledge base, and feature toggle.',
        icon: Bot,
        roles: ['super_admin'],
        badge: aiCompanionEnabled ? 'Active' : null,
      },
      {
        href: '/admin/settings',
        label: 'System Settings',
        description: 'Feature toggles, release mode, and system-wide configuration.',
        icon: Settings,
        roles: ['super_admin'],
      },
    ].filter((card) => card.roles.some((r) => hasRole(r)))
  );
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div>
      <p class="text-sm text-muted-foreground">
        Configure reference data, templates, and system-wide settings.
      </p>
    </div>

    {#if setupCards.length > 0}
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {#each setupCards as card}
          <Link
            href={card.href}
            class="group relative flex flex-col gap-3 rounded-2xl border border-border bg-card p-6 transition-all hover:border-primary/30 hover:shadow-md hover:shadow-primary/5 hover:-translate-y-0.5"
          >
            <div class="flex items-start justify-between">
              <div
                class="rounded-xl bg-muted p-2.5 transition-colors group-hover:bg-primary/10"
              >
                <card.icon
                  class="h-5 w-5 text-muted-foreground transition-colors group-hover:text-primary"
                />
              </div>
              {#if card.badge}
                <span
                  class="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                >
                  {card.badge}
                </span>
              {/if}
            </div>
            <div>
              <h3 class="font-semibold text-foreground">{card.label}</h3>
              <p class="mt-1 text-sm text-muted-foreground leading-relaxed">
                {card.description}
              </p>
            </div>
            <div class="mt-auto pt-2">
              <span
                class="text-sm font-medium text-primary opacity-0 transition-opacity group-hover:opacity-100"
              >
                Configure →
              </span>
            </div>
          </Link>
        {/each}
      </div>
    {:else}
      <p class="py-12 text-center text-muted-foreground">
        No setup options available for your role.
      </p>
    {/if}
  </div>
</AuthenticatedLayout>
