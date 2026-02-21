<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
  import { Input } from '@/Components/ui/input';
  import * as Table from '@/Components/ui/table';
  import { MessageSquare, Users, Send, ClipboardList, Search, Settings } from 'lucide-svelte';

  let { applicants_pending = [], applicants_released = [], stats = { pending: 0, released: 0, total_with_scores: 0 } } = $props();

  let searchQuery = $state('');

  const filteredPending = $derived(
    searchQuery
      ? applicants_pending.filter(
          (a) =>
            a.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            a.reference.toLowerCase().includes(searchQuery.toLowerCase())
        )
      : applicants_pending
  );

  const filteredReleased = $derived(
    searchQuery
      ? applicants_released.filter(
          (a) =>
            a.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            a.reference.toLowerCase().includes(searchQuery.toLowerCase())
        )
      : applicants_released
  );

  function formatDate(value) {
    if (!value) return '—';
    const s = String(value).split('T')[0];
    if (!s) return '—';
    const [y, m, d] = s.split('-').map(Number);
    return new Date(y, (m || 1) - 1, d || 1).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }
</script>

<svelte:head>
  <title>Consultation - SecureCAT</title>
</svelte:head>

<AuthenticatedLayout>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold">Consultation</h1>
        <p class="mt-1 text-sm text-muted-foreground">Review scores and release consultations to applicants.</p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <Link href="/consultation/schedule">
          <Button variant="outline" class="min-h-[44px]">Schedule consultation day</Button>
        </Link>
        <Link href="/consultation/day">
          <Button variant="outline" class="min-h-[44px]">Consultation day</Button>
        </Link>
        <Link href="/consultation/rules">
          <Button variant="outline" class="min-h-[44px]">
            <Settings class="h-4 w-4 mr-2" />
            Decision rules
          </Button>
        </Link>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid gap-4 sm:grid-cols-3">
      <Card>
        <CardHeader class="pb-2">
          <CardTitle class="text-sm font-medium text-muted-foreground">Pending consultation</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-2">
            <Users class="h-8 w-8 text-muted-foreground" />
            <span class="text-2xl font-bold">{stats.pending}</span>
          </div>
          <p class="mt-1 text-xs text-muted-foreground">Applicants with finalized scores, not yet released</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2">
          <CardTitle class="text-sm font-medium text-muted-foreground">Released</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-2">
            <Send class="h-8 w-8 text-muted-foreground" />
            <span class="text-2xl font-bold">{stats.released}</span>
          </div>
          <p class="mt-1 text-xs text-muted-foreground">Consultations released to applicants</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2">
          <CardTitle class="text-sm font-medium text-muted-foreground">With scores</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-2">
            <ClipboardList class="h-8 w-8 text-muted-foreground" />
            <span class="text-2xl font-bold">{stats.total_with_scores}</span>
          </div>
          <p class="mt-1 text-xs text-muted-foreground">Total applicants with finalized scores</p>
        </CardContent>
      </Card>
    </div>

    <!-- Search -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-[200px] max-w-sm">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          type="search"
          placeholder="Search by name or reference"
          bind:value={searchQuery}
          class="pl-9 min-h-[44px]"
        />
      </div>
    </div>

    <!-- Pending consultation -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <MessageSquare class="h-5 w-5" />
          Pending consultation
        </CardTitle>
        <CardDescription>Applicants ready for review. View scores, add summary, and release when ready.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="rounded-lg border border-border overflow-hidden min-w-0">
          <Table.Root class="w-full min-w-[520px]">
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="px-4 py-3">Reference</Table.Head>
                <Table.Head class="px-4 py-3">Name</Table.Head>
                <Table.Head class="px-4 py-3">Scores finalized</Table.Head>
                <Table.Head class="px-4 py-3 text-right">Action</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each filteredPending as app}
                <Table.Row>
                  <Table.Cell class="px-4 py-3">{app.reference}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{app.name}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{formatDate(app.scores_finalized_at)}</Table.Cell>
                  <Table.Cell class="px-4 py-3 text-right">
                    <Link href={`/consultation/applicants/${app.id}`}>
                      <Button variant="outline" size="sm" class="min-h-[44px]">View &amp; consult</Button>
                    </Link>
                  </Table.Cell>
                </Table.Row>
              {:else}
                <Table.Row>
                  <Table.Cell colspan={4} class="px-4 py-12 text-center text-muted-foreground">
                    {searchQuery ? 'No matching applicants.' : 'No applicants pending consultation.'}
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>
      </CardContent>
    </Card>

    <!-- Released -->
    <Card>
      <CardHeader>
        <CardTitle>Released consultations</CardTitle>
        <CardDescription>Recently released — view-only.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="rounded-lg border border-border overflow-hidden min-w-0">
          <Table.Root class="w-full min-w-[520px]">
            <Table.Header class="bg-muted/50">
              <Table.Row>
                <Table.Head class="px-4 py-3">Reference</Table.Head>
                <Table.Head class="px-4 py-3">Name</Table.Head>
                <Table.Head class="px-4 py-3">Released</Table.Head>
                <Table.Head class="px-4 py-3 text-right">Action</Table.Head>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {#each filteredReleased as app}
                <Table.Row>
                  <Table.Cell class="px-4 py-3">{app.reference}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{app.name}</Table.Cell>
                  <Table.Cell class="px-4 py-3">{formatDate(app.released_at)}</Table.Cell>
                  <Table.Cell class="px-4 py-3 text-right">
                    <Link href={`/consultation/applicants/${app.id}`}>
                      <Button variant="ghost" size="sm" class="min-h-[44px]">View</Button>
                    </Link>
                  </Table.Cell>
                </Table.Row>
              {:else}
                <Table.Row>
                  <Table.Cell colspan={4} class="px-4 py-12 text-center text-muted-foreground">
                    {searchQuery ? 'No matching applicants.' : 'No released consultations yet.'}
                  </Table.Cell>
                </Table.Row>
              {/each}
            </Table.Body>
          </Table.Root>
        </div>
      </CardContent>
    </Card>
  </div>
</AuthenticatedLayout>
