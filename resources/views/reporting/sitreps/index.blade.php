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
                          {{ is_null($activeStatus) ? 'bg-navy-800 text-white border-navy-800 dark:bg-gold-400 dark:text-navy-900 dark:border-gold-400' : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400' }}">
                    All
                </a>
                @foreach ($statuses as $status)
                    <a href="{{ route('reporting.sitreps.index', ['status' => $status]) }}"
                       class="shrink-0 min-h-[44px] inline-flex items-center px-4 py-2 rounded-full text-sm font-medium border
                              {{ $activeStatus === $status ? 'bg-navy-800 text-white border-navy-800 dark:bg-gold-400 dark:text-navy-900 dark:border-gold-400' : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400' }}">
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
                        $statusName = $sitrep->currentStatus->name;
                        $isEditable = in_array($statusName, ['Draft', 'Returned for Correction']);
                    @endphp

                    {{-- Flex row: info (links to detail) on the left; badge and,
                         for editable reports, the edit action on the right.
                         The right column only takes space when it has content,
                         so the badge always sits flush against the card edge. --}}
                    <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:border-blue-400 hover:shadow-sm sm:flex-row sm:items-center sm:gap-4 dark:border-white/[0.07] dark:bg-[#111A2E] dark:hover:border-blue-400/50">
                        <a href="{{ route('reporting.sitreps.show', $sitrep) }}" class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 truncate">{{ $sitrep->reference_number }}</p>
                            <p class="text-sm text-gray-600 mt-0.5">{{ $sitrep->reportType->name }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                Created {{ $sitrep->created_at->format('M d, Y g:i A') }}
                                @if($sitrep->submitted_at)
                                    · Submitted {{ $sitrep->submitted_at->format('M d, g:i A') }}
                                @endif
                            </p>
                        </a>

                        <div class="flex shrink-0 items-center justify-between gap-3 sm:justify-end">
                            <x-status-badge :status="$statusName" />

                            {{-- Edit action: only for editable states.
                                 Policy 'update' (owner + state) re-checks server-side. --}}
                            @if ($isEditable)
                                <a href="{{ route('reporting.sitreps.edit', $sitrep) }}"
                                   class="inline-flex min-h-[40px] items-center gap-1.5 rounded-lg px-3.5 text-xs font-semibold text-white shadow-sm transition
                                          {{ $statusName === 'Draft'
                                              ? 'bg-navy-800 hover:bg-navy-700 dark:bg-blue-600 dark:hover:bg-blue-500'
                                              : 'bg-red-600 hover:bg-red-700' }}">
                                    <x-icon name="{{ $statusName === 'Draft' ? 'doc' : 'return' }}" class="h-4 w-4" />
                                    {{ $statusName === 'Draft' ? 'Continue editing' : 'Fix & resubmit' }}
                                </a>
                            @endif
                        </div>
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