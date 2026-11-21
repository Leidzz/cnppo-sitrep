<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Sitrep;
use App\Models\Unit;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Admin dashboard: system health, workflow funnel, unit performance
     * and recent system activity. Every number is a database aggregate;
     * no SITREP rows are loaded into memory just to be counted.
     *
     * ?range=7|30|90 controls the trend, deltas and unit table.
     */
    public function __invoke(Request $request, DashboardMetricsService $metrics): View
    {
        $days = $metrics->normalizeRange($request->query('range', 7));
        $p = $metrics->period($days);

        $sitrepCount = Sitrep::count();
        $submittedTotal = Sitrep::whereNotNull('submitted_at')->count();
        $gpsTagged = Sitrep::whereNotNull('submitted_at')->whereNotNull('latitude')->whereNotNull('longitude')->count();

        $received = $metrics->submittedBetween(null, $p['from'], $p['to']);
        $receivedPrev = $metrics->submittedBetween(null, $p['prevFrom'], $p['prevTo']);

        $trend = $metrics->submissionTrend(days: $days);
        $roles = Role::withCount('users')->orderBy('name')->get();

        return view('admin.dashboard', [
            'days' => $days,
            'userCount' => User::count(),
            'activeUsers' => User::where('is_active', true)->count(),
            'unitCount' => Unit::count(),
            'sitrepCount' => $sitrepCount,
            'received' => $received,
            'receivedDelta' => $metrics->delta($received, $receivedPrev),
            'gpsPct' => $submittedTotal ? round($gpsTagged / $submittedTotal * 100) : null,
            'roles' => $roles,
            'trendLabels' => $trend['labels'],
            'trendData' => $trend['data'],
            'byStatus' => $metrics->countsByStatus(),
            'byType' => $metrics->countsByType(),
            'funnel' => $metrics->workflowFunnel(),
            'unitPerformance' => $metrics->unitPerformance($p['from'], $p['to']),
            'activity' => AuditLog::query()
                ->with(['user:id,name', 'sitrep:id,reference_number'])
                ->latest('created_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
