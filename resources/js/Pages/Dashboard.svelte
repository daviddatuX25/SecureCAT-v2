<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import { Card, CardContent } from '@/Components/ui/card';
  import StatCard from '@/Components/StatCard.svelte';
  import {
    Users,
    Settings,
    FileText,
    BookOpen,
    DoorOpen,
    Calendar,
    GraduationCap,
    MessageSquare,
    ClipboardList,
    FileQuestion,
    PlusCircle,
    UploadCloud,
    FileCheck,
    Printer,
    Sparkles,
  } from 'lucide-svelte';

  let { user, stats } = $props();
  const breadcrumbs = [{ label: 'Dashboard' }];
  const safeStats = $derived(Array.isArray(stats) ? stats : []);
  const page = usePage();
  const authUser = $derived($page.props.auth?.user ?? null);
  const roles = $derived(authUser?.roles?.map((r) => r.name) ?? user?.roles?.map((r) => r.name) ?? []);

  const iconMap = {
    applications_pending: FileText,
    exam_sessions: Calendar,
    rooms: DoorOpen,
    grading_sessions_active: ClipboardList,
    exams_without_grading: FileQuestion,
    consultation_pending: MessageSquare,
    my_sessions_upcoming: Calendar,
  };

  const accentMap = {
    applications_pending: 'primary',
    exam_sessions: 'blue',
    rooms: 'orange',
    grading_sessions_active: 'primary',
    exams_without_grading: 'purple',
    consultation_pending: 'primary',
    my_sessions_upcoming: 'blue',
  };

  function hasRole(r) {
    return roles.includes(r);
  }

  function statIcon(key) {
    return iconMap[key] ?? FileText;
  }

  function statAccent(key) {
    return accentMap[key] ?? 'primary';
  }

  const statHrefs = $derived(new Set(safeStats.map((s) => s.href).filter(Boolean)));

  const quickLinksRaw = $derived([
    hasRole('super_admin') && { href: '/admin/users', label: 'Users', icon: Users },
    hasRole('super_admin') && { href: '/admin/settings', label: 'Settings', icon: Settings },
    (hasRole('admin') || hasRole('staff') || hasRole('test_administrator')) && { href: '/applications', label: 'Applications', icon: FileText },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/courses', label: 'Courses', icon: BookOpen },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/rooms', label: 'Rooms', icon: DoorOpen },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/test-scheduling', label: 'Exam Scheduling', icon: Calendar },
    hasRole('proctor') && { href: '/admin/test-scheduling', label: 'My Sessions', icon: Calendar },
    (hasRole('super_admin') || hasRole('test_administrator')) && { href: '/grading', label: 'Grading', icon: GraduationCap },
    (hasRole('super_admin') || hasRole('test_administrator')) && { href: '/consultation', label: 'Consultation', icon: MessageSquare },
  ].filter(Boolean));

  const quickLinks = $derived(quickLinksRaw.filter((link) => !statHrefs.has(link.href)));

  const quickActionsRaw = $derived([
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/test-scheduling/create', label: 'New Session', icon: PlusCircle },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/knowledge-documents/import', label: 'Import Data', icon: UploadCloud },
    (hasRole('super_admin') || hasRole('grader')) && { href: '/grading', label: 'Grade Sheets', icon: FileCheck },
    (hasRole('admin') || hasRole('staff') || hasRole('counselor')) && { href: '/applications/print-slips', label: 'Print Slips', icon: Printer },
  ].filter(Boolean));

  const quickActions = $derived(quickActionsRaw.filter((action) => !statHrefs.has(action.href)));

  const showAiExamScheduler = $derived(hasRole('super_admin') || hasRole('admin'));
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-8 min-w-0">
    <p class="text-muted-foreground">
      Welcome back, {user?.name ?? 'User'}.
    </p>

    {#if safeStats.length > 0}
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-6">
        {#each safeStats as stat (stat.key)}
          {@const IconComponent = statIcon(stat.key)}
          <StatCard
            label={stat.label}
            value={stat.value}
            icon={IconComponent}
            href={stat.href}
            accent={statAccent(stat.key)}
          />
        {/each}
      </div>
    {/if}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-6">
        {#if quickLinks.length > 0}
          <div class="glass-panel p-6 rounded-2xl">
            <h2 class="text-lg font-bold mb-4 text-foreground">Quick links</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {#each quickLinks as link}
                <Link
                  href={link.href}
                  class="flex min-h-[44px] items-center gap-3 rounded-xl border border-border/50 p-4 transition-all hover:bg-primary hover:text-primary-foreground hover:border-primary"
                >
                  <link.icon class="h-6 w-6 shrink-0" />
                  <span class="font-medium">{link.label}</span>
                </Link>
              {/each}
            </div>
          </div>
        {/if}
      </div>

      <div class="space-y-6">
        {#if quickActions.length > 0}
          <div class="glass-panel p-6 rounded-2xl">
            <h2 class="text-lg font-bold mb-4 text-foreground">Quick Actions</h2>
            <div class="grid grid-cols-2 gap-4">
              {#each quickActions as action}
                <Link
                  href={action.href}
                  class="flex flex-col items-center justify-center p-4 rounded-xl border border-border/50 transition-all min-h-[44px] hover:bg-primary hover:text-primary-foreground hover:border-primary group"
                >
                  <action.icon class="w-6 h-6 mb-2 group-hover:scale-110 transition-transform" />
                  <span class="text-xs font-semibold text-center">{action.label}</span>
                </Link>
              {/each}
            </div>
          </div>
        {/if}

        {#if showAiExamScheduler}
          <div class="rounded-2xl border border-border bg-muted/50 p-6">
            <div class="flex items-center gap-2 mb-2">
              <Sparkles class="h-5 w-5 text-primary" />
              <h3 class="font-bold text-lg text-foreground">AI Exam Scheduler</h3>
            </div>
            <p class="text-sm text-muted-foreground mb-4 line-clamp-2">
              Plan exam sessions with AI: describe dates, rooms, and capacity. The assistant suggests a schedule; apply to create sessions and assign applicants.
            </p>
            <Link
              href="/admin/test-scheduling?open=schedule-assistant"
              class="inline-flex items-center justify-center rounded-lg border border-primary bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition-colors w-full"
            >
              Open AI Scheduler
            </Link>
          </div>
        {/if}
      </div>
    </div>

    {#if safeStats.length === 0 && quickLinks.length === 0 && quickActions.length === 0 && !showAiExamScheduler}
      <Card variant="glass" class="p-6">
        <CardContent class="pt-0">
          <p class="text-muted-foreground">
            Use the sidebar to navigate.
          </p>
        </CardContent>
      </Card>
    {/if}
  </div>
</AuthenticatedLayout>
