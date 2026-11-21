# CNPPO SITREP — Dashboard Guide

This document explains what every number and chart on the three role dashboards means, where it comes from, and how to maintain it.

> **Rule followed throughout:** every figure is computed from real database records at page load. Nothing is hardcoded or estimated. When there is no data, a chart shows an empty state ("No data for this period yet.") instead of a blank canvas.

---

## 1. Common concepts

| Term | Meaning |
|---|---|
| **Range toggle (7 / 30 / 90 days)** | Sets the *period* for every period-based widget on the page. Stored in the URL as `?range=30`, so it can be bookmarked and survives a page refresh. Any other value falls back to 7 days (30 on the officer dashboard). |
| **Period** | From the start of the day *N − 1* days ago until now. With `range=7` on a Friday, that is Saturday 00:00 → now. |
| **Previous period** | The equally long window immediately before the current one. |
| **Delta (▲ / ▼ %)** | `(current − previous) ÷ previous × 100`. **Hidden when the previous period is 0**, because a "+∞%" badge would be misleading. Green means good, red means bad. For *Returned* and *Turnaround*, an increase is bad, so the colors are inverted. |
| **Validation rate** | `Validated ÷ (Validated + Returned)` for review decisions made in the period. Target shown to users: **80%**. |
| **Review turnaround** | Average hours from `submitted_at` to `reviewed_at`, for reports reviewed in the period. |
| **"As of" timestamp** | Shown under every page title. It is printed with the page so a briefing copy shows when the numbers were taken. |

### Where review decisions are counted from

*Validated* and *Returned* counts per period come from the **append-only `audit_logs` table** (`action = 'status_changed'`, `new_status = …`), not from each report's current status.

**Why:** a report returned on Monday and resubmitted on Tuesday no longer *has* the "Returned" status, but it *was* returned on Monday. The audit log keeps that history, so the counts stay correct.

---

## 2. Command Center (`/command/dashboard`)

### Current workload (live snapshot; not affected by the range)

| Card | Source |
|---|---|
| Newly Submitted / For Review | Count of reports whose **current** status is *Submitted* / *Under Review*. The subtitle shows how long the **oldest** one has been waiting. |
| Validated | All-time count of reports currently *Validated*. |
| Returned | Reports currently waiting for the officer to correct them. |

Clicking a card opens the review queue filtered to that status.

### Performance · last N days

| Card | Source |
|---|---|
| SITREPs Received | Reports with `submitted_at` in the period. Sparkline = daily count. |
| Validated | Audit-log transitions **to** *Validated* in the period. |
| Returned for Correction | Audit-log transitions **to** *Returned for Correction* in the period. Delta colors inverted. |
| Avg. Review Turnaround | See *Review turnaround* above. Delta colors inverted. |

### Charts and panels

| Widget | What it answers | Source |
|---|---|---|
| **Submitted vs. Validated** | Is the review team keeping up with incoming volume? | Daily submissions vs. daily validations (audit log). |
| **Validation Rate** gauge | How often are reports accepted on review? | See definition. Green ≥ 80%, amber ≥ 60%, red below. |
| **Pending Reports by Age** | Is anything stuck in the queue? | Reports still *Submitted* or *Under Review*, bucketed by time since submission: < 4 h, 4–24 h, 1–3 days, > 3 days. The red badge counts those over 3 days. |
| **Report Type Mix** | What kind of work is in the pipeline? | Reports in Submitted / Under Review / Validated / Returned, by type. |
| **When Reports Come In** | When should reviewers be on duty? | Submissions in the period by day-of-week × hour (Asia/Manila time). Darker = more. The summary row shows the busiest time slot, busiest day and peak hour. |
| **Reports by Unit** | Which stations file the most? | Top 10 units by reports submitted in the period. Axis labels abbreviate "Provincial Mobile Force Company" to **PMFC**; tooltips show the full name. |
| **Latest Incoming** | What came in most recently? | 6 newest reports still awaiting a decision. |
| **Search the Archive** | — | Shortcut form to *All Reports* with filters. |

---

## 3. Admin (`/admin/dashboard`)

| Widget | Source |
|---|---|
| Users | Total accounts; subtitle splits active / inactive (`users.is_active`). |
| Units | Total units. |
| Total SITREPs | All reports ever created. The delta compares reports **received** this period vs. the previous period. |
| GPS-Tagged Reports | % of *submitted* reports that have both latitude and longitude. |
| Submissions trend | Daily submissions for the selected range. |
| Users by Role | Accounts per Spatie role. |
| **Workflow Funnel** (all time) | *Created* = all reports · *Submitted* = has `submitted_at` · *Reviewed* = has `reviewed_at` · *Validated* = current status Validated. Percentages are relative to *Created*. The footnote shows drafts never submitted. |
| By Status | Current status of all reports, using the official status colors. |
| By Report Type | All reports by type. |
| **Unit Performance** table | For reports *submitted in the period*: total, validated, returned, pending, validation rate and average review time per unit. Click any column header to sort. Rows under a 60% validation rate are lightly highlighted. |
| Recent System Activity | Latest 8 audit-log entries (who, what, when). |

---

## 4. Reporting Personnel (`/reporting/dashboard`)

Everything here is scoped to **the signed-in officer's own reports** (`sitreps.created_by`).

