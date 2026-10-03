<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('My Reports') }}</h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">

            {{-- Status filter tabs: horizontally scrollable on mobile --}}
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0">
                <a href="{{ route('reporting.sitreps.index') }}"
                   class="shrink-0 min-h-[44px] inline-flex items-center px-4 py-2 rounded-full text-sm font-medium border
                          {{ is_null($activeStatus) ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300' }}">
                    All
                </a>
                @foreach ($statuses as $status)
                    <a href="{{ route('reporting.sitreps.index', ['status' => $status]) }}"
                       class="shrink-0 min-h-[44px] inline-flex items-center px-4 py-2 rounded-full text-sm font-medium border
                              {{ $activeStatus === $status ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300' }}">
                        {{ $status }}
                    </a>
                @endforeach
            </div>

            {{-- Cards: main area opens the detail page; editable rows also
                 get a "Continue editing" action. Card is a DIV (not <a>)
                 because links cannot legally nest inside links. --}}
            <div class="mt-6 space-y-3">
                @forelse ($sitreps as $sitrep)
                    @php
                        $badge = match($sitrep->currentStatus->name) {
                            'Draft' => 'bg-gray-100 text-gray-700',
                            'Submitted' => 'bg-blue-100 text-blue-800',
                            'Under Review' => 'bg-amber-100 text-amber-800',
                            'Validated' => 'bg-green-100 text-green-800',
                            'Returned for Correction' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-700',
                        };
                        $isEditable = in_array($sitrep->currentStatus->name, ['Draft', 'Returned for Correction']);
                    @endphp

                    <div class="relative rounded-lg border border-gray-200 bg-white p-4 hover:border-blue-400 hover:shadow-sm transition">
                        {{-- Whole info area links to the detail page --}}
                        <a href="{{ route('reporting.sitreps.show', $sitrep) }}" class="block pr-24 sm:pr-32">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 truncate">{{ $sitrep->reference_number }}</p>
                                    <p class="text-sm text-gray-600 mt-0.5">{{ $sitrep->reportType->name }}</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Created {{ $sitrep->created_at->format('M d, Y g:i A') }}
                                        @if($sitrep->submitted_at)
                                            · Submitted {{ $sitrep->submitted_at->format('M d, g:i A') }}
                                        @endif
                                    </p>
                                </div>
                                <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                                    {{ $sitrep->currentStatus->name }}
                                </span>
                            </div>
                        </a>

                        {{-- Edit action: separate link, only for editable states.
                             Policy 'update' (owner + state) re-checks server-side. --}}
                        @if ($isEditable)
                            <a href="{{ route('reporting.sitreps.edit', $sitrep) }}"
                               class="absolute top-4 right-4 min-h-[44px] inline-flex items-center px-3 py-2 rounded-lg bg-gray-900 text-white text-xs font-semibold hover:bg-gray-700">
                                Continue editing
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">
                        No reports here yet.
                        <a href="{{ route('reporting.sitreps.create') }}" class="text-blue-700 font-semibold">Create your first SITREP</a>
                    </div>
                @endforelse
            </div>

            {{-- Pagination: compact on mobile --}}
            <div class="mt-6">
                {{ $sitreps->links() }}
            </div>
        </div>
    </div>
</x-app-layout>