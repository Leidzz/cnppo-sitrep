<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSitrepRequest;
use App\Models\ReportType;
use App\Models\Sitrep;
use App\Services\AttachmentService;
use App\Services\SitrepWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SitrepController extends Controller
{
    public function create(): View
    {
        // Eager-load fields; only active report types are selectable
        // (spec Section 6: categories are admin-configurable).
        $reportTypes = ReportType::where('is_active', true)
            ->with(['templateFields' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        return view('reporting.sitreps.create', [
            'reportTypes' => $reportTypes,
        ]);
    }

    public function store(StoreSitrepRequest $request, SitrepWorkflowService $workflow, AttachmentService $attachments): RedirectResponse
    {
        $reportType = ReportType::findOrFail($request->integer('report_type_id'));

        $sitrep = $workflow->create(
            user: $request->user(),
            reportType: $reportType,
            dynamicFields: $request->input('dynamic_fields', []),
            action: $request->string('action')->toString(),
            location: $request->safe()->only([
                'latitude',
                'longitude',
                'gps_accuracy_meters',
                'location_captured_at',
                'location_description',
            ]),
        );

        if ($request->hasFile('photos')) {
            $attachments->storeForSitrep($sitrep, $request->user(), $request->file('photos'));
        }

        $message = $request->input('action') === 'submit'
            ? "SITREP {$sitrep->reference_number} submitted."
            : "SITREP {$sitrep->reference_number} saved as draft.";

        return redirect()
            ->route('reporting.dashboard')
            ->with('status', $message);
    }

    /**
     * My Reports — ONLY the current officer's own reports, newest first,
     * with an optional status filter (?status=Draft etc.).
     */
    public function index(Request $request): View
    {
        $statuses = ['Draft', 'Submitted', 'Under Review', 'Validated', 'Returned for Correction'];

        $sitreps = $request->user()->sitreps()
            ->with(['currentStatus', 'reportType'])
            ->when($request->query('status'), function ($q) use ($request, $statuses) {
                // whitelist: only filter on statuses we recognize
                abort_unless(in_array($request->query('status'), $statuses), 404);
                $q->whereHas('currentStatus', fn ($s) => $s->where('name', $request->query('status')));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();   // keeps ?status= alive when paginating

        return view('reporting.sitreps.index', [
            'sitreps' => $sitreps,
            'statuses' => $statuses,
            'activeStatus' => $request->query('status'),
        ]);
    }

    /**
     * View SITREP detail (spec Section 14). Read-only.
     */
    public function show(Sitrep $sitrep): View
    {
        // THE record-level check: author, command center, or admin.
        // Any other officer gets 403 — even with a valid URL.
        $this->authorize('view', $sitrep);

        $sitrep->load(['reportType.templateFields', 'unit', 'creator', 'reviewer', 'currentStatus', 'attachments']);

        return view('reporting.sitreps.show', compact('sitrep'));
    }

    /**
     * Edit form for a draft / returned report (Part D2). The report type
     * is LOCKED — changing the template mid-draft would orphan field data.
     */
    public function edit(Sitrep $sitrep): View
    {
        $this->authorize('update', $sitrep);

        $reportTypes = ReportType::where('is_active', true)
            ->with(['templateFields' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        return view('reporting.sitreps.edit', [
            'sitrep' => $sitrep->load('currentStatus'),
            'reportTypes' => $reportTypes,
        ]);
    }

    /**
     * Update a draft / returned report. All status changes go through
     * the workflow service (rule 7) — this controller only gathers input.
     */
    public function update(Sitrep $sitrep, StoreSitrepRequest $request, SitrepWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('update', $sitrep);

        $workflow->updateDraft(
            sitrep: $sitrep,
            user: $request->user(),
            dynamicFields: $request->input('dynamic_fields', []),
            location: $request->safe()->only([
                'latitude',
                'longitude',
                'gps_accuracy_meters',
                'location_captured_at',
                'location_description',
            ]),
            action: $request->string('action')->toString(),
        );

        $message = $request->input('action') === 'submit'
            ? "SITREP {$sitrep->reference_number} submitted."
            : "SITREP {$sitrep->reference_number} draft updated.";

        return redirect()
            ->route('reporting.sitreps.show', $sitrep)
            ->with('status', $message);
    }
}
