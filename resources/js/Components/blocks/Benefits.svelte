<script>
  import { CheckCircle2, Clock, ShieldCheck } from 'lucide-svelte';
</script>

<section class="bg-muted/40 border-y border-border">
  <div class="mx-auto flex max-w-6xl flex-col gap-10 px-4 py-16 lg:flex-row lg:items-center lg:justify-between">
    <div class="max-w-xl space-y-4">
      <p class="text-sm font-semibold uppercase tracking-wide text-primary">Why SecureCAT</p>
      <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
        End-to-end admission and testing in one place
      </h2>
      <p class="text-sm text-muted-foreground sm:text-base">
        From online application to examination, grading, and post‑exam consultation, SecureCAT keeps
        everything in a single, auditable workflow for both applicants and staff.
      </p>
    </div>

    <div class="grid w-full max-w-xl gap-4 sm:grid-cols-2">
      <div class="flex gap-3 rounded-lg border border-border bg-background p-4 shadow-sm">
        <div class="mt-1 rounded-full bg-primary/10 p-2 text-primary">
          <CheckCircle2 class="h-5 w-5" />
        </div>
        <div class="space-y-1">
          <h3 class="text-sm font-semibold sm:text-base">Streamlined applications</h3>
          <p class="text-xs text-muted-foreground sm:text-sm">
            Applicants apply once and track their status, while staff manage intakes, exam seasons,
            and decisions without spreadsheets.
          </p>
        </div>
      </div>

      <div class="flex gap-3 rounded-lg border border-border bg-background p-4 shadow-sm">
        <div class="mt-1 rounded-full bg-primary/10 p-2 text-primary">
          <Clock class="h-5 w-5" />
        </div>
        <div class="space-y-1">
          <h3 class="text-sm font-semibold sm:text-base">Faster exam operations</h3>
          <p class="text-xs text-muted-foreground sm:text-sm">
            Room assignments, proctor dashboards, grading sessions, and result printing are built‑in
            so exam days run on schedule.
          </p>
        </div>
      </div>

      <div class="flex gap-3 rounded-lg border border-border bg-background p-4 shadow-sm sm:col-span-2">
        <div class="mt-1 rounded-full bg-primary/10 p-2 text-primary">
          <ShieldCheck class="h-5 w-5" />
        </div>
        <div class="space-y-1">
          <h3 class="text-sm font-semibold sm:text-base">Secure & auditable</h3>
          <p class="text-xs text-muted-foreground sm:text-sm">
            Role‑based access, audit logs, and structured workflows help protect sensitive admission
            data and support compliance reviews.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

