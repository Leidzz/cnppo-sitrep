<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Command Center') }}</h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">

            @if (session('status'))
                <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Command Center</h1>
            <p class="mt-2 text-base text-gray-600">Incoming Situation Reports from all units</p>

            <div class="mt-8 grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ($statuses as $status)
                    <a href="{{ route('command.incoming', ['status' => $status->name]) }}"
                       class="rounded-lg border border-gray-200 bg-white p-4 hover:border-blue-400 transition">
                        <p class="text-2xl font-bold text-gray-900">{{ $counts[$status->id] ?? 0 }}</p>
                        <p class="mt-1 text-xs font-medium text-gray-500">{{ $status->name }}</p>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                <a href="{{ route('command.incoming') }}" class="min-h-[44px] inline-flex items-center px-6 py-3 rounded-lg bg-blue-700 text-white text-base font-semibold hover:bg-blue-800">
                    Open Incoming Queue
                </a>
            </div>

            <h2 class="mt-10 text-lg font-semibold text-gray-900">Latest Incoming</h2>
            <div class="mt-3 space-y-2">
                @forelse ($recent as $sitrep)
                    <a href="{{ route('command.sitreps.show', $sitrep) }}"
                       class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 hover:border-blue-400 transition">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $sitrep->reference_number }}</p>
                            <p class="text-xs text-gray-500">{{ $sitrep->unit->name }} · {{ $sitrep->reportType->name }} · {{ $sitrep->created_at->format('M d, g:i A') }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $sitrep->currentStatus->name }}</span>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 rounded-lg border border-dashed border-gray-300 p-6 text-center">No incoming reports.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>