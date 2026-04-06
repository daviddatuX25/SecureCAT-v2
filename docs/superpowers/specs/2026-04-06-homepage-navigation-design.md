# Homepage & Public Navigation Design

**Date:** 2026-04-06
**Status:** Draft

---

## 1. Overview

Split the current root route (`/`) into a clean public homepage and preserve the authenticated staff dashboard at `/dashboard`. Unauthenticated visitors see a centered card navigation page. Staff log in via a tabbed login page and land on `/dashboard`.

---

## 2. Pages & Routes

| Route | Purpose | Auth | Layout |
|-------|---------|------|--------|
| `/` | Public homepage — card navigation | None | `HomeLayout` |
| `/login` | Tabbed login — Applicant / Staff | None | `HomeLayout` |
| `/apply` | Static application info page | None | `HomeLayout` |
| `/about` | Static about page (empty/minimal) | None | `HomeLayout` |
| `/dashboard` | Staff dashboard (existing) | Authenticated | `AuthenticatedLayout` |

---

## 3. Layout: HomeLayout

A minimal, centered layout for unauthenticated public pages. No sidebar, no top nav bar.

**Structure:**
```
<body>
  [Header — logo left, tagline center, Login link right]
  [Main content — {page content} centered, max-w-5xl]
  [Footer — copyright, links]
</body>
```

**Header:** SecureCAT logo (shield icon + "SecureCAT" text), right side shows "Login" text link.
**Footer:** Simple copyright text, no links yet.

---

## 4. Homepage (`/`)

### Visual

Centered content with a hero area and 2×2 card grid below it.

```
[SecureCAT logo centered above]
[Tagline: "Examination Management System"]
[subheading text]

[Home card]  [Application card]
[About card] [Login card]
```

### Cards

Each card is a large clickable panel (min 180×160px), icon on top, label below, with hover effect.

| Card | Icon | Description |
|------|------|-------------|
| Home | `Home` | Current page — no navigation, acts as indicator |
| Application | `FileText` | Links to `/apply` |
| About | `Info` | Links to `/about` |
| Login | `LogIn` | Links to `/login` |

### Redirect Behavior

- **If already authenticated:** homepage detects auth user and shows a "Go to Dashboard" banner/button instead of the 4 cards, linking to `/dashboard`.
- **If not authenticated:** show the 4-card layout.

---

## 5. Login Page (`/login`)

### Layout

Full-page centered card with SecureCAT branding, then the tabbed form.

```
[SecureCAT logo]
[Tab: Applicant] [Tab: Staff]
[Email input]
[Password input]
[Remember me checkbox]
[Submit button]
[Forgot password link? — Applicant only]
```

### Behavior

- **Applicant tab:** posts to `/login` with `guard: applicant` (or similar mechanism your existing auth uses for applicant sessions).
- **Staff tab:** posts to `/login` with `guard: web` (default).
- Failed validation shows inline errors under each field.
- On success: redirect to `/dashboard` (staff) or `/portal/dashboard` (applicant).

---

## 6. Apply Page (`/apply`)

Static informational page for new applicants. Content is placeholder/minimal — "Coming soon" or a brief description. Links back to home.

---

## 7. About Page (`/about`)

Static page, empty/minimal. Link back to home.

---

## 8. Route Guarding

Unauthenticated routes (`/login`, `/apply`, `/about`) should redirect to `/dashboard` if a valid session already exists (applicant or staff).

---

## 9. Technical Approach

**Backend:**
- No new controllers needed — existing auth controllers handle login.
- Add a new `HomeController` with `index()` (renders homepage), `apply()` (renders apply page), `about()` (renders about page).
- Existing route `/` currently serves the authenticated dashboard — needs to be changed to serve the new homepage for unauthenticated users, with a redirect for authenticated staff.

**Frontend:**
- New `HomeLayout.svelte` in `resources/js/Layouts/` — no sidebar, no top nav, centered content wrapper.
- New `resources/js/Pages/Home/Index.svelte` — the 4-card homepage.
- New `resources/js/Pages/Home/Apply.svelte` — static apply page.
- New `resources/js/Pages/Home/About.svelte` — static about page.
- Modify `resources/js/Pages/Auth/Login.svelte` — add Applicant/Staff tabs.

**Route file changes (`routes/web.php`):**
```
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/apply', [HomeController::class, 'apply'])->name('apply');
Route::get('/about', [HomeController::class, 'about'])->name('about');
// Existing:
Route::get('/dashboard', ...)->name('dashboard'); // keep as-is, auth required
```

---

## 10. Scope

**In scope:**
- New HomeLayout, homepage, apply page, about page
- Tabbed login page (Applicant / Staff)
- Route adjustments for `/` and `/login`
- Authenticated user redirect from homepage to dashboard

**Out of scope:**
- Any new auth guards or user models — existing applicant/staff guards assumed working
- Portal dashboard changes
- Real content for Apply/About pages
- Any backend API changes

---

## 11. Acceptance Criteria

- [ ] `/` shows 4 cards when unauthenticated
- [ ] `/` shows "Go to Dashboard" when authenticated (staff or applicant)
- [ ] `/login` has Applicant / Staff tabs that submit to the correct auth guard
- [ ] `/apply` and `/about` render without errors (empty content OK)
- [ ] Existing `/dashboard` continues to work unchanged for authenticated staff
- [ ] All public pages use HomeLayout with no sidebar/nav