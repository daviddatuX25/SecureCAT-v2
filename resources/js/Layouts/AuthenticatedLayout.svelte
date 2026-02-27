<script>
  import { Link, router } from '@inertiajs/svelte';
  import { usePage } from '@inertiajs/svelte';
  import { ChevronDown, Menu, LayoutDashboard, Users, FileText, Calendar, DoorOpen, GraduationCap, BookOpen, Settings, MessageSquare, Gavel, CalendarCheck, UsersRound, ScrollText, FileStack, Activity, CalendarRange } from 'lucide-svelte';
  import { Button } from '@/Components/ui/button';

  let { children } = $props();

  const page = usePage();
  const user = $derived($page.props.auth?.user ?? null);
  const roles = $derived(user?.roles?.map((r) => r.name) ?? []);
  const consultationEnabled = $derived($page.props.consultation_enabled ?? true);
  let sidebarOpen = $state(false);
  let userDropdownOpen = $state(false);

  function hasRole(r) {
    return roles.includes(r);
  }

  function canSee(requiredRoles, item) {
    if (requiredRoles.includes('*')) return true;
    if (!requiredRoles.some((r) => hasRole(r))) return false;
    if (item?.requiresConsultation && !consultationEnabled) return false;
    return true;
  }

  function logout() {
    router.post('/logout');
  }

  const navSections = $derived([
    { label: null, items: [{ href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: ['*'] }] },
    { label: 'System', items: [
      { href: '/admin/users', label: 'Users', icon: Users, roles: ['super_admin'] },
      { href: '/admin/settings', label: 'Settings', icon: Settings, roles: ['super_admin'] },
      { href: '/admin/logs', label: 'Audit log', icon: ScrollText, roles: ['super_admin'] },
      { href: '/admin/knowledge-documents', label: 'Knowledge docs', icon: BookOpen, roles: ['super_admin'] },
      { href: '/admin/admission-slip-templates', label: 'Admission slip templates', icon: FileStack, roles: ['super_admin', 'admin'] },
    ]},
    { label: 'Registrar', items: [
      { href: '/admin/seasons', label: 'Seasons', icon: CalendarRange, roles: ['super_admin', 'admin'] },
      { href: '/applications', label: 'Applications', icon: FileText, roles: ['super_admin', 'staff', 'admin', 'counselor'] },
      { href: '/admin/courses', label: 'Courses', icon: BookOpen, roles: ['super_admin', 'admin'] },
      { href: '/admin/rooms', label: 'Rooms', icon: DoorOpen, roles: ['super_admin', 'admin'] },
      { href: '/admin/exam-sessions', label: 'Exam Sessions', icon: Calendar, roles: ['super_admin', 'admin'] },
    ]},
    { label: 'Guidance', items: [
      { href: '/admin/exam-sessions', label: 'My Sessions', icon: Calendar, roles: ['proctor'] },
      { href: '/admin/exam-sessions/monitoring', label: 'Session monitor', icon: Activity, roles: ['super_admin', 'admin', 'proctor'] },
      { href: '/grading', label: 'Grading', icon: GraduationCap, roles: ['super_admin', 'grader'] },
      { href: '/admin/result-sheet-templates', label: 'Result templates', icon: FileText, roles: ['super_admin', 'admin', 'counselor'] },
      { href: '/consultation', label: 'Consultation', icon: MessageSquare, roles: ['super_admin', 'counselor'], requiresConsultation: true },
      { href: '/consultation/rules', label: 'Decision rules', icon: Gavel, roles: ['super_admin', 'counselor'], requiresConsultation: true },
      { href: '/consultation/day', label: 'Live consultation', icon: UsersRound, roles: ['super_admin', 'counselor'], requiresConsultation: true },
      { href: '/consultation/schedule', label: 'Schedule consultation', icon: CalendarCheck, roles: ['super_admin', 'counselor'], requiresConsultation: true },
    ]},
  ].map((section) => ({
    ...section,
    items: section.items.filter((item) => canSee(item.roles, item)),
  })).filter((section) => section.items.length > 0));

  function closeDropdowns() {
    userDropdownOpen = false;
    sidebarOpen = false;
  }
</script>

<svelte:head>
  <title>SecureCAT</title>
</svelte:head>

<div class="min-h-screen w-full max-w-[100vw] bg-background flex overflow-x-hidden">
  <!-- Sidebar backdrop (mobile) - only show when sidebar is open -->
  {#if sidebarOpen}
  <button
    type="button"
    class="fixed inset-0 z-40 bg-black/50 lg:hidden"
    aria-label="Close sidebar"
    onclick={closeDropdowns}
  ></button>
  {/if}

  <!-- Sidebar -->
  <aside
    class="fixed inset-y-0 left-0 z-50 w-64 bg-card border-r border-border transform transition-transform duration-200 ease-in-out lg:translate-x-0 {sidebarOpen
      ? 'translate-x-0'
      : '-translate-x-full lg:translate-x-0'}"
  >
    <div class="flex h-16 items-center justify-between px-4 border-b border-border lg:justify-center">
      <Link href="/dashboard" class="font-bold text-primary text-lg">SecureCAT</Link>
      <Button variant="ghost" size="icon" class="lg:hidden" onclick={() => (sidebarOpen = !sidebarOpen)} aria-label="Close menu">
        <Menu class="h-5 w-5" />
      </Button>
    </div>
    <nav class="flex flex-col gap-4 p-4">
      {#each navSections as section}
        <div class="flex flex-col gap-1">
          {#if section.label}
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">{section.label}</p>
          {/if}
          {#each section.items as item}
            <Link
              href={item.href}
              class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
              onclick={closeDropdowns}
            >
              <item.icon class="h-4 w-4 shrink-0" />
              {item.label}
            </Link>
          {/each}
        </div>
      {/each}
    </nav>
  </aside>

  <div class="flex min-w-0 flex-1 flex-col lg:pl-64 overflow-x-hidden">
    <!-- Header -->
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-border bg-background px-4 lg:px-8">
      <Button variant="ghost" size="icon" class="lg:hidden" onclick={() => (sidebarOpen = true)} aria-label="Open menu">
        <Menu class="h-5 w-5" />
      </Button>
      <div class="flex-1"></div>
      <div class="relative">
        <button
          type="button"
          class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent transition-colors min-h-[44px] min-w-[44px]"
          onclick={() => {
            userDropdownOpen = !userDropdownOpen;
          }}
          aria-expanded={userDropdownOpen}
          aria-haspopup="true"
        >
          <span class="text-foreground">{user?.name ?? 'User'}</span>
          <ChevronDown class="h-4 w-4 text-muted-foreground" />
        </button>
        {#if userDropdownOpen}
          <div
            class="absolute right-0 top-full mt-1 w-48 rounded-lg border border-border bg-card py-1 shadow-lg z-50"
            role="menu"
          >
            <button
              type="button"
              class="w-full px-4 py-2 text-left text-sm text-foreground hover:bg-accent transition-colors min-h-[44px]"
              role="menuitem"
              onclick={() => {
                logout();
                userDropdownOpen = false;
              }}
            >
              Sign out
            </button>
          </div>
        {/if}
      </div>
    </header>

    <!-- Main content: min-w-0 so table containers can shrink and scroll horizontally on mobile -->
    <main class="flex-1 min-w-0 overflow-x-hidden p-4 lg:p-8">
      <div class="min-w-0 w-full max-w-full overflow-x-hidden">
        {@render children?.()}
      </div>
    </main>
  </div>
</div>
