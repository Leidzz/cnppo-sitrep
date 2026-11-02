<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sitrep;
use App\Models\Unit;
use App\Models\User;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
        public function __invoke(): View
    {
        // 7-day province-wide submission trend
        $trendRaw = Sitrep::whereNotNull('submitted_at')
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

        return view('admin.dashboard', [
            'userCount' => User::count(),
            'unitCount' => Unit::count(),
            'sitrepCount' => Sitrep::count(),
            'roles' => Role::withCount('users')->get(),
            'trendLabels' => $trendLabels,
            'trendData' => $trendData,
            'byStatus' => Sitrep::with('currentStatus')->get()->groupBy(fn ($s) => $s->currentStatus->name)->map->count()->sortDesc(),
            'byType' => Sitrep::with('reportType')->get()->groupBy(fn ($s) => $s->reportType->name)->map->count()->sortDesc(),
            'byUnit' => Sitrep::with('unit')->get()->groupBy(fn ($s) => $s->unit->name)->map->count()->sortDesc()->take(7),
        ]);
    }
}
