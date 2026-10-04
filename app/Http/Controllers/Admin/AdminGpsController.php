<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferencePoint;
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
}
