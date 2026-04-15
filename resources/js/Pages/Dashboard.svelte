<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import KpiCard from '@/Components/KpiCard.svelte';
  import AreaChart from '@/Components/AreaChart.svelte';
  import PieChart from '@/Components/PieChart.svelte';
  import * as Tabs from '@/Components/ui/tabs';
  import { FileText, Calendar, GraduationCap, SendHorizonal, Users, DoorOpen, BookOpen, Sparkles } from 'lucide-svelte';

  let { user, applicationStats, sessionStats, gradingStats, analytics, myActivity } = $props();

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

  // Analytics data (guarded)
  const appAnalytics = $derived(analytics?.applications ?? null);
  const sessionAnalytics = $derived(analytics?.sessions ?? null);
  const gradingAnalytics = $derived(analytics?.grading ?? null);
  const userAnalytics = $derived(analytics?.users ?? null);

  // ─── Role visibility flags ─────────────────────────────────────────────────

  const showAppAnalytics = $derived(
    hasRole('super_admin') || hasRole('admin') || hasRole('registrar_administrator')
  );
  const showSessionAnalytics = $derived(
    hasRole('super_admin') || hasRole('proctor') || hasRole('registrar_administrator') || hasRole('test_administrator')
  );
  const showGradingAnalytics = $derived(
    hasRole('super_admin') || hasRole('registrar_administrator') || hasRole('test_administrator')
  );
  const showUserAnalytics = $derived(hasRole('super_admin') || hasRole('admin'));

  // Quick actions per role
  const quickActions = $derived([
    (hasRole('admin') || hasRole('super_admin')) && { href: '/admin/applications', label: 'View Applications', icon: FileText },
    (hasRole('proctor') || hasRole('registrar_administrator') || hasRole('super_admin')) && { href: '/admin/exam-scheduling', label: 'My Sessions', icon: Calendar },
    (hasRole('registrar_administrator') || hasRole('super_admin')) && { href: '/grading', label: 'Grading', icon: GraduationCap },
    (hasRole('registrar_administrator') || hasRole('super_admin')) && { href: '/release', label: 'Release Results', icon: SendHorizonal },
    (hasRole('admin') || hasRole('super_admin')) && { href: '/admin/users', label: 'Manage Users', icon: Users },
  ].filter(Boolean));

  const showAiExamScheduler = $derived(hasRole('super_admin') || hasRole('admin'));
  const showInstitutionInfo = $derived(hasRole('super_admin') || hasRole('admin'));

  const standardTabs = $derived([
    (hasRole('admin') || hasRole('super_admin') || hasRole('registrar_administrator')) && { value: 'applications', label: 'Applications' },
    (hasRole('proctor') || hasRole('registrar_administrator') || hasRole('super_admin') || hasRole('test_administrator')) && { value: 'sessions', label: 'Sessions' },
    (hasRole('registrar_administrator') || hasRole('super_admin') || hasRole('test_administrator')) && { value: 'grading', label: 'Grading' },
    (hasRole('super_admin') || hasRole('admin')) && { value: 'users', label: 'Users' },
  ].filter(Boolean));

  // "My Activity" always shown for any authenticated user (appended last)
  const activeTabs = $derived([...standardTabs, { value: 'my_activity', label: 'My Activity' }]);

  // ─── Chart data helpers ───────────────────────────────────────────────────

  /**
   * Build a stacked-area dataset for two series (scheduled vs completed).
   * AreaChart only takes one `values` array, so we show scheduled + overlay
   * info in the subtitle. For true dual-series we'd need two charts; we
   * pass the sum as the primary value and derive the second from labels.
   */
  function buildDualAreaData(trends) {
    if (! trends?.labels?.length) return null;
    const scheduled = trends.scheduled ?? [];
    const completed = trends.completed ?? [];
    // Show scheduled as the primary line; completed implied by difference
    const values = scheduled.map((s, i) => s + (completed[i] ?? 0));
    return {
      labels: trends.labels,
      values,
      trendDelta: completed.reduce((a, b) => a + b, 0),
      trendLabel: 'completed',
    };
  }

  function buildAttendanceData(attendance) {
    if (! attendance?.labels?.length) return null;
    // Show present as primary; absent shown as delta
    const present = attendance.present ?? [];
    return {
      labels: attendance.labels,
      values: present,
      trendDelta: (attendance.absent ?? []).reduce((a, b) => a + b, 0),
      trendLabel: 'absent',
    };
  }

  function pieNotEmpty(pieData) {
    return Array.isArray(pieData) && pieData.length > 0;
  }
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-8 min-w-0">
    <!-- ─── Overview & Welcome ────────────────────────────────────────────── -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <p class="text-muted-foreground text-lg mb-1">Welcome back, {user?.name ?? 'User'}.</p>
        <h1 class="text-3xl font-bold tracking-tight text-foreground">Dashboard</h1>
      </div>
    </div>

    <!-- ─── Global Top Actions (Grid) ─────────────────────────────────── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- AI Scheduler -->
      {#if showAiExamScheduler}
        <Link href="/admin/exam-scheduling?open=schedule-assistant" class="group relative overflow-hidden flex flex-col justify-between rounded-2xl border border-primary/20 bg-primary/5 p-6 hover:bg-primary/10 transition-colors h-full">
          <div>
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-bold text-foreground">AI Exam Scheduler</h3>
              <Sparkles class="h-5 w-5 text-primary" />
            </div>
            <p class="text-sm text-muted-foreground mb-4">Plan exam sessions with the AI advisor.</p>
          </div>
          <div class="inline-flex items-center text-sm font-semibold text-primary group-hover:underline mt-auto">Open Assistant &rarr;</div>
        </Link>
      {/if}

      <!-- Institute Info -->
      {#if showInstitutionInfo}
        <Link href="/admin/rooms" class="group relative overflow-hidden flex flex-col justify-between rounded-2xl border border-border bg-card p-6 hover:bg-muted/50 transition-colors h-full">
          <div>
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-bold text-foreground">Facilities</h3>
              <DoorOpen class="h-5 w-5 text-muted-foreground" />
            </div>
            <p class="text-sm text-muted-foreground mb-4">Manage assessment rooms and capacity.</p>
          </div>
          <div class="inline-flex items-center text-sm font-medium text-foreground group-hover:underline mt-auto">Manage &rarr;</div>
        </Link>

        <Link href="/admin/courses" class="group relative overflow-hidden flex flex-col justify-between rounded-2xl border border-border bg-card p-6 hover:bg-muted/50 transition-colors h-full">
          <div>
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-bold text-foreground">Programs</h3>
              <BookOpen class="h-5 w-5 text-muted-foreground" />
            </div>
            <p class="text-sm text-muted-foreground mb-4">Manage academic courses & programs.</p>
          </div>
          <div class="inline-flex items-center text-sm font-medium text-foreground group-hover:underline mt-auto">Manage &rarr;</div>
        </Link>
      {/if}

      <!-- Other Quick Actions -->
      {#each quickActions as action}
        <Link href={action.href} class="group relative overflow-hidden flex flex-col justify-between rounded-2xl border border-border bg-card p-6 hover:bg-muted/50 transition-colors h-full">
          <div>
            <div class="flex items-center gap-3 mb-3">
              <div class="rounded-lg bg-muted p-2"><action.icon class="h-5 w-5 text-foreground" /></div>
              <h3 class="font-bold text-foreground">{action.label}</h3>
            </div>
          </div>
          <p class="text-sm font-medium text-foreground mt-4 group-hover:underline mt-auto">Go to {action.label} &rarr;</p>
        </Link>
      {/each}
    </div>

    <!-- ─── Tabs Layout ────────────────────────────────────────────── -->
    {#if activeTabs.length > 0}
      <Tabs.Root value={activeTabs[0].value} class="w-full mt-6">
        <div class="overflow-x-auto pb-2 mb-6 scrollbar-none">
          <Tabs.List class="w-full sm:w-auto inline-flex h-11">
            {#each activeTabs as tab}
              <Tabs.Trigger value={tab.value} class="px-6 data-[state=active]:bg-primary/10 data-[state=active]:text-primary">{tab.label}</Tabs.Trigger>
            {/each}
          </Tabs.List>
        </div>

        <!-- ─── Applications Tab ─────────────────────────────────── -->
        {#if activeTabs.find(t => t.value === 'applications')}
          <Tabs.Content value="applications" class="space-y-8 mt-0 focus-visible:outline-none focus-visible:ring-0">
            {#if safeApplicationStats.length > 0}
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {#each safeApplicationStats as stat (stat.key)}
                  <KpiCard label={stat.label} value={stat.value} href={stat.href} />
                {/each}
              </div>
            {/if}

            {#if showAppAnalytics && appAnalytics}
              <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Submission Trends -->
                {#if appAnalytics.trends?.labels?.length}
                  <div class="xl:col-span-2">
                    <AreaChart
                      title="Submission Trends"
                      subtitle="Applications submitted per day (last 30 days)"
                      labels={appAnalytics.trends.labels}
                      values={appAnalytics.trends.values}
                      height={280}
                    />
                  </div>
                {/if}

                <div class="space-y-6">
                  <!-- Status Distribution -->
                  {#if pieNotEmpty(appAnalytics.statusDistribution)}
                    <PieChart
                      title="Status Breakdown"
                      data={appAnalytics.statusDistribution}
                      size={180}
                    />
                  {/if}

                  <!-- Course Preferences -->
                  {#if pieNotEmpty(appAnalytics.coursePreferences)}
                    <PieChart
                      title="Top Course Preferences"
                      data={appAnalytics.coursePreferences}
                      size={160}
                    />
                  {/if}
                </div>
              </div>
            {/if}
          </Tabs.Content>
        {/if}

        <!-- ─── Sessions Tab ─────────────────────────────────────── -->
        {#if activeTabs.find(t => t.value === 'sessions')}
          <Tabs.Content value="sessions" class="space-y-8 mt-0 focus-visible:outline-none focus-visible:ring-0">
            {#if safeSessionStats.length > 0}
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {#each safeSessionStats as stat (stat.key)}
                  <KpiCard label={stat.label} value={stat.value} href={stat.href} />
                {/each}
              </div>
            {/if}

            {#if showSessionAnalytics && sessionAnalytics}
              <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                {#if sessionAnalytics.trends?.labels?.length}
                  {@const trendData = buildDualAreaData(sessionAnalytics.trends)}
                  {#if trendData}
                    <AreaChart
                      title="Session Trends"
                      subtitle="Scheduled vs completed sessions per week"
                      labels={trendData.labels}
                      values={trendData.values}
                      height={200}
                      trendDelta={trendData.trendDelta}
                      trendLabel="completed"
                    />
                  {/if}
                {/if}

                {#if sessionAnalytics.attendance?.labels?.length}
                  {@const attData = buildAttendanceData(sessionAnalytics.attendance)}
                  {#if attData}
                    <AreaChart
                      title="Attendance"
                      subtitle="Present vs absent per week"
                      labels={attData.labels}
                      values={attData.values}
                      height={200}
                      trendDelta={attData.trendDelta}
                      trendLabel="absent"
                    />
                  {/if}
                {/if}

                {#if pieNotEmpty(sessionAnalytics.statusDistribution)}
                  <div class="xl:col-span-2 max-w-sm mx-auto w-full">
                    <PieChart
                      title="Session Status"
                      data={sessionAnalytics.statusDistribution}
                      size={180}
                    />
                  </div>
                {/if}
              </div>
            {/if}
          </Tabs.Content>
        {/if}

        <!-- ─── Grading Tab ──────────────────────────────────────── -->
        {#if activeTabs.find(t => t.value === 'grading')}
          <Tabs.Content value="grading" class="space-y-8 mt-0 focus-visible:outline-none focus-visible:ring-0">
            {#if safeGradingStats.length > 0}
              <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {#each safeGradingStats as stat (stat.key)}
                  <KpiCard label={stat.label} value={stat.value} href={stat.href} />
                {/each}
              </div>
            {/if}

            {#if showGradingAnalytics && gradingAnalytics}
              <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                {#if gradingAnalytics.turnaround?.labels?.length}
                  <div class="xl:col-span-2">
                    <AreaChart
                      title="Grading Turnaround"
                      subtitle="Avg. days to finalize per week"
                      labels={gradingAnalytics.turnaround.labels}
                      values={gradingAnalytics.turnaround.values}
                      height={240}
                    />
                  </div>
                {/if}

                {#if pieNotEmpty(gradingAnalytics.statusDistribution)}
                  <PieChart
                    title="Grading Status"
                    data={gradingAnalytics.statusDistribution}
                    size={200}
                  />
                {/if}
              </div>
            {/if}
          </Tabs.Content>
        {/if}

        <!-- ─── Users Tab ────────────────────────────────────────── -->
        {#if activeTabs.find(t => t.value === 'users')}
          <Tabs.Content value="users" class="space-y-8 mt-0 focus-visible:outline-none focus-visible:ring-0">
            {#if showUserAnalytics && userAnalytics}
              <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                {#if userAnalytics.growth?.labels?.length}
                  <div class="xl:col-span-2">
                    <AreaChart
                      title="User Growth"
                      subtitle="New registrations per month"
                      labels={userAnalytics.growth.labels}
                      values={userAnalytics.growth.values}
                      height={280}
                    />
                  </div>
                {/if}

                {#if pieNotEmpty(userAnalytics.roleDistribution)}
                  <PieChart
                    title="Users by Role"
                    data={userAnalytics.roleDistribution}
                    size={220}
                  />
                {/if}
              </div>
            {/if}
          </Tabs.Content>
        {/if}

        <!-- ─── My Activity Tab (fallback for unrecognised roles) ─ -->
        {#if activeTabs.find(t => t.value === 'my_activity')}
          <Tabs.Content value="my_activity" class="space-y-6 mt-0 focus-visible:outline-none focus-visible:ring-0">
            <div class="rounded-2xl border border-border bg-card">
              <div class="px-6 py-4 border-b border-border">
                <h2 class="font-semibold text-foreground">My Activity</h2>
                <p class="text-sm text-muted-foreground mt-0.5">Your recent actions in the system.</p>
              </div>
              {#if Array.isArray(myActivity) && myActivity.length > 0}
                <ul class="divide-y divide-border">
                  {#each myActivity as entry (entry.created_at)}
                    <li class="flex items-start gap-4 px-6 py-4">
                      <div class="mt-0.5 shrink-0">
                        <div class="h-2 w-2 rounded-full bg-primary mt-2"></div>
                      </div>
                      <div class="min-w-0 flex-1">
                        <p class="text-sm text-foreground">{entry.summary || entry.event}</p>
                        <p class="text-xs text-muted-foreground mt-0.5 capitalize">{entry.category?.replace('_', ' ')} &middot; {new Date(entry.created_at).toLocaleString()}</p>
                      </div>
                    </li>
                  {/each}
                </ul>
              {:else}
                <div class="px-6 py-12 text-center text-sm text-muted-foreground">
                  No activity recorded yet.
                </div>
              {/if}
            </div>
          </Tabs.Content>
        {/if}
      </Tabs.Root>
    {/if}

    {#if safeApplicationStats.length === 0 && safeSessionStats.length === 0 && safeGradingStats.length === 0 && quickActions.length === 0}
      <p class="text-muted-foreground">Use the sidebar to navigate.</p>
    {/if}
  </div>
</AuthenticatedLayout>
