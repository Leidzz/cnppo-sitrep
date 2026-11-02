<x-app-layout>
    <x-slot name="header"><h2 class="text-lg font-semibold text-white truncate">New User</h2></x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto">
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-600">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-600">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Password <span class="text-red-600">*</span></label>
                    <input type="password" name="password" required class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm Password <span class="text-red-600">*</span></label>
                    <input type="password" name="password_confirmation" required class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit / Station <span class="text-red-600">*</span></label>
                    <select name="unit_id" required class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                        <option value="">Select unit…</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    @error('unit_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Role <span class="text-red-600">*</span></label>
                    <select name="role" required class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                        <option value="">Select role…</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <x-btn type="submit">Create User</x-btn>
            </form>
        </div>
    </div>
</x-app-layout>