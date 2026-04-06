<script>
  import { Link, usePage, router } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Bell, LogOut, Sun, Moon } from 'lucide-svelte';

  function toggleTheme() {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
  }

  let { children } = $props();
  const page = usePage();
  const applicant = $derived($page.props.auth?.applicant ?? null);
  const notificationsUnreadCount = $derived($page.props.auth?.notifications_unread_count ?? 0);
  const notificationsRecent = $derived($page.props.auth?.notifications_recent ?? []);

  function logout() {
    router.post('/portal/logout');
  }

  function markRead(id) {
    router.post(`/portal/notifications/${id}/read`, {}, { preserveScroll: true, onSuccess: () => router.reload() });
  }

  function formatNotifDate(iso) {
    if (!iso) return '';
    try {
      const d = new Date(iso);
      const now = new Date();
      const diffMs = now - d;
      const diffMins = Math.floor(diffMs / 60000);
      if (diffMins < 1) return 'Just now';
      if (diffMins < 60) return `${diffMins}m ago`;
      const diffHours = Math.floor(diffMins / 60);
      if (diffHours < 24) return `${diffHours}h ago`;
      return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    } catch {
      return '';
    }
  }
</script>

<svelte:head>
  <title>Portal - SecureCAT</title>
</svelte:head>

<div class="min-h-screen flex flex-col">
  <header class="sticky top-0 z-10 glass-panel border-b">
    <div class="container flex h-14 items-center justify-between px-4">
      <Link href="/portal" class="font-semibold text-foreground no-underline hover:text-primary">
        SecureCAT <span class="text-muted-foreground font-normal">Portal</span>
      </Link>
      <div class="flex items-center gap-2">
        <Button variant="ghost" size="icon" onclick={toggleTheme} aria-label="Toggle theme" class="rounded-full min-h-[44px] min-w-[44px]">
          <Sun class="h-5 w-5 dark:hidden" />
          <Moon class="h-5 w-5 hidden dark:block" />
        </Button>
        {#if applicant}
          <span class="text-sm text-muted-foreground max-w-[140px] truncate sm:max-w-none" title={applicant.email}>
            {applicant.name}
          </span>
        {/if}
        <details class="relative" data-notification-dropdown>
          <summary
            class="list-none flex items-center justify-center min-h-[44px] min-w-[44px] rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors relative cursor-pointer"
            aria-label="Notifications"
          >
            <Bell class="h-5 w-5" />
            {#if notificationsUnreadCount > 0}
              <span
                class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-medium text-primary-foreground"
                aria-hidden="true"
              >
                {notificationsUnreadCount > 99 ? '99+' : notificationsUnreadCount}
              </span>
            {/if}
          </summary>
          <div
            class="absolute right-0 top-full z-50 mt-1 w-[min(320px,calc(100vw-2rem)] rounded-lg border border-border bg-popover shadow-md py-1"
          >
            {#if notificationsRecent.length > 0}
              <ul class="max-h-[280px] overflow-y-auto">
                {#each notificationsRecent as notif}
                  <li
                    class="flex flex-col gap-1 px-3 py-2 border-b border-border last:border-b-0 {notif.read ? 'opacity-75' : ''}"
                  >
                    <span class="text-sm">{notif.message}</span>
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-xs text-muted-foreground">{formatNotifDate(notif.created_at)}</span>
                      {#if !notif.read}
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          class="h-7 text-xs"
                          onclick={() => markRead(notif.id)}
                        >
                          Mark read
                        </Button>
                      {/if}
                    </div>
                  </li>
                {/each}
              </ul>
              <div class="border-t border-border px-3 py-2">
                <Link href="/portal" class="text-sm text-primary hover:underline">View all</Link>
              </div>
            {:else}
              <p class="px-3 py-4 text-sm text-muted-foreground">No notifications</p>
              <div class="border-t border-border px-3 py-2">
                <Link href="/portal" class="text-sm text-primary hover:underline">Dashboard</Link>
              </div>
            {/if}
          </div>
        </details>
        <Button type="button" variant="ghost" size="icon" class="min-h-[44px] min-w-[44px]" aria-label="Log out" onclick={logout}>
          <LogOut class="h-5 w-5" />
        </Button>
      </div>
    </div>
  </header>

  <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {@render children?.()}
  </main>

  <footer class="border-t bg-muted/40 py-6 mt-auto">
    <div class="flex items-center justify-center container px-4 text-center text-sm text-muted-foreground">
      <p>&copy; {new Date().getFullYear()} SecureCAT. All rights reserved.</p>
    </div>
  </footer>
</div>