| Widget | Source |
|---|---|
| **Needs your correction** | Shown only when the officer has reports with status *Returned for Correction*. Lists up to 3 (newest first) with the reviewer's remarks and a *Fix & resubmit* button. |
| Status cards | The officer's reports by current status. |
| Your Submissions | Daily submissions for the selected range (default 30 days). |
| Your Reports by Status | Same counts as the cards, as a chart. |
| Your Track Record | All-time validation rate (from the audit log) and average hours from submission to validation, plus submissions in the selected range. |
| Recent Reports | The officer's 5 newest reports. |

---

## 5. Where the code lives

| File | Purpose |
|---|---|
| `app/Services/DashboardMetricsService.php` | **All dashboard queries.** Each method is a single SQL aggregate (`GROUP BY` / `COUNT` / `SUM(CASE…)`), so memory use does not grow with the number of reports. |
| `app/Http/Controllers/{Admin,Command,Reporting}/DashboardController.php` | Read the range, call the service, pass the results to the view. |
| `resources/views/{admin,command,reporting}/dashboard.blade.php` | Layout of each dashboard. |
| `public/vendor/charts/chart-theme.js` | Shared Chart.js theme and chart factories. Loaded after the local `chart.umd.js`. No CDN is used, so it works on a restricted network. |
| `tests/Feature/DashboardMetricsTest.php` | Tests for the service, empty states, range toggle and activity feed. |

### Reusable Blade components

```blade
<x-page-header eyebrow="Command Center" title="Overview" subtitle="…">
    <x-slot name="actions">…buttons…</x-slot>
</x-page-header>

<x-kpi-card label="Validated" :count="47" icon="check" tint="green"
            :delta="12.5" delta-label="vs previous 7 days" :delta-inverse="false"
            :spark="[3, 5, 4, 8, 6, 9, 11]" suffix="hrs" subtitle="…" href="…" />

<x-chart-card title="By Status" subtitle="…" chart-id="statusChart"
              height="h-64" :empty="$total === 0" empty-text="…" />

<x-card title="Latest Incoming" subtitle="…" :padded="false">…</x-card>

<x-range-toggle :current="$days" />
```

`tint` accepts `blue`, `amber`, `green`, `red`, `gray` or `navy`. `<x-stat-card>` still works; it now renders `<x-kpi-card>`.

### Chart factories (`window.SitrepCharts`)

```js
C.line(id, labels, data, { label, color })
C.multiLine(id, labels, [{ label, data, color, dashed }])
C.bar(id, labels, data, { color, horizontal })
C.stackedBar(id, labels, [{ label, data, color }])
C.doughnut(id, labels, data, { statusColors, centerLabel, cutout, colors })
C.gauge(id, percentOrNull, { label })
```

Each factory returns `null` (instead of throwing) when the canvas is not on the page, for example when a chart card rendered its empty state.

---

## 6. Maintenance notes

### Status colors live in three places — keep them in sync

1. `tailwind.config.js` → `theme.extend.colors.status`
2. `resources/views/components/status-badge.blade.php`
3. `public/vendor/charts/chart-theme.js` → `STATUS_COLORS`

### Brand tokens

Defined in `tailwind.config.js`:

- **`navy-50 … navy-950`**: primary brand. `navy-800` (`#16233F`) is the sidebar and top bar.
- **`gold-*`**: accent only (active menu item, eyebrow labels, seal). Use sparingly.
- **`canvas`**: page background.

After changing `tailwind.config.js` or `resources/css/app.css`, run `npm run build`.

### Swapping in the official logo

In `resources/views/layouts/app.blade.php`, find the comment *"Seal placeholder"* and replace the `PNP` circle with:

```blade
<img src="{{ asset('images/cnppo-seal.png') }}" alt="CNPPO seal" class="h-11 w-11 shrink-0 rounded-full">
```

Put the image file at `public/images/cnppo-seal.png`.

### MySQL vs. SQLite

Production (Laragon) runs MySQL; the test suite runs SQLite in memory. The few date calculations that differ between them (minutes between two timestamps, day-of-week, hour) are isolated in the private `diffMinutesSql()`, `dowSql()` and `hourSql()` helpers at the bottom of `DashboardMetricsService`. Add any new date math there.

### Known quirk: `AuditLog::created_at` is a string

The `AuditLog` model sets `$timestamps = false` and has no cast, so `created_at` arrives as a string. The admin activity feed parses it with `Carbon::parse()`. Adding `protected $casts = ['created_at' => 'datetime'];` to the model would be cleaner. Check other views that use `created_at` before changing it.

### Recommended indexes (not yet applied)

When the system holds many thousands of reports, these indexes keep the dashboards fast:

```php
Schema::table('sitreps', fn ($t) => $t->index('submitted_at'));
Schema::table('sitreps', fn ($t) => $t->index('reviewed_at'));
Schema::table('audit_logs', fn ($t) => $t->index(['action', 'new_status', 'created_at']));
```

### Performance reference

Measured with the test suite's probe: query counts stay **constant** as data grows (no N+1). With 500 reports: Admin 19 queries, Command Center 27, Reporting 13 (including authentication and layout queries).

---

## 7. Verifying after changes

```bash
npm run build
php artisan view:clear
php artisan test
vendor\bin\phpstan analyse
```
