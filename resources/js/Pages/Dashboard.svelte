<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import KpiCard from '@/Components/KpiCard.svelte';
  import { FileText, Calendar, GraduationCap, SendHorizonal, Users, DoorOpen, BookOpen, Sparkles } from 'lucide-svelte';

  let { user, applicationStats, sessionStats, gradingStats } = $props();

  const breadcrumbs = [{ label: 'Dashboard' }];
  const page = usePage();
  const authUser = $derived($page.props.auth?.user ?? null);
  const roles = $derived(authUser?.roles?.map((r) => r.name) ?? user?.roles?.map((r) => r.name) ?? []);

  function hasRole(r) {
    return roles.includes(r);
  }

  const safeApplicationStats = $derived(Array.isArray(applicationStats) ? applicationStats : []);
  const safeSessionStats = $derived(Array.isArray(sessionStats) ? sessionStats : []);
  const safeGradingStats = $derived(Array.isArray(gradingStats) ? gradingStats : []);

  // Quick actions per role — no "Print Admission Slip"
  const quickActions = $derived([
    (hasRole('admin') || hasRole('super_admin')) && { href: '/applications', label: 'View Applications', icon: FileText },
    (hasRole('proctor') || hasRole('test_administrator') || hasRole('super_admin')) && { href: '/admin/test-scheduling', label: 'My Sessions', icon: Calendar },
    (hasRole('test_administrator') || hasRole('super_admin')) && { href: '/grading', label: 'Grading', icon: GraduationCap },
    (hasRole('test_administrator') || hasRole('super_admin')) && { href: '/release', label: 'Release Results', icon: SendHorizonal },
    (hasRole('admin') || hasRole('super_admin')) && { href: '/admin/users', label: 'Manage Users', icon: Users },
  ].filter(Boolean));

  const showAiExamScheduler = $derived(hasRole('super_admin') || hasRole('admin'));
  const showInstitutionInfo = $derived(hasRole('super_admin') || hasRole('admin'));
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-8 min-w-0">
    <p class="text-muted-foreground">Welcome back, {user?.name ?? 'User'}.</p>

    <!-- Application KPIs — admin / super_admin -->
    {#if (hasRole('admin') || hasRole('super_admin')) && safeApplicationStats.length > 0}
      <section>
        <h2 class="mb-4 text-base font-semibold text-foreground">Applications</h2>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
          {#each safeApplicationStats as stat (stat.key)}
            <KpiCard
              label={stat.label}
              value={stat.value}
              href={stat.href}
            />
          {/each}
        </div>
      </section>
    {/if}

    <!-- Session KPIs — proctor / test_administrator / super_admin -->
    {#if (hasRole('proctor') || hasRole('test_administrator') || hasRole('super_admin')) && safeSessionStats.length > 0}
      <section>
        <h2 class="mb-4 text-base font-semibold text-foreground">Sessions</h2>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
          {#each safeSessionStats as stat (stat.key)}
            <KpiCard
              label={stat.label}
              value={stat.value}
              href={stat.href}
            />
          {/each}
        </div>
      </section>
    {/if}

    <!-- Grading + Release KPIs — test_administrator / super_admin -->
    {#if (hasRole('test_administrator') || hasRole('super_admin')) && safeGradingStats.length > 0}
      <section>
        <h2 class="mb-4 text-base font-semibold text-foreground">Grading & Release</h2>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
          {#each safeGradingStats as stat (stat.key)}
            <KpiCard
              label={stat.label}
              value={stat.value}
              href={stat.href}
            />
          {/each}
        </div>
      </section>
    {/if}

    <!-- Quick Actions -->
    {#if quickActions.length > 0}
      <section>
        <div class="glass-panel p-6 rounded-2xl">
          <h2 class="text-lg font-bold mb-4 text-foreground">Quick Actions</h2>
          <div class="flex flex-wrap gap-3">
            {#each quickActions as action}
              <Link
                href={action.href}
                class="inline-flex items-center gap-2 rounded-xl border border-border/60 px-4 py-2 text-sm font-medium text-foreground hover:bg-muted transition-colors min-h-[44px]"
              >
                <action.icon class="h-4 w-4 shrink-0" />
                {action.label}
              </Link>
            {/each}
          </div>
        </div>
      </section>
    {/if}

    <!-- Institution Information — admin / super_admin (2.5) -->
    {#if showInstitutionInfo}
      <section>
        <div class="glass-panel p-6 rounded-2xl">
          <h2 class="text-lg font-bold mb-4 text-foreground">Institution Information</h2>
          <div class="flex flex-wrap gap-3">
            <Link
              href="/admin/rooms"
              class="inline-flex items-center gap-2 rounded-xl border border-border/60 px-4 py-2 text-sm font-medium text-foreground hover:bg-muted transition-colors min-h-[44px]"
            >
              <DoorOpen class="h-4 w-4 shrink-0" />
              Room Management
            </Link>
            <Link
              href="/admin/courses"
              class="inline-flex items-center gap-2 rounded-xl border border-border/60 px-4 py-2 text-sm font-medium text-foreground hover:bg-muted transition-colors min-h-[44px]"
            >
              <BookOpen class="h-4 w-4 shrink-0" />
              Course Management
            </Link>
          </div>
        </div>
      </section>
    {/if}

    <!-- AI Exam Scheduler promo — admin / super_admin -->
    {#if showAiExamScheduler}
      <section>
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
      </section>
    {/if}

    {#if safeApplicationStats.length === 0 && safeSessionStats.length === 0 && safeGradingStats.length === 0 && quickActions.length === 0}
      <p class="text-muted-foreground">Use the sidebar to navigate.</p>
    {/if}
  </div>
</AuthenticatedLayout>