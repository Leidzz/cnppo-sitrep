<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\Status;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Reporting Personnel dashboard.
     *
     * Route middleware (auth + role:reporting-personnel) has already verified
     * the user. Counts come from actual system records (spec Section 4):
     * grouped queries over the officer's own SITREPs — no hardcoded numbers.
     */
    public function __invoke(Request $request, DashboardMetricsService $metrics): View
    {
        $user = $request->user();
        $days = $metrics->normalizeRange($request->query('range', 30));

        $statuses = Status::whereIn('name', [
            'Draft', 'Submitted', 'Under Review', 'Validated', 'Returned for Correction',
        ])->get();

        // One GROUP BY query for all five counts instead of five queries.
        // pluck() gives us [status_id => count] for the view.
        $counts = $user->sitreps()
            ->whereIn('current_status_id', $statuses->pluck('id'))
            ->select('current_status_id', DB::raw('count(*) as aggregate'))
            ->groupBy('current_status_id')
            ->pluck('aggregate', 'current_status_id');

        $recent = $user->sitreps()
            ->with(['currentStatus', 'reportType'])
            ->latest()
            ->limit(5)
            ->get();

        // Returned reports the officer must fix, with the reviewer's reason.
        $returnedId = $statuses->firstWhere('name', 'Returned for Correction')?->id;
        $needsAction = $user->sitreps()
            ->with('reportType')
            ->where('current_status_id', $returnedId)
            ->orderByDesc('reviewed_at')
            ->limit(3)
            ->get();
        $needsActionCount = (int) ($counts[$returnedId] ?? 0);

        $trend = $metrics->submissionTrend($user->sitreps(), days: $days);

        // Personal track record (all time)
        $since = Carbon::create(2000);
        $validatedId = $statuses->firstWhere('name', 'Validated')?->id;

        $labels = [
            'Draft' => 'Draft',
            'Submitted' => 'Submitted',
            'Under Review' => 'Under Review',
            'Validated' => 'Validated',
            'Returned for Correction' => 'Returned',
        ];

        return view('reporting.dashboard', [
            'days' => $days,
            'statuses' => $statuses,
            'counts' => $counts,
            'recent' => $recent,
            'labels' => $labels,
            'needsAction' => $needsAction,
            'needsActionCount' => $needsActionCount,
            'trendLabels' => $trend['labels'],
            'trendData' => $trend['data'],
            'byStatus' => $metrics->countsByStatus($user->sitreps()),
            'validationRate' => $metrics->validationRate($since, now(), $user->id),
            'avgHoursToValidation' => $metrics->avgTurnaroundHours(
                $user->sitreps()->where('current_status_id', $validatedId), $since, now()
            ),
        ]);
    }
}
