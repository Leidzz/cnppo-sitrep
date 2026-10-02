<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSitrepRequest;
use App\Models\ReportType;
use App\Services\AttachmentService;
use App\Services\SitrepWorkflowService;
use Illuminate\Http\RedirectResponse;
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
}
