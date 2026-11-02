<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        return view('admin.units.index', [
            'units' => Unit::withCount(['users', 'sitreps'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:units,name'],
            'code' => ['required', 'string', 'max:50', 'unique:units,code', 'regex:/^[A-Z0-9\-]+$/'],
        ]);

        Unit::create($validated);

        return redirect()->route('admin.units.index')
            ->with('status', "Unit '{$validated['name']}' added.");
    }

    public function edit(Unit $unit): View
    {
        return view('admin.units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('units')->ignore($unit->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('units')->ignore($unit->id), 'regex:/^[A-Z0-9\-]+$/'],
        ]);

        $unit->update($validated);

        return redirect()->route('admin.units.index')
            ->with('status', "Unit '{$unit->name}' updated.");
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        abort_if($unit->users()->exists() || $unit->sitreps()->exists(), 422,
            'Cannot delete a unit with users or SITREPs.');

        $unit->delete();

        return redirect()->route('admin.units.index')
            ->with('status', 'Unit deleted.');
    }
}
