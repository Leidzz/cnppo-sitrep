<x-app-layout>
    <x-slot name="header"><h2 class="text-lg font-semibold text-white truncate">Admin Dashboard</h2></x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">
            <x-flash />

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card label="Users" :count="$userCount" icon="user" tint="blue" :href="route('admin.users.index')" />
                <x-stat-card label="Units" :count="$unitCount" icon="pin" tint="green" />
                <x-stat-card label="Total SITREPs" :count="$sitrepCount" icon="doc" tint="amber" />
                <x-stat-card label="Roles" :count="$roles->count()" icon="cog" tint="gray" />
            </div>

            <div class="anim-rise mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900">Users per Role</h3>
                <div class="mt-3 space-y-2 text-sm">
                    @foreach ($roles as $role)
                        <div class="flex justify-between">
                            <span class="text-gray-600">{{ $role->name }}</span>
                            <span class="font-semibold">{{ $role->users_count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>