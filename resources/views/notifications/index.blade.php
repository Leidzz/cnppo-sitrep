<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white truncate">Notifications</h2>
    </x-slot>

    @php
        $unread = auth()->user()->notifications()->where('is_read', false)->count();
        // Type -> presentation. Returned/notices get urgency colors; new_sitrep is informational.
        $typeStyle = [
            'sitrep_validated' => ['icon' => 'check', 'cls' => 'bg-green-100 text-green-700'],
            'sitrep_returned'  => ['icon' => 'return', 'cls' => 'bg-red-100 text-red-700'],
            'new_sitrep'       => ['icon' => 'inbox',  'cls' => 'bg-blue-100 text-blue-700'],
        ];
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">

            @if ($unread > 0)
                <div class="flex items-center justify-between mb-5">
                    <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">{{ $unread }}</span> unread</p>
                    <form method="POST" action="{{ route('notifications.readAll') }}">
                        @csrf
                        <x-btn type="submit" variant="outline">Mark all as read</x-btn>
                    </form>
                </div>
            @endif

            <div class="space-y-2">
                @forelse ($notifications as $notification)
                    @php $style = $typeStyle[$notification->type] ?? ['icon' => 'bell', 'cls' => 'bg-gray-100 text-gray-600']; @endphp
                    <div class="flex items-start gap-4 rounded-2xl border {{ $notification->is_read ? 'border-gray-200 bg-white' : 'border-blue-200 bg-blue-50/50' }} p-4">
                        <div class="h-10 w-10 shrink-0 rounded-xl {{ $style['cls'] }} flex items-center justify-center">
                            <x-icon name="{{ $style['icon'] }}" class="w-5 h-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $notification->message }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $notification->created_at->diffForHumans() }}
                                <span class="text-gray-300">·</span> {{ $notification->created_at->format('M d, g:i A') }}
                            </p>
                        </div>
                        @unless ($notification->is_read)
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-blue-600" aria-label="Unread"></span>
                        @endunless
                    </div>
                @empty
                    <x-empty-state>You're all caught up.</x-empty-state>
                @endforelse
            </div>

            <div class="mt-6">{{ $notifications->links() }}</div>
        </div>
    </div>
</x-app-layout>