<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\Status;
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
     * one grouped query over the officer's own SITREPs — no hardcoded numbers.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

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

        // Officer's own last-7-day submission activity (for the trend chart)
        $trendRaw = $user->sitreps()
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(submitted_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $trendLabels = [];
        $trendData = [];
        foreach (range(6, 0) as $i) {
            $d = now()->subDays($i)->toDateString();
            $trendLabels[] = now()->subDays($i)->format('M d');
            $trendData[] = (int) ($trendRaw[$d] ?? 0);
        }

        // Card labels: keep officer-facing names, shorten the long one
        $labels = [
            'Draft' => 'Draft',
            'Submitted' => 'Submitted',
            'Under Review' => 'Under Review',
            'Validated' => 'Validated',
            'Returned for Correction' => 'Returned',
        ];

        return view('reporting.dashboard', [
            'statuses' => $statuses,
            'counts' => $counts,
            'recent' => $recent,
            'labels' => $labels,
            'trendLabels' => $trendLabels,
            'trendData' => $trendData,
        ]);
    }
}
