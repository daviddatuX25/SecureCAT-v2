<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
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
  } from 'lucide-svelte';

  let { user, stats } = $props();
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

  function hasRole(r) {
    return roles.includes(r);
  }

  function statIcon(key) {
    return iconMap[key] ?? FileText;
  }

  const quickLinks = $derived([
    hasRole('super_admin') && { href: '/admin/users', label: 'Users', icon: Users },
    hasRole('super_admin') && { href: '/admin/settings', label: 'Settings', icon: Settings },
    (hasRole('admin') || hasRole('staff') || hasRole('counselor')) && { href: '/applications', label: 'Applications', icon: FileText },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/courses', label: 'Courses', icon: BookOpen },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/rooms', label: 'Rooms', icon: DoorOpen },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/exam-sessions', label: 'Exam Sessions', icon: Calendar },
    hasRole('proctor') && { href: '/admin/exam-sessions', label: 'My Sessions', icon: Calendar },
    (hasRole('super_admin') || hasRole('grader')) && { href: '/grading', label: 'Grading', icon: GraduationCap },
    (hasRole('super_admin') || hasRole('counselor')) && { href: '/consultation', label: 'Consultation', icon: MessageSquare },
  ].filter(Boolean));
</script>

<svelte:head>
  <title>Dashboard - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <div>
      <h1 class="text-2xl font-bold">Dashboard</h1>
      <p class="mt-1 text-muted-foreground">
        Welcome back, {user?.name ?? 'User'}.
      </p>
    </div>

    {#if safeStats.length > 0}
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {#each safeStats as stat (stat.key)}
          {@const IconComponent = statIcon(stat.key)}
          {#if stat.href}
            <Link href={stat.href} class="block transition-opacity hover:opacity-90">
              <Card>
                <CardHeader class="pb-2">
                  <CardTitle class="text-sm font-medium text-muted-foreground">{stat.label}</CardTitle>
                </CardHeader>
                <CardContent>
                  <div class="flex items-center gap-2">
                    <IconComponent class="h-8 w-8 text-muted-foreground" />
                    <span class="text-2xl font-bold">{stat.value}</span>
                  </div>
                  <p class="mt-1 text-xs text-muted-foreground">View details</p>
                </CardContent>
              </Card>
            </Link>
          {:else}
            <Card>
              <CardHeader class="pb-2">
                <CardTitle class="text-sm font-medium text-muted-foreground">{stat.label}</CardTitle>
              </CardHeader>
              <CardContent>
                <div class="flex items-center gap-2">
                  <IconComponent class="h-8 w-8 text-muted-foreground" />
                  <span class="text-2xl font-bold">{stat.value}</span>
                </div>
              </CardContent>
            </Card>
          {/if}
        {/each}
      </div>
    {/if}

    {#if quickLinks.length > 0}
      <div>
        <h2 class="mb-3 text-lg font-semibold">Quick links</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {#each quickLinks as link}
            <Link
              href={link.href}
              class="flex min-h-[44px] items-center gap-3 rounded-lg border border-border bg-card p-6 transition-colors hover:bg-accent"
            >
              <link.icon class="h-6 w-6 shrink-0 text-muted-foreground" />
              <span class="font-medium">{link.label}</span>
            </Link>
          {/each}
        </div>
      </div>
    {:else if safeStats.length === 0}
      <Card>
        <CardContent class="pt-6">
          <p class="text-muted-foreground">
            Use the sidebar to navigate.
          </p>
        </CardContent>
      </Card>
    {/if}
  </div>
</AuthenticatedLayout>
