<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Sitrep;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregate queries shared by the three role dashboards.
 *
 * Every method lets the database do the counting (GROUP BY + COUNT)
 * instead of loading SITREP rows into PHP and grouping them in memory.
 * That keeps memory flat no matter how many reports pile up.
 *
 * Most methods take an optional base query so a dashboard can narrow the
 * scope (e.g. "only this officer's reports" or "only the review pipeline")
 * and still reuse the same aggregation logic.
 *
 * Date math that differs between MySQL (production, Laragon) and SQLite
 * (the test suite) is isolated in the small *Sql() helpers at the bottom.
 */
class DashboardMetricsService
{
    /** Allowed range options (days) for trend charts and period KPIs. */
    public const RANGES = [7, 30, 90];

    /** Statuses that still need action from Command Center. */
    public const PENDING = ['Submitted', 'Under Review'];

    // ------------------------------------------------------------------
    // Ranges & deltas
    // ------------------------------------------------------------------

    /** Clamp any user-supplied range to an allowed value. */
    public function normalizeRange(mixed $days): int
    {
        $days = (int) $days;

        return in_array($days, self::RANGES, true) ? $days : 7;
    }

    /**
     * Current period and the equally long period right before it.
     *
     * @return array{from: CarbonInterface, to: CarbonInterface, prevFrom: CarbonInterface, prevTo: CarbonInterface}
     */
    public function period(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        return [
            'from' => $from,
            'to' => now(),
            'prevFrom' => $from->copy()->subDays($days),
            'prevTo' => $from->copy()->subSecond(),
        ];
    }

