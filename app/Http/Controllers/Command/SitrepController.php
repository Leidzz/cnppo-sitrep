<?php

namespace App\Http\Controllers\Command;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewSitrepRequest;
use App\Models\ReportType;
use App\Models\Sitrep;
use App\Models\Unit;
use App\Services\SitrepWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SitrepController extends Controller
{
    private const QUEUE_STATUSES = ['Submitted', 'Under Review', 'Validated', 'Returned for Correction'];

    /**
     * Incoming queue — the reviewer's triage list. Defaults to Newly
     * Submitted (what needs attention NOW); ?status=all shows everything
     * in the review pipeline. Never drafts. Filters: unit, report type.
     */
    public function incoming(Request $request): View
    {
        $activeStatus = $request->query('status', 'Submitted');
        $showAll = $activeStatus === 'all';
        abort_unless($showAll || in_array($activeStatus, self::QUEUE_STATUSES), 404);

        abort_unless($showAll || in_array($activeStatus, self::QUEUE_STATUSES), 404);

        $sitreps = Sitrep::query()
            ->with(['unit', 'reportType', 'currentStatus'])
            ->whereHas('currentStatus', fn ($q) => $q->whereIn('name', self::QUEUE_STATUSES))
            ->when(! $showAll, fn ($q) => $q->whereHas('currentStatus', fn ($s) => $s->where('name', $activeStatus)))
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
     * the review action section renders via @can('review', ...) in Blade.
     */
    public function show(Sitrep $sitrep): View
    {
        $this->authorize('view', $sitrep);

        $sitrep->load(['reportType.templateFields', 'unit', 'creator', 'reviewer', 'currentStatus', 'attachments']);

        return view('reporting.sitreps.show', compact('sitrep'));
    }

    /**
     * Record the review decision.
     * The Form Request already authorized ('review' policy) and validated
     * (remarks required when returning). The service performs the
     * transition + audit; this controller only gathers input.
     */
    public function decide(Sitrep $sitrep, ReviewSitrepRequest $request, SitrepWorkflowService $workflow): RedirectResponse
    {
        $workflow->review(
            sitrep: $sitrep,
            user: $request->user(),
            decision: $request->string('decision')->toString(),
            remarks: $request->input('remarks'),
        );

        $message = $request->input('decision') === 'validated'
            ? "SITREP {$sitrep->reference_number} validated."
            : "SITREP {$sitrep->reference_number} returned for correction.";

        return redirect()
            ->route('command.incoming')
            ->with('status', $message);
    }

    /**
     * All-Reports repository (spec Sections 12-13): searchable archive of
     * every non-draft SITREP, filterable by keyword, date range, unit,
     * report type, and status. Sort whitelist guards the ORDER BY.
     */
    public function reports(Request $request): View
    {
        $activeStatus = $request->query('status');
        abort_unless(is_null($activeStatus) || in_array($activeStatus, self::QUEUE_STATUSES), 404);

        $sort = $request->query('sort', 'desc');
        abort_unless(in_array($sort, ['asc', 'desc'], true), 404);

        $sitreps = Sitrep::query()
            ->with(['unit', 'reportType', 'currentStatus'])
            ->whereHas('currentStatus', fn ($q) => $q->whereIn('name', self::QUEUE_STATUSES))
            // Keyword: reference number OR anywhere in the report content
            ->when($request->query('q'), function ($q) use ($request) {
                $keyword = '%'.$request->query('q').'%';
                $q->where(fn ($inner) => $inner
                    ->where('reference_number', 'like', $keyword)
                    ->orWhere('dynamic_fields', 'like', $keyword));
            })
            ->when($activeStatus, fn ($q) => $q->whereHas('currentStatus', fn ($s) => $s->where('name', $activeStatus)))
            ->when($request->query('unit_id'), function ($q) use ($request) {
                abort_unless(Unit::whereKey($request->query('unit_id'))->exists(), 404);
                $q->where('unit_id', $request->query('unit_id'));
            })
            ->when($request->query('report_type_id'), function ($q) use ($request) {
                abort_unless(ReportType::whereKey($request->query('report_type_id'))->exists(), 404);
                $q->where('report_type_id', $request->query('report_type_id'));
            })
            ->when($request->query('date_from'), fn ($q) => $q->whereDate('submitted_at', '>=', $request->query('date_from')))
            ->when($request->query('date_to'), fn ($q) => $q->whereDate('submitted_at', '<=', $request->query('date_to')))
            ->orderBy('submitted_at', $sort)
            ->paginate(15)
            ->withQueryString();

        return view('command.reports', [
            'sitreps' => $sitreps,
            'statuses' => self::QUEUE_STATUSES,
            'units' => Unit::orderBy('name')->get(),
            'reportTypes' => ReportType::where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['q', 'status', 'unit_id', 'report_type_id', 'date_from', 'date_to', 'sort']),
        ]);
    }

    /**
     * Map view (spec Section 10): shell page; markers load via mapData().
     */
    public function map(): View
    {
        return view('command.map', [
            'statuses' => self::QUEUE_STATUSES,
        ]);
    }

    /**
     * JSON feed of mappable reports. Only records WITH coordinates appear.
     * Neutral fields only — the popup says 'Location captured (device)',
     * never 'verified'. Includes device accuracy for the uncertainty circle.
     */
    public function mapData(Request $request): JsonResponse
    {
        $activeStatus = $request->query('status');
        abort_unless(is_null($activeStatus) || in_array($activeStatus, self::QUEUE_STATUSES), 404);

        $markers = Sitrep::query()
            ->with(['unit', 'reportType', 'currentStatus'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('currentStatus', fn ($q) => $q->whereIn('name', self::QUEUE_STATUSES))
            ->when($activeStatus, fn ($q) => $q->whereHas('currentStatus', fn ($s) => $s->where('name', $activeStatus)))
            ->latest('submitted_at')
            ->limit(500)   // hard cap: keeps the map responsive; flagged for pagination if CNPPO needs more
            ->get()
            ->map(fn (Sitrep $s) => [
                'id' => $s->id,
                'reference' => $s->reference_number,
                'unit' => $s->unit->name,
                'type' => $s->reportType->name,
                'status' => $s->currentStatus->name,
                'lat' => $s->latitude,
                'lng' => $s->longitude,
                'accuracy' => $s->gps_accuracy_meters,
                'captured_at' => $s->location_captured_at?->format('M d, Y g:i A'),
                'url' => route('command.sitreps.show', $s),
            ]);

        return response()->json(['markers' => $markers]);
    }
}
