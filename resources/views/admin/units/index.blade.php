<x-app-layout>
    <x-slot name="header"><h2 class="text-lg font-semibold text-white truncate">Units / Stations</h2></x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <x-flash />

            {{-- Inline create form --}}
            <form method="POST" action="{{ route('admin.units.store') }}"
                  class="anim-rise rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                @csrf
                <div class="grid gap-3 sm:grid-cols-[1fr_180px_auto]">
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Name <span class="text-red-600">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g., Basud Municipal Police Station"
                               class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Code <span class="text-red-600">*</span></label>
                        <input type="text" name="code" value="{{ old('code') }}" required placeholder="BASUD-MPS"
                               class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px] uppercase">
                        @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <x-btn type="submit">Add Unit</x-btn>
                    </div>
                </div>
            </form>

            {{-- Units list --}}
            <div class="anim-rise mt-6 rounded-2xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100" style="animation-delay:120ms">
                @forelse ($units as $unit)
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $unit->name }}</p>
                            <p class="text-xs text-gray-500">{{ $unit->code }} · {{ $unit->users_count }} user(s) · {{ $unit->sitreps_count }} SITREP(s)</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('admin.units.edit', $unit) }}" class="min-h-[44px] inline-flex items-center px-3 text-sm font-semibold text-blue-700">Edit</a>
                            @if ($unit->users_count === 0 && $unit->sitreps_count === 0)
                                <form method="POST" action="{{ route('admin.units.destroy', $unit) }}"
                                      onsubmit="return confirm('Delete this unit?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="min-h-[44px] px-2 text-xs font-semibold text-red-600">Delete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8"><x-empty-state>No units defined.</x-empty-state></div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>