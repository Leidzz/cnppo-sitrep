<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">

            {{-- Flash message after creating/submitting/updating a SITREP --}}
            @if (session('status'))
                <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                Welcome, {{ auth()->user()->name }}
            </h1>
            <p class="mt-2 text-base text-gray-600">
                {{ auth()->user()->unit?->name ?? 'No unit assigned' }}
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('reporting.sitreps.create') }}"
                   class="min-h-[44px] inline-flex items-center px-6 py-3 rounded-lg bg-blue-700 text-white text-base font-semibold shadow hover:bg-blue-800">
                    + Create SITREP
                </a>
                <a href="{{ route('reporting.sitreps.index') }}"
                   class="min-h-[44px] inline-flex items-center px-6 py-3 rounded-lg border border-gray-300 bg-white text-base font-semibold text-gray-700 hover:bg-gray-50">
                    My Reports
                </a>
            </div>

            {{-- Status counts: 2 columns on mobile, 5 on desktop.
                 Every card links to the filtered list — count to drill-down
                 in one tap. Counts come from the controller's grouped query. --}}
            <div class="mt-8 grid grid-cols-2 sm:grid-cols-5 gap-3">
                @foreach ($statuses as $status)
                    <a href="{{ route('reporting.sitreps.index', ['status' => $status->name]) }}"
                       class="rounded-lg border border-gray-200 bg-white p-4 hover:border-blue-400 transition">
                        <p class="text-2xl font-bold text-gray-900">{{ $counts[$status->id] ?? 0 }}</p>
                        <p class="mt-1 text-xs font-medium text-gray-500">{{ $status->name }}</p>
                    </a>
                @endforeach
            </div>

            {{-- Recent reports (max 5, from controller) --}}
            <h2 class="mt-10 text-lg font-semibold text-gray-900">Recent Reports</h2>
            <div class="mt-3 space-y-2">
                @forelse ($recent as $sitrep)
                    <a href="{{ route('reporting.sitreps.show', $sitrep) }}"
                       class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 hover:border-blue-400 transition">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $sitrep->reference_number }}</p>
                            <p class="text-xs text-gray-500">{{ $sitrep->reportType->name }} · {{ $sitrep->created_at->format('M d, g:i A') }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">
                            {{ $sitrep->currentStatus->name }}
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 rounded-lg border border-dashed border-gray-300 p-6 text-center">
                        No reports yet — create your first SITREP.
                    </p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>