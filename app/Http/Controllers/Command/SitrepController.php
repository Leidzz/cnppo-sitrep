<?php

namespace App\Http\Controllers\Command;

use App\Http\Controllers\Controller;
use App\Models\ReportType;
use App\Models\Sitrep;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SitrepController extends Controller
{
    private const QUEUE_STATUSES = ['Submitted', 'Under Review', 'Validated', 'Returned for Correction'];

    /**
     * Incoming queue — review-related statuses ONLY (never drafts),
     * across ALL units. Filters: status tab, unit, report type.
     */
    public function incoming(Request $request): View
    {
        $activeStatus = $request->query('status');
        abort_unless(is_null($activeStatus) || in_array($activeStatus, self::QUEUE_STATUSES), 404);

        $sitreps = Sitrep::query()
            ->with(['unit', 'reportType', 'currentStatus'])
            ->whereHas('currentStatus', fn ($q) => $q->whereIn('name', self::QUEUE_STATUSES))
            ->when($activeStatus, fn ($q) => $q->whereHas('currentStatus', fn ($s) => $s->where('name', $activeStatus)))
            ->when($request->query('unit_id'), function ($q) use ($request) {
                abort_unless(Unit::whereKey($request->query('unit_id'))->exists(), 404);
                $q->where('unit_id', $request->query('unit_id'));
            })
            ->when($request->query('report_type_id'), function ($q) use ($request) {
                abort_unless(ReportType::whereKey($request->query('report_type_id'))->exists(), 404);
                $q->where('report_type_id', $request->query('report_type_id'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('command.incoming', [
            'sitreps' => $sitreps,
            'statuses' => self::QUEUE_STATUSES,
            'activeStatus' => $activeStatus,
            'units' => Unit::orderBy('name')->get(),
            'reportTypes' => ReportType::where('is_active', true)->orderBy('name')->get(),
            'activeUnit' => $request->query('unit_id'),
            'activeType' => $request->query('report_type_id'),
        ]);
    }

    /**
     * Read-only detail for reviewers. Reuses the SAME view as officers —
     * the review actions arrive in Step 2 via the review policy.
     */
    public function show(Sitrep $sitrep): View
    {
        $this->authorize('view', $sitrep);

        $sitrep->load(['reportType.templateFields', 'unit', 'creator', 'reviewer', 'currentStatus', 'attachments']);

        return view('reporting.sitreps.show', compact('sitrep'));
    }
}
