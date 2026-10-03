<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Incoming SITREPs') }}</h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">

            {{-- Status tabs --}}
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0">
                <a href="{{ route('command.incoming') }}"
                   class="shrink-0 min-h-[44px] inline-flex items-center px-4 py-2 rounded-full text-sm font-medium border
                          {{ is_null($activeStatus) ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300' }}">
                    All
                </a>
                @foreach ($statuses as $status)
                    <a href="{{ route('command.incoming', array_filter(['status' => $status, 'unit_id' => $activeUnit, 'report_type_id' => $activeType])) }}"
                       class="shrink-0 min-h-[44px] inline-flex items-center px-4 py-2 rounded-full text-sm font-medium border
                              {{ $activeStatus === $status ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300' }}">
                        {{ $status }}
                    </a>
                @endforeach
            </div>

            {{-- Filters: unit + report type (GET form keeps it simple/bookmarkable) --}}
            <form method="GET" action="{{ route('command.incoming') }}" class="mt-4 flex flex-col sm:flex-row gap-3">
                @if($activeStatus)
                    <input type="hidden" name="status" value="{{ $activeStatus }}">
                @endif
                <select name="unit_id" class="min-h-[44px] rounded-md border-gray-300 text-sm">
                    <option value="">All units</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected((string)$activeUnit === (string)$unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
                <select name="report_type_id" class="min-h-[44px] rounded-md border-gray-300 text-sm">
                    <option value="">All types</option>
                    @foreach ($reportTypes as $type)
                        <option value="{{ $type->id }}" @selected((string)$activeType === (string)$type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="min-h-[44px] px-5 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold">Filter</button>
            </form>

            {{-- Queue cards --}}
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
                    <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">Nothing in this queue.</div>
                @endforelse
            </div>

            <div class="mt-6">{{ $sitreps->links() }}</div>
        </div>
    </div>
</x-app-layout>