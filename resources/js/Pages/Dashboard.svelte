<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import { Users, Settings, FileText, BookOpen, DoorOpen, ClipboardCheck, Calendar, GraduationCap, MessageSquare } from 'lucide-svelte';

  let { user, stats } = $props();
  const page = usePage();
  const authUser = $derived($page.props.auth?.user ?? null);
  const roles = $derived(authUser?.roles?.map((r) => r.name) ?? user?.roles?.map((r) => r.name) ?? []);

  function hasRole(r) {
    return roles.includes(r);
  }

  const quickLinks = $derived([
    hasRole('super_admin') && { href: '/admin/users', label: 'Users', icon: Users },
    hasRole('super_admin') && { href: '/admin/settings', label: 'Settings', icon: Settings },
    (hasRole('admin') || hasRole('staff') || hasRole('counselor')) && { href: '/applications', label: 'Applications', icon: FileText },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/courses', label: 'Courses', icon: BookOpen },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/rooms', label: 'Rooms', icon: DoorOpen },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/proctors', label: 'Proctors', icon: ClipboardCheck },
    (hasRole('super_admin') || hasRole('admin')) && { href: '/admin/exam-sessions', label: 'Exam Sessions', icon: Calendar },
    (hasRole('super_admin') || hasRole('admin') || hasRole('proctor')) && { href: '/proctor', label: 'My Sessions', icon: Calendar },
    (hasRole('super_admin') || hasRole('grader')) && { href: '/grading', label: 'Grading', icon: GraduationCap },
    (hasRole('super_admin') || hasRole('counselor')) && { href: '/consultation', label: 'Consultation', icon: MessageSquare },
  ].filter(Boolean));
</script>

<svelte:head>
  <title>Dashboard - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold">Dashboard</h1>
      <p class="mt-1 text-muted-foreground">
        Welcome back, {user?.name ?? 'User'}.
      </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      {#if stats}
        {#each Object.entries(stats) as [key, value]}
          <div class="rounded-lg border border-border bg-card p-6">
            <p class="text-sm font-medium text-muted-foreground">{key}</p>
            <p class="mt-2 text-2xl font-bold">{value}</p>
          </div>
        {/each}
      {/if}
      {#if quickLinks.length > 0}
        {#each quickLinks as link}
          <Link
            href={link.href}
            class="flex items-center gap-3 rounded-lg border border-border bg-card p-6 transition-colors hover:bg-accent"
          >
            <link.icon class="h-6 w-6 text-muted-foreground" />
            <span class="font-medium">{link.label}</span>
          </Link>
        {/each}
      {:else}
        <div class="rounded-lg border border-border bg-card p-6">
          <p class="text-sm font-medium text-muted-foreground">Quick start</p>
          <p class="mt-2 text-muted-foreground">
            Use the sidebar to navigate. Stats will appear here when available.
          </p>
        </div>
      {/if}
    </div>
  </div>
</AuthenticatedLayout>
