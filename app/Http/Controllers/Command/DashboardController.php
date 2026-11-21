<?php

namespace App\Http\Controllers\Command;

use App\Http\Controllers\Controller;
use App\Models\ReportType;
use App\Models\Sitrep;
use App\Models\Status;
use App\Models\Unit;
use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Command Center dashboard — province-wide counts (spec Section 4:
     * counts from actual records), period performance with deltas,
     * backlog aging, activity patterns and the latest incoming reports.
     *
     * ?range=7|30|90 controls every period-based widget.
     */
    public function __invoke(Request $request, DashboardMetricsService $metrics): View
    {
        $days = $metrics->normalizeRange($request->query('range', 7));
        $p = $metrics->period($days);

        // ---- Current workload (snapshot, not period-based) ----------------
        $statuses = Status::whereIn('name', [
            'Submitted', 'Under Review', 'Validated', 'Returned for Correction',
        ])->get();

        $pipelineIds = $statuses->pluck('id');

        $counts = Sitrep::query()
            ->whereIn('current_status_id', $pipelineIds)
            ->select('current_status_id', DB::raw('count(*) as aggregate'))
            ->groupBy('current_status_id')
            ->pluck('aggregate', 'current_status_id');

        $recent = Sitrep::query()
            ->with(['unit', 'reportType', 'currentStatus'])
            ->whereIn('current_status_id', $statuses->whereIn('name', DashboardMetricsService::PENDING)->pluck('id'))
            ->latest()
            ->limit(6)
            ->get();

        // Triage counter: NEW reports waiting (already inside $counts).
        $submittedId = $statuses->firstWhere('name', 'Submitted')?->id;
        $newCount = (int) ($counts[$submittedId] ?? 0);

        $labels = [
            'Submitted' => 'Newly Submitted',
            'Under Review' => 'For Review',
            'Validated' => 'Validated',
            'Returned for Correction' => 'Returned',
        ];

        // ---- Period performance (current vs previous period) -------------
        $received = $metrics->submittedBetween(null, $p['from'], $p['to']);
        $receivedPrev = $metrics->submittedBetween(null, $p['prevFrom'], $p['prevTo']);
        $validated = $metrics->transitionsBetween('Validated', $p['from'], $p['to']);
        $validatedPrev = $metrics->transitionsBetween('Validated', $p['prevFrom'], $p['prevTo']);
        $returned = $metrics->transitionsBetween('Returned for Correction', $p['from'], $p['to']);
        $returnedPrev = $metrics->transitionsBetween('Returned for Correction', $p['prevFrom'], $p['prevTo']);
        $turnaround = $metrics->avgTurnaroundHours(null, $p['from'], $p['to']);
        $turnaroundPrev = $metrics->avgTurnaroundHours(null, $p['prevFrom'], $p['prevTo']);

        $submittedTrend = $metrics->submissionTrend(days: $days);
        $validatedTrend = $metrics->transitionTrend('Validated', $days);
        $returnedTrend = $metrics->transitionTrend('Returned for Correction', $days);

        $performance = [
            'received' => ['count' => $received, 'delta' => $metrics->delta($received, $receivedPrev), 'spark' => $submittedTrend['data']],
            'validated' => ['count' => $validated, 'delta' => $metrics->delta($validated, $validatedPrev), 'spark' => $validatedTrend['data']],
            'returned' => ['count' => $returned, 'delta' => $metrics->delta($returned, $returnedPrev), 'spark' => $returnedTrend['data']],
            'turnaround' => [
                'hours' => $turnaround,
                'delta' => $turnaround !== null && $turnaroundPrev !== null ? $metrics->delta($turnaround, $turnaroundPrev) : null,
            ],
        ];

        // ---- Breakdowns ---------------------------------------------------
        $byType = $metrics->countsByType(
            Sitrep::query()->whereIn('sitreps.current_status_id', $pipelineIds)
        );
        $byUnit = $metrics->countsByUnit(
            Sitrep::query()->whereBetween('sitreps.submitted_at', [$p['from'], $p['to']]),
            limit: 10
        );

        return view('command.dashboard', [
            'days' => $days,
            'statuses' => $statuses,
            'counts' => $counts,
            'recent' => $recent,
            'newCount' => $newCount,
            'labels' => $labels,
            'oldest' => $metrics->oldestPending(),
            'performance' => $performance,
            'validationRate' => $metrics->validationRate($p['from'], $p['to']),
            'trendLabels' => $submittedTrend['labels'],
            'trendData' => $submittedTrend['data'],
            'validatedData' => $validatedTrend['data'],
            'typeLabels' => $byType->keys()->all(),
            'typeData' => $byType->values()->all(),
            'unitChart' => $metrics->toChart($byUnit),
            'aging' => $metrics->pendingAging(),
            'heatmap' => $metrics->activityHeatmap($days),
            // Filter-band options
            'units' => Unit::orderBy('name')->get(['id', 'name']),
            'reportTypes' => ReportType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
