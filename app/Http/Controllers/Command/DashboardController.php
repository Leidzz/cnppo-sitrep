<?php

namespace App\Http\Controllers\Command;

use App\Http\Controllers\Controller;
use App\Models\Sitrep;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Command Center dashboard — province-wide counts (spec Section 4:
     * counts from actual records) + latest incoming + triage counter
     * + chart data (7-day trend, by report type).
     */
    public function __invoke(): View
    {
        $statuses = Status::whereIn('name', [
            'Submitted', 'Under Review', 'Validated', 'Returned for Correction',
        ])->get();

        $counts = Sitrep::query()
            ->whereIn('current_status_id', $statuses->pluck('id'))
            ->select('current_status_id', DB::raw('count(*) as aggregate'))
            ->groupBy('current_status_id')
            ->pluck('aggregate', 'current_status_id');

        $recent = Sitrep::query()
            ->with(['unit', 'reportType', 'currentStatus'])
            ->whereHas('currentStatus', fn ($q) => $q->whereIn('name', ['Submitted', 'Under Review']))
            ->latest()
            ->limit(5)
            ->get();

        // Triage counter for the queue button: how many NEW reports are waiting
        $newCount = Sitrep::whereHas('currentStatus', fn ($q) => $q->where('name', 'Submitted'))->count();

        // Card labels: DB status name -> reviewer-friendly display label
        $labels = [
            'Submitted' => 'Newly Submitted',
            'Under Review' => 'For Review',
            'Validated' => 'Validated',
            'Returned for Correction' => 'Returned',
        ];

        // Last-7-day submission trend (real counts, zeros filled)
        $trendRaw = Sitrep::query()
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

        // Workload by report type (review pipeline only)
        $byType = Sitrep::query()
            ->whereHas('currentStatus', fn ($q) => $q->whereIn('name',
                ['Submitted', 'Under Review', 'Validated', 'Returned for Correction']))
            ->with('reportType')
            ->get()
            ->groupBy(fn ($s) => $s->reportType->name)
            ->map->count()
            ->sortDesc();

        return view('command.dashboard', [
            'statuses' => $statuses,
            'counts' => $counts,
            'recent' => $recent,
            'newCount' => $newCount,
            'labels' => $labels,
            'trendLabels' => $trendLabels,
            'trendData' => $trendData,
            'typeLabels' => $byType->keys()->all(),
            'typeData' => $byType->values()->all(),
        ]);
    }
}