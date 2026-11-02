<x-app-layout>
    <x-slot name="header"><h2 class="text-lg font-semibold text-white truncate">Edit Unit</h2></x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto">
            <form method="POST" action="{{ route('admin.units.update', $unit) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-600">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $unit->name) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Code <span class="text-red-600">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $unit->code) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px] uppercase">
                    @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3">
                    <x-btn type="submit">Save Changes</x-btn>
                    <a href="{{ route('admin.units.index') }}"
                       class="min-h-[44px] inline-flex items-center px-5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>