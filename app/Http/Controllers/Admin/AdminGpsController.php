<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GpsObservation;
use App\Models\ReferencePoint;
use App\Services\GpsObservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminGpsController extends Controller
{
    /**
     * Reference points list + create form (with click-to-place map).
     */
    public function index(): View
    {
        return view('admin.gps.index', [
            'points' => ReferencePoint::withCount('observations')->orderBy('label')->get(),
        ]);
    }

    public function storePoint(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        ReferencePoint::create($validated);

        return redirect()->route('admin.gps.index')
            ->with('status', "Reference point '{$validated['label']}' added.");
    }

    public function destroyPoint(ReferencePoint $referencePoint): RedirectResponse
    {
        abort_if($referencePoint->observations()->exists(), 422,
            'Cannot delete a point that has observations — the study data depends on it.');

        $referencePoint->delete();

        return redirect()->route('admin.gps.index')
            ->with('status', 'Reference point deleted.');
    }

    /**
     * Mobile capture screen: stand at the point, record repeatedly.
     */
    public function capture(ReferencePoint $referencePoint): View
    {
        return view('admin.gps.capture', [
            'point' => $referencePoint,
            'stats' => app(GpsObservationService::class)->stats($referencePoint),
            'recent' => $referencePoint->observations()->latest('id')->limit(10)->get(),
        ]);
    }

    /**
     * Record ONE observation (JSON — called repeatedly from the capture page).
     * The service computes the positional error in the same transaction.
     */
    public function record(Request $request, ReferencePoint $referencePoint, GpsObservationService $service): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'gt:0'],
            'device_timestamp' => ['required', 'date'],
            'device_label' => ['nullable', 'string', 'max:100'],
            'conditions' => ['nullable', 'string', 'max:255'],
        ]);

        $observation = $service->record($referencePoint, $request->user(), $validated);

        return response()->json([
            'id' => $observation->id,
            'error' => $observation->computed_error_meters,
            'stats' => $service->stats($referencePoint),
        ]);
    }

    /**
     * Results dashboard — per-point statistics (the thesis results table).
     */
    public function results(GpsObservationService $service): View
    {
        $points = ReferencePoint::orderBy('label')->get()
            ->map(fn (ReferencePoint $p) => array_merge(['point' => $p], $service->stats($p)));

        return view('admin.gps.results', compact('points'));
    }

    /**
     * CSV export of ALL raw observations — for Excel/SPSS analysis.
     * One row per observation, reference coordinates included so error
     * can be re-derived independently (research reproducibility).
     */
    public function export()
    {
        $observations = GpsObservation::with('referencePoint')
            ->orderBy('reference_point_id')->orderBy('id')->get();

        $callback = function () use ($observations) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'point_label', 'reference_lat', 'reference_lng',
                'captured_lat', 'captured_lng', 'error_meters',
                'device_accuracy_meters', 'device_label', 'conditions',
                'device_timestamp', 'server_received_at',
            ]);
            foreach ($observations as $o) {
                fputcsv($out, [
                    $o->referencePoint->label,
                    $o->referencePoint->latitude,
                    $o->referencePoint->longitude,
                    $o->captured_latitude,
                    $o->captured_longitude,
                    $o->computed_error_meters,
                    $o->device_accuracy_meters,
                    $o->device_label,
                    $o->conditions,
                    $o->device_timestamp,
                    $o->server_received_at,
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'gps-observations-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
