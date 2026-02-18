<script>
  import { Link, router } from '@inertiajs/svelte';
  import { usePage } from '@inertiajs/svelte';
  import { ChevronDown, Menu, LayoutDashboard, Users, FileText, Calendar, DoorOpen, ClipboardCheck, GraduationCap, BookOpen, Settings } from 'lucide-svelte';
  import { Button } from '@/Components/ui/button';

  let { children } = $props();

  const page = usePage();
  const user = $derived($page.props.auth?.user ?? null);
  const roles = $derived(user?.roles?.map((r) => r.name) ?? []);
  let sidebarOpen = $state(false);
  let userDropdownOpen = $state(false);

  function hasRole(r) {
    return roles.includes(r);
  }

  function canSee(requiredRoles) {
    if (requiredRoles.includes('*')) return true;
    return requiredRoles.some((r) => hasRole(r));
  }

  function logout() {
    router.post('/logout');
  }

  const navSections = $derived([
    { label: null, items: [{ href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: ['*'] }] },
    { label: 'System', items: [
      { href: '/admin/users', label: 'Users', icon: Users, roles: ['super_admin'] },
      { href: '/admin/settings', label: 'Settings', icon: Settings, roles: ['super_admin'] },
    ]},
    { label: 'Registrar', items: [
      { href: '/applications', label: 'Applications', icon: FileText, roles: ['super_admin', 'staff', 'admin', 'counselor'] },
      { href: '/admin/courses', label: 'Courses', icon: BookOpen, roles: ['super_admin', 'admin'] },
      { href: '/admin/rooms', label: 'Rooms', icon: DoorOpen, roles: ['super_admin', 'admin'] },
      { href: '/admin/proctors', label: 'Proctors', icon: ClipboardCheck, roles: ['super_admin', 'admin'] },
      { href: '/admin/exam-sessions', label: 'Exam Sessions', icon: Calendar, roles: ['super_admin', 'admin'] },
    ]},
    { label: 'Guidance', items: [
      { href: '/proctor', label: 'My Sessions', icon: Calendar, roles: ['super_admin', 'admin', 'proctor'] },
      { href: '/grading', label: 'Grading', icon: GraduationCap, roles: ['super_admin', 'grader'] },
      { href: '/consultation', label: 'Consultation', icon: BookOpen, roles: ['super_admin', 'counselor'] },
    ]},
  ].map((section) => ({
    ...section,
    items: section.items.filter((item) => canSee(item.roles)),
  })).filter((section) => section.items.length > 0));

  function closeDropdowns() {
    userDropdownOpen = false;
    sidebarOpen = false;
  }
</script>

<svelte:head>
  <title>SecureCAT</title>
</svelte:head>

<div class="min-h-screen bg-background flex">
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

  <div class="flex flex-1 flex-col lg:pl-64">
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

    <!-- Main content -->
    <main class="flex-1 p-4 lg:p-8">
      {@render children?.()}
    </main>
  </div>
</div>