    /**
     * Percent change from $previous to $current, or null when there is no
     * baseline (a "+∞%" badge would be misleading, so we show nothing).
     */
    public function delta(int|float $current, int|float $previous): ?float
    {
        if ($previous == 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    // ------------------------------------------------------------------
    // Submissions
    // ------------------------------------------------------------------

    /**
     * Daily submission counts for the last $days days, zero-filled so the
     * chart always has one point per day.
     *
     * @param  Builder<Sitrep>|Relation<Sitrep, *, *>|null  $query
     * @return array{labels: list<string>, data: list<int>}
     */
    public function submissionTrend(Builder|Relation|null $query = null, int $days = 7): array
    {
        $days = $this->normalizeRange($days);
        $start = now()->subDays($days - 1)->startOfDay();

        $raw = $this->base($query)
            ->whereNotNull('sitreps.submitted_at')
            ->where('sitreps.submitted_at', '>=', $start)
            ->selectRaw('DATE(sitreps.submitted_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->zeroFill($raw, $days);
    }

    /**
     * @param  Builder<Sitrep>|Relation<Sitrep, *, *>|null  $query
     */
    public function submittedBetween(Builder|Relation|null $query, CarbonInterface $from, CarbonInterface $to): int
    {
        return $this->base($query)->whereBetween('sitreps.submitted_at', [$from, $to])->count();
    }

    // ------------------------------------------------------------------
    // Review decisions (from the append-only audit log, so a report that
    // was returned and later resubmitted still counts as "returned" on
    // the day it happened)
    // ------------------------------------------------------------------

    /**
     * Daily count of transitions INTO $status over the last $days days.
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    public function transitionTrend(string $status, int $days = 7, ?int $creatorId = null): array
    {
        $days = $this->normalizeRange($days);
        $start = now()->subDays($days - 1)->startOfDay();

        $raw = $this->transitions($status, $creatorId)
            ->where('audit_logs.created_at', '>=', $start)
            ->selectRaw('DATE(audit_logs.created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->zeroFill($raw, $days);
    }

    public function transitionsBetween(string $status, CarbonInterface $from, CarbonInterface $to, ?int $creatorId = null): int
    {
        return $this->transitions($status, $creatorId)
            ->whereBetween('audit_logs.created_at', [$from, $to])
            ->count();
    }

    /**
     * Validated ÷ (Validated + Returned) for decisions made in the window,
     * as a percentage. Null when no decisions were made.
     */
    public function validationRate(CarbonInterface $from, CarbonInterface $to, ?int $creatorId = null): ?float
    {
        $validated = $this->transitionsBetween('Validated', $from, $to, $creatorId);
        $returned = $this->transitionsBetween('Returned for Correction', $from, $to, $creatorId);
        $decided = $validated + $returned;

        return $decided ? round($validated / $decided * 100, 1) : null;
    }

    /**
     * Average hours from submission to review decision, for reports
     * reviewed inside the window. Null when nothing was reviewed.
     *
     * @param  Builder<Sitrep>|Relation<Sitrep, *, *>|null  $query
     */
    public function avgTurnaroundHours(Builder|Relation|null $query, CarbonInterface $from, CarbonInterface $to): ?float
    {
        $minutes = $this->base($query)
            ->whereNotNull('sitreps.submitted_at')
            ->whereBetween('sitreps.reviewed_at', [$from, $to])
            ->selectRaw('AVG('.$this->diffMinutesSql('sitreps.submitted_at', 'sitreps.reviewed_at').') as m')
            ->value('m');

        return $minutes === null ? null : round($minutes / 60, 1);
    }

    // ------------------------------------------------------------------
    // Workload & backlog
    // ------------------------------------------------------------------

    /**
     * Pending reports bucketed by how long they've been waiting.
     *
     * @return array{buckets: list<string>, series: array<string, list<int>>, total: int, overdue: int}
     */
    public function pendingAging(): array
    {
        $now = now();
        $h4 = $now->copy()->subHours(4);
        $h24 = $now->copy()->subDay();
        $d3 = $now->copy()->subDays(3);

        $rows = Sitrep::query()
            ->join('statuses', 'statuses.id', '=', 'sitreps.current_status_id')
            ->whereIn('statuses.name', self::PENDING)
            ->whereNotNull('sitreps.submitted_at')
            ->selectRaw('statuses.name as status,
                SUM(CASE WHEN sitreps.submitted_at >= ? THEN 1 ELSE 0 END) as b1,
                SUM(CASE WHEN sitreps.submitted_at < ? AND sitreps.submitted_at >= ? THEN 1 ELSE 0 END) as b2,
                SUM(CASE WHEN sitreps.submitted_at < ? AND sitreps.submitted_at >= ? THEN 1 ELSE 0 END) as b3,
                SUM(CASE WHEN sitreps.submitted_at < ? THEN 1 ELSE 0 END) as b4',
                [$h4, $h4, $h24, $h24, $d3, $d3])
            ->groupBy('statuses.name')
            ->toBase()   // aggregate rows, not Sitrep models
            ->get()
            ->keyBy('status');

        $series = [];
        foreach (self::PENDING as $status) {
            $r = $rows->get($status);
            $series[$status] = [(int) ($r->b1 ?? 0), (int) ($r->b2 ?? 0), (int) ($r->b3 ?? 0), (int) ($r->b4 ?? 0)];
        }

        return [
            'buckets' => ['< 4 hours', '4–24 hours', '1–3 days', '> 3 days'],
            'series' => $series,
            'total' => array_sum(array_map('array_sum', $series)),
            'overdue' => array_sum(array_column($series, 3)),
        ];
    }

    /**
     * Oldest submission still waiting, per pending status.
     *
     * @return Collection<string, Carbon>
     */
    public function oldestPending(): Collection
    {
        return Sitrep::query()
            ->join('statuses', 'statuses.id', '=', 'sitreps.current_status_id')
            ->whereIn('statuses.name', self::PENDING)
            ->whereNotNull('sitreps.submitted_at')
            ->selectRaw('statuses.name as status, MIN(sitreps.submitted_at) as oldest')
            ->groupBy('statuses.name')
            ->toBase()   // aggregate rows, not Sitrep models
            ->get()
            ->mapWithKeys(fn (object $r): array => [(string) $r->status => Carbon::parse((string) $r->oldest)]);
    }

    /**
     * Submissions by day-of-week (0 = Sunday) and hour for the window.
     *
     * @return array{grid: list<list<int>>, max: int, total: int}
     */
    public function activityHeatmap(int $days): array
    {
        $rows = Sitrep::query()
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', now()->subDays($this->normalizeRange($days) - 1)->startOfDay())
            ->selectRaw($this->dowSql('submitted_at').' as dow, '.$this->hourSql('submitted_at').' as hr, count(*) as total')
            ->groupBy('dow', 'hr')
            ->toBase()   // aggregate rows, not Sitrep models
            ->get();

        $grid = array_fill(0, 7, array_fill(0, 24, 0));
        foreach ($rows as $r) {
            $grid[(int) $r->dow][(int) $r->hr] = (int) $r->total;
        }

        $flat = array_merge(...$grid);

        return ['grid' => $grid, 'max' => max($flat), 'total' => array_sum($flat)];
    }

    /**
     * Per-unit performance for reports submitted in the window.
     *
     * @return Collection<int, array{name: string, total: int, validated: int, returned: int, pending: int, rate: float|null, avg_hours: float|null}>
     */
    public function unitPerformance(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $diff = $this->diffMinutesSql('sitreps.submitted_at', 'sitreps.reviewed_at');

        return DB::table('units')
            ->leftJoin('sitreps', function ($j) use ($from, $to) {
                $j->on('sitreps.unit_id', '=', 'units.id')
                    ->whereBetween('sitreps.submitted_at', [$from, $to]);
            })
            ->leftJoin('statuses', 'statuses.id', '=', 'sitreps.current_status_id')
            ->selectRaw("units.name,
                COUNT(sitreps.id) as total,
                SUM(CASE WHEN statuses.name = 'Validated' THEN 1 ELSE 0 END) as validated,
                SUM(CASE WHEN statuses.name = 'Returned for Correction' THEN 1 ELSE 0 END) as returned,
                SUM(CASE WHEN statuses.name IN ('Submitted', 'Under Review') THEN 1 ELSE 0 END) as pending,
                AVG(CASE WHEN sitreps.reviewed_at IS NOT NULL THEN {$diff} END) as avg_minutes")
            ->groupBy('units.id', 'units.name')
            ->orderByDesc('total')
            ->orderBy('units.name')
            ->get()
            ->map(function (object $r): array {
                $decided = (int) $r->validated + (int) $r->returned;

                return [
                    'name' => (string) $r->name,
                    'total' => (int) $r->total,
                    'validated' => (int) $r->validated,
                    'returned' => (int) $r->returned,
                    'pending' => (int) $r->pending,
                    'rate' => $decided ? round($r->validated / $decided * 100, 1) : null,
                    'avg_hours' => $r->avg_minutes === null ? null : round($r->avg_minutes / 60, 1),
                ];
            });
    }

    /**
     * How far reports get through the workflow (all time), based on the
     * timestamps each stage leaves behind.
     *
     * @return list<array{label: string, count: int, pct: float}>
     */
    public function workflowFunnel(): array
    {
        $validatedId = DB::table('statuses')->where('name', 'Validated')->value('id');

        $r = Sitrep::query()
            ->selectRaw('COUNT(*) as created,
                SUM(CASE WHEN submitted_at IS NOT NULL THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN reviewed_at IS NOT NULL THEN 1 ELSE 0 END) as reviewed,
                SUM(CASE WHEN current_status_id = ? THEN 1 ELSE 0 END) as validated', [$validatedId])
            ->toBase()   // one aggregate row, not a Sitrep model
            ->first();

        $created = (int) ($r->created ?? 0);
        $stages = [
            'Created' => $created,
            'Submitted' => (int) ($r->submitted ?? 0),
            'Reviewed' => (int) ($r->reviewed ?? 0),
            'Validated' => (int) ($r->validated ?? 0),
        ];

        return collect($stages)->map(fn ($n, $label) => [
            'label' => $label,
            'count' => $n,
            'pct' => $created ? round($n / $created * 100, 1) : 0.0,
        ])->values()->all();
    }

    // ------------------------------------------------------------------
    // Breakdowns
    // ------------------------------------------------------------------

    /**
     * @param  Builder<Sitrep>|Relation<Sitrep, *, *>|null  $query
     * @return Collection<string, int>  status name => count, highest first
     */
    public function countsByStatus(Builder|Relation|null $query = null): Collection
    {
        return $this->countByLookup($query, 'statuses', 'current_status_id');
    }

    /**
     * @param  Builder<Sitrep>|Relation<Sitrep, *, *>|null  $query
     * @return Collection<string, int>  report type name => count, highest first
     */
    public function countsByType(Builder|Relation|null $query = null): Collection
    {
        return $this->countByLookup($query, 'report_types', 'report_type_id');
    }

    /**
     * @param  Builder<Sitrep>|Relation<Sitrep, *, *>|null  $query
     * @return Collection<string, int>  unit name => count, highest first
     */
    public function countsByUnit(Builder|Relation|null $query = null, ?int $limit = null): Collection
    {
        return $this->countByLookup($query, 'units', 'unit_id', $limit);
    }

    /**
     * Shape a name => count collection into the arrays Chart.js expects.
     *
     * @param  Collection<string, int>  $counts
     * @return array{labels: list<string>, data: list<int>}
     */
    public function toChart(Collection $counts): array
    {
        return ['labels' => $counts->keys()->all(), 'data' => $counts->values()->all()];
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * One query: JOIN the lookup table, GROUP BY its name, COUNT the rows.
     *
     * @param  Builder<Sitrep>|Relation<Sitrep, *, *>|null  $query
     * @return Collection<string, int>
     */
    private function countByLookup(Builder|Relation|null $query, string $table, string $foreignKey, ?int $limit = null): Collection
    {
        $q = $this->base($query)
            ->join($table, "{$table}.id", '=', "sitreps.{$foreignKey}")
            ->selectRaw("{$table}.name as label, count(*) as total")
            ->groupBy("{$table}.name")
            ->orderByDesc('total')
            ->orderBy("{$table}.name");   // stable order when counts tie

        if ($limit) {
            $q->limit($limit);
        }

        return $q->pluck('total', 'label')->map(fn ($n) => (int) $n);
    }

    /** @return Builder<AuditLog> */
    private function transitions(string $status, ?int $creatorId): Builder
    {
        return AuditLog::query()
            ->where('audit_logs.action', 'status_changed')
            ->where('audit_logs.new_status', $status)
            ->when($creatorId, fn ($q) => $q->whereIn(
                'audit_logs.sitrep_id',
                Sitrep::query()->select('id')->where('created_by', $creatorId)
            ));
    }

    /**
     * @param  Collection<string, int|string>  $raw  'Y-m-d' => count
     * @return array{labels: list<string>, data: list<int>}
     */
    private function zeroFill(Collection $raw, int $days): array
    {
        // Longer ranges get a shorter label so the x-axis stays readable.
        $format = $days > 7 ? 'M j' : 'M d';

        $labels = [];
        $data = [];
        foreach (range($days - 1, 0) as $i) {
            $date = now()->subDays($i);
            $labels[] = $date->format($format);
            $data[] = (int) ($raw[$date->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Clone the caller's scope (so we never mutate it) or start fresh.
     *
     * @param  Builder<Sitrep>|Relation<Sitrep, *, *>|null  $query
     * @return Builder<Sitrep>
     */
    private function base(Builder|Relation|null $query): Builder
    {
        if ($query instanceof Relation) {
            return $query->getQuery()->clone();
        }

        return $query ? $query->clone() : Sitrep::query();
    }

    private function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    private function diffMinutesSql(string $from, string $to): string
    {
        return $this->driver() === 'sqlite'
            ? "((julianday({$to}) - julianday({$from})) * 1440)"
            : "TIMESTAMPDIFF(MINUTE, {$from}, {$to})";
    }

    /** Day of week, 0 = Sunday … 6 = Saturday. */
    private function dowSql(string $col): string
    {
        return $this->driver() === 'sqlite'
            ? "CAST(strftime('%w', {$col}) AS INTEGER)"
            : "(DAYOFWEEK({$col}) - 1)";
    }

    private function hourSql(string $col): string
    {
        return $this->driver() === 'sqlite'
            ? "CAST(strftime('%H', {$col}) AS INTEGER)"
            : "HOUR({$col})";
    }
}
