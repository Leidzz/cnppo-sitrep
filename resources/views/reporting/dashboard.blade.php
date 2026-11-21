<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white truncate">Dashboard</h2>
    </x-slot>

    @push('head')
        <script src="{{ asset('vendor/charts/chart.umd.js') }}"></script>
        <script src="{{ asset('vendor/charts/chart-theme.js') }}"></script>
    @endpush

    @php
        $tintFor = ['Draft' => 'gray', 'Submitted' => 'blue', 'Under Review' => 'amber', 'Validated' => 'green', 'Returned for Correction' => 'red'];
        $iconFor = ['Draft' => 'file', 'Submitted' => 'doc', 'Under Review' => 'clock', 'Validated' => 'check', 'Returned for Correction' => 'return'];
        $subFor  = ['Draft' => 'Not yet submitted', 'Submitted' => 'Waiting for review', 'Under Review' => 'Being validated', 'Validated' => 'Approved', 'Returned for Correction' => 'Needs your correction'];
        $ordered = $statuses->sortBy(fn ($s) => array_search($s->name, array_keys($tintFor)))->values();
        $statusJson = ['labels' => $byStatus->keys()->all(), 'data' => $byStatus->values()->all()];
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-[1400px] mx-auto space-y-6">

            <x-flash />

            <x-page-header :eyebrow="auth()->user()->unit?->name ?? 'No unit assigned'"
                           :title="'Welcome, '.auth()->user()->name"
                           subtitle="Your situation reports at a glance">
                <x-slot name="actions">
                    <a href="{{ route('reporting.sitreps.index') }}"
                       class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 rounded-lg text-sm font-semibold border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">
                        <x-icon name="doc" class="w-4 h-4" /> My Reports
                    </a>
                    <a href="{{ route('reporting.sitreps.create') }}"
                       class="inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-3 rounded-lg text-sm font-semibold bg-blue-600 text-white shadow-sm hover:bg-blue-700 transition">
                        <x-icon name="plus" class="w-4 h-4" /> Create SITREP
                    </a>
                </x-slot>
            </x-page-header>

            {{-- Needs your action: only when the officer has returned reports --}}
            @if ($needsActionCount > 0)
                <section class="anim-rise overflow-hidden rounded-xl border border-red-200 bg-white shadow-card" aria-labelledby="h-action">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-red-100 bg-red-50 px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-700">
                                <x-icon name="alert" class="h-[18px] w-[18px]" />
                            </span>
                            <div>
                                <h2 id="h-action" class="text-sm font-semibold text-red-900">
                                    {{ $needsActionCount }} {{ \Illuminate\Support\Str::plural('report', $needsActionCount) }} need{{ $needsActionCount === 1 ? 's' : '' }} your correction
                                </h2>
                                <p class="text-xs text-red-700/80">Command Center returned these with remarks. Fix and resubmit them.</p>
                            </div>
                        </div>
                        @if ($needsActionCount > $needsAction->count())
                            <a href="{{ route('reporting.sitreps.index', ['status' => 'Returned for Correction']) }}" class="text-xs font-semibold text-red-700 hover:text-red-900">
                                View all {{ $needsActionCount }} →
                            </a>
                        @endif
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach ($needsAction as $sitrep)
                            <li class="flex flex-col gap-3 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-navy-900">
                                        {{ $sitrep->reference_number }}
                                        <span class="ml-1 font-normal text-slate-500">· {{ $sitrep->reportType->name }}</span>
                                    </p>
                                    @if ($sitrep->review_remarks)
                                        <p class="mt-0.5 text-sm text-slate-600 line-clamp-2">“{{ \Illuminate\Support\Str::limit($sitrep->review_remarks, 160) }}”</p>
                                    @endif
                                    @if ($sitrep->reviewed_at)
                                        <p class="mt-0.5 text-xs text-slate-400">Returned {{ $sitrep->reviewed_at->diffForHumans() }}</p>
                                    @endif
                                </div>
                                <a href="{{ route('reporting.sitreps.edit', $sitrep) }}"
                                   class="inline-flex shrink-0 items-center justify-center gap-1.5 min-h-[40px] rounded-lg bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700 transition">
                                    Fix &amp; resubmit <x-icon name="arrow-right" class="h-4 w-4" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- KPI cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach ($ordered as $status)
                    {{-- 5th card spans both columns on phones so it isn't left alone --}}
                    <div class="anim-rise {{ $loop->last && $loop->count % 2 === 1 ? 'col-span-2 sm:col-span-1' : '' }}" style="animation-delay: {{ $loop->index * 60 }}ms">
                        <x-kpi-card
                            :label="$labels[$status->name] ?? $status->name"
                            :count="$counts[$status->id] ?? 0"
                            :icon="$iconFor[$status->name]"
                            :tint="$tintFor[$status->name]"
                            :subtitle="$subFor[$status->name]"
                            :href="route('reporting.sitreps.index', ['status' => $status->name])" />
                    </div>
                @endforeach
            </div>

            {{-- Trend + personal status mix --}}
            <div class="grid lg:grid-cols-12 gap-4">
                <x-chart-card class="anim-rise lg:col-span-8"
                              title="Your Submissions — Last {{ $days }} Days" subtitle="Reports you submitted, by day"
                              chart-id="trend" height="h-64" :empty="array_sum($trendData) === 0"
                              empty-text="You haven't submitted any reports in this period.">
                    <x-slot name="actions">
                        <x-range-toggle :current="$days" />
                    </x-slot>
                </x-chart-card>

                <x-chart-card class="anim-rise lg:col-span-4"
                              title="Your Reports by Status" subtitle="All your SITREPs"
                              chart-id="statusChart" height="h-64" :empty="array_sum($statusJson['data']) === 0" />
            </div>

            {{-- Track record + recent reports --}}
            <div class="grid lg:grid-cols-12 gap-4">
                <x-card class="anim-rise lg:col-span-4" title="Your Track Record" subtitle="All time">
                    <dl class="space-y-4">
                        <div class="flex items-center justify-between gap-4 rounded-lg bg-slate-50 px-4 py-3.5">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Validation rate</dt>
                                <dd class="mt-0.5 text-xs text-slate-500">Accepted on review</dd>
                            </div>
                            <p class="text-2xl font-bold tabular-nums {{ $validationRate === null ? 'text-slate-300' : ($validationRate >= 80 ? 'text-green-700' : ($validationRate >= 60 ? 'text-amber-700' : 'text-red-700')) }}">
                                {{ $validationRate === null ? '—' : rtrim(rtrim(number_format($validationRate, 1), '0'), '.').'%' }}
                            </p>
                        </div>
                        <div class="flex items-center justify-between gap-4 rounded-lg bg-slate-50 px-4 py-3.5">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Avg. time to validation</dt>
                                <dd class="mt-0.5 text-xs text-slate-500">From submit to approval</dd>
                            </div>
                            <p class="text-2xl font-bold tabular-nums text-navy-900">
                                @if ($avgHoursToValidation === null)
                                    <span class="text-slate-300">—</span>
                                @else
                                    {{ rtrim(rtrim(number_format($avgHoursToValidation, 1), '0'), '.') }}<span class="ml-1 text-sm font-semibold text-slate-400">hrs</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center justify-between gap-4 rounded-lg bg-slate-50 px-4 py-3.5">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted</dt>
                                <dd class="mt-0.5 text-xs text-slate-500">Last {{ $days }} days</dd>
                            </div>
                            <p class="text-2xl font-bold tabular-nums text-navy-900">{{ array_sum($trendData) }}</p>
                        </div>
                    </dl>
                    @if ($validationRate === null)
                        <p class="mt-4 text-xs text-slate-500">Your stats appear once Command Center reviews your first report.</p>
                    @endif
                </x-card>

                <x-card class="anim-rise lg:col-span-8" title="Recent Reports" :padded="false">
                    <x-slot name="actions">
                        <a href="{{ route('reporting.sitreps.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-navy-600 hover:text-navy-900">
                            View all <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                        </a>
                    </x-slot>
                    <div class="divide-y divide-slate-100">
                        @forelse ($recent as $sitrep)
                            <a href="{{ route('reporting.sitreps.show', $sitrep) }}"
                               class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50 transition">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-navy-900 truncate">{{ $sitrep->reference_number }}</p>
                                    <p class="text-xs text-slate-500 truncate">
                                        {{ $sitrep->reportType->name }} ·
                                        <time datetime="{{ $sitrep->created_at->toIso8601String() }}" title="{{ $sitrep->created_at->format('M d, Y g:i A') }}">{{ $sitrep->created_at->diffForHumans() }}</time>
                                    </p>
                                </div>
                                <x-status-badge :status="$sitrep->currentStatus->name" class="shrink-0" />
                            </a>
                        @empty
                            <div class="p-5">
                                <x-empty-state>
                                    No reports yet — create your first SITREP.
                                    <x-slot name="action">
                                        <a href="{{ route('reporting.sitreps.create') }}" class="mt-3 inline-block text-sm font-semibold text-blue-700">Create SITREP →</a>
                                    </x-slot>
                                </x-empty-state>
                            </div>
                        @endforelse
                    </div>
                </x-card>
            </div>

        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            const C = window.SitrepCharts;
            C.line('trend', @json($trendLabels), @json($trendData), { label: 'Submitted' });
            C.doughnut('statusChart', @json($statusJson['labels']), @json($statusJson['data']), { statusColors: true, centerLabel: 'Reports' });
        });
    </script>
</x-app-layout>
