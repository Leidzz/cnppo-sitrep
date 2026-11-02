<x-app-layout>
    <x-slot name="header"><h2 class="text-lg font-semibold text-white truncate">Edit User</h2></x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-600">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-600">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        New Password <span class="text-gray-400 font-normal">(leave blank to keep current password)</span>
                    </label>
                    <input type="password" name="password"
                           class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input type="password" name="password_confirmation"
                           class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit / Station <span class="text-red-600">*</span></label>
                    <select name="unit_id" required class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                        <option value="">Select unit…</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected(old('unit_id', $user->unit_id) == $unit->id)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    @error('unit_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Role <span class="text-red-600">*</span></label>
                    <select name="role" required class="mt-1 block w-full rounded-md border-gray-300 min-h-[44px]">
                        <option value="">Select role…</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role', $user->roles->first()?->name) === $role->name)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3">
                    <x-btn type="submit">Save Changes</x-btn>
                    <a href="{{ route('admin.users.index') }}"
                       class="min-h-[44px] inline-flex items-center px-5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>