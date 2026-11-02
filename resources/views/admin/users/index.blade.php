<x-app-layout>
    <x-slot name="header"><h2 class="text-lg font-semibold text-white truncate">Users</h2></x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <x-flash />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <form method="GET" class="flex flex-wrap gap-2">
                    <select name="role" class="min-h-[44px] rounded-md border-gray-300 text-sm">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(($filters['role'] ?? '') === $role->name)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <select name="unit_id" class="min-h-[44px] rounded-md border-gray-300 text-sm">
                        <option value="">All units</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? '') == $unit->id)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <x-btn type="submit" variant="outline">Filter</x-btn>
                </form>
                <a href="{{ route('admin.users.create') }}"
                   class="min-h-[44px] inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-700 text-white text-sm font-semibold">+ New User</a>
            </div>

            <div class="anim-rise mt-6 rounded-2xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
                @forelse ($users as $user)
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $user->email }} · {{ $user->unit?->name ?? 'No unit' }} ·
                                {{ $user->roles->pluck('name')->join(', ') ?: 'no role' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <a href="{{ route('admin.users.edit', $user) }}" class="min-h-[44px] inline-flex items-center px-3 text-sm font-semibold text-blue-700">Edit</a>
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.toggleActive', $user) }}">
                                    @csrf
                                    <button type="submit" class="min-h-[44px] px-2 text-xs font-semibold {{ $user->is_active ? 'text-red-600' : 'text-green-700' }}">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8"><x-empty-state>No users match these filters.</x-empty-state></div>
                @endforelse
            </div>

            <div class="mt-6">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>