<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('All Reports') }}</h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">

            {{-- Search + filters (single GET form — bookmarkable) --}}
            <form method="GET" action="{{ route('command.reports.index') }}" class="space-y-3">
                <div class="flex gap-2">
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                           placeholder="Search reference number or content…"
                           class="min-h-[44px] flex-1 rounded-md border-gray-300 text-sm">
                    <button type="submit" class="min-h-[44px] px-5 rounded-lg bg-gray-900 text-white text-sm font-semibold">Search</button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <select name="unit_id" class="min-h-[44px] rounded-md border-gray-300 text-sm">
                        <option value="">All units</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected(($filters['unit_id'] ?? '') == $unit->id)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <select name="report_type_id" class="min-h-[44px] rounded-md border-gray-300 text-sm">
                        <option value="">All types</option>
                        @foreach ($reportTypes as $type)
                            <option value="{{ $type->id }}" @selected(($filters['report_type_id'] ?? '') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="min-h-[44px] rounded-md border-gray-300 text-sm" aria-label="From date">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="min-h-[44px] rounded-md border-gray-300 text-sm" aria-label="To date">
                </div>

                <div class="flex flex-wrap gap-2">
                    <select name="status" class="min-h-[44px] rounded-md border-gray-300 text-sm">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <select name="sort" class="min-h-[44px] rounded-md border-gray-300 text-sm">
                        <option value="desc" @selected(($filters['sort'] ?? 'desc') === 'desc')>Newest first</option>
                        <option value="asc" @selected(($filters['sort'] ?? '') === 'asc')>Oldest first</option>
                    </select>
                    <a href="{{ route('command.reports.index') }}" class="min-h-[44px] inline-flex items-center px-4 rounded-lg border border-gray-300 text-sm">Reset</a>
                </div>
            </form>

            {{-- Results --}}
            <div class="mt-6 space-y-3">
                @forelse ($sitreps as $sitrep)
                    <a href="{{ route('command.sitreps.show', $sitrep) }}"
                       class="block rounded-lg border border-gray-200 bg-white p-4 hover:border-blue-400 hover:shadow-sm transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 truncate">{{ $sitrep->reference_number }}</p>
                                <p class="text-sm text-gray-600 mt-0.5">{{ $sitrep->unit->name }} · {{ $sitrep->reportType->name }}</p>
                                <p class="text-xs text-gray-400 mt-1">Submitted {{ $sitrep->submitted_at?->format('M d, Y g:i A') ?? '—' }}</p>
                            </div>
                            <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                {{ match($sitrep->currentStatus->name) {
                                    'Submitted' => 'bg-blue-100 text-blue-800',
                                    'Under Review' => 'bg-amber-100 text-amber-800',
                                    'Validated' => 'bg-green-100 text-green-800',
                                    'Returned for Correction' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-700',
                                } }}">
                                {{ $sitrep->currentStatus->name }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">No reports match these filters.</div>
                @endforelse
            </div>

            <div class="mt-6">{{ $sitreps->links() }}</div>
        </div>
    </div>
</x-app-layout>