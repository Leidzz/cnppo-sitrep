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
     * counts from actual records) + latest incoming.
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

        return view('command.dashboard', [
            'statuses' => $statuses,
            'counts' => $counts,
            'recent' => $recent,
        ]);
    }
}
