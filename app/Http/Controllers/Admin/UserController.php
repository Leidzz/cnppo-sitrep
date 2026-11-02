<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with(['unit', 'roles'])
            ->when($request->query('role'), fn ($q) => $q->role($request->query('role')))
            ->when($request->query('unit_id'), fn ($q) => $q->where('unit_id', $request->query('unit_id')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::all(),
            'units' => Unit::orderBy('name')->get(),
            'filters' => $request->only(['role', 'unit_id']),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::all(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'unit_id' => ['required', 'exists:units,id'],
            'role' => ['required', Rule::in(Role::pluck('name'))],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'unit_id' => $validated['unit_id'],
            'is_active' => true,
        ]);
        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('status', "User '{$user->name}' created with role {$validated['role']}.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::all(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'unit_id' => ['required', 'exists:units,id'],
            'role' => ['required', Rule::in(Role::pluck('name'))],
        ]);

        $user->update(collect($validated)->only(['name', 'email', 'unit_id'])->toArray());

        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')
            ->with('status', "User '{$user->name}' updated.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 422, 'You cannot deactivate your own account.');

        $user->update(['is_active' => ! $user->is_active]);

        $state = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.users.index')
            ->with('status', "User '{$user->name}' {$state}.");
    }
}
