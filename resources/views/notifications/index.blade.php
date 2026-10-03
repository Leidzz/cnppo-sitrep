<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Notifications') }}</h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">

            @php $unread = auth()->user()->notifications()->where('is_read', false)->count(); @endphp

            @if ($unread > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button type="submit"
                            class="min-h-[44px] px-5 py-3 rounded-lg bg-gray-900 text-white text-sm font-semibold">
                        Mark all as read ({{ $unread }})
                    </button>
                </form>
            @endif

            <div class="mt-6 space-y-2">
                @forelse ($notifications as $notification)
                    <div class="rounded-lg border {{ $notification->is_read ? 'border-gray-200 bg-white' : 'border-blue-300 bg-blue-50' }} p-4">
                        <p class="text-sm font-medium text-gray-900">{{ $notification->message }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $notification->created_at->format('M d, Y g:i A') }}
                            @unless ($notification->is_read)
                                · <span class="font-semibold text-blue-700">Unread</span>
                            @endunless
                        </p>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">
                        No notifications yet.
                    </div>
                @endforelse
            </div>

            <div class="mt-6">{{ $notifications->links() }}</div>
        </div>
    </div>
</x-app-layout>