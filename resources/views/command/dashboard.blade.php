<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white truncate">Command Center</h2>
    </x-slot>

    @push('head')
        <script src="{{ asset('vendor/charts/chart.umd.js') }}"></script>
        <script src="{{ asset('vendor/charts/chart-theme.js') }}"></script>
    @endpush

    @php
        $tintFor = ['Submitted' => 'blue', 'Under Review' => 'amber', 'Validated' => 'green', 'Returned for Correction' => 'red'];
        $iconFor = ['Submitted' => 'doc', 'Under Review' => 'clock', 'Validated' => 'check', 'Returned for Correction' => 'return'];
        $subFor = [
            'Submitted' => isset($oldest['Submitted']) ? 'Oldest waiting '.$oldest['Submitted']->diffForHumans(null, true) : 'Nothing waiting',
            'Under Review' => isset($oldest['Under Review']) ? 'Oldest waiting '.$oldest['Under Review']->diffForHumans(null, true) : 'Nothing under validation',
            'Validated' => 'Approved, all time',
            'Returned for Correction' => 'Awaiting officer correction',
        ];
        // Workflow order for the KPI row (DB order is not guaranteed)
        $ordered = $statuses->sortBy(fn ($s) => array_search($s->name, array_keys($tintFor)))->values();
        $periodLabel = "vs previous {$days} days";

        // Aging chart datasets
        $agingSets = [
            ['label' => 'Submitted', 'data' => $aging['series']['Submitted'], 'color' => '#2563EB'],
            ['label' => 'Under Review', 'data' => $aging['series']['Under Review'], 'color' => '#D97706'],
        ];
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-[1400px] mx-auto space-y-6">

            <x-flash />

            <x-page-header eyebrow="Command Center" title="Province-wide SITREP Overview"
                           subtitle="Camarines Norte Police Provincial Office">
                <x-slot name="actions">
                    <x-range-toggle :current="$days" />
                    <x-btn variant="outline" onclick="window.print()"><x-icon name="printer" class="w-4 h-4" /> Print</x-btn>
                    <a href="{{ route('command.incoming') }}"
                       class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 rounded-lg text-sm font-semibold bg-navy-800 text-white hover:bg-navy-700 transition">
                        <x-icon name="inbox" class="w-4 h-4" /> Review Queue
                        @if ($newCount > 0)
                            <span class="inline-flex min-w-[22px] justify-center rounded-full bg-gold-400 px-1.5 text-xs font-bold text-navy-900 tabular-nums">{{ $newCount }}</span>
                        @endif
                    </a>
                </x-slot>
            </x-page-header>

            {{-- ===== Current workload (live snapshot) ===== --}}
            <section aria-labelledby="h-workload">
                <h2 id="h-workload" class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Current workload</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($ordered as $status)
                        <div class="anim-rise" style="animation-delay: {{ $loop->index * 60 }}ms">
                            <x-kpi-card
                                :label="$labels[$status->name] ?? $status->name"
                                :count="$counts[$status->id] ?? 0"
                                :icon="$iconFor[$status->name]"
                                :tint="$tintFor[$status->name]"
                                :subtitle="$subFor[$status->name]"
                                :href="route('command.incoming', ['status' => $status->name])" />
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ===== Period performance ===== --}}
            <section aria-labelledby="h-performance">
                <h2 id="h-performance" class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Performance · last {{ $days }} days</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="anim-rise" style="animation-delay:240ms">
                        <x-kpi-card label="SITREPs Received" :count="$performance['received']['count']" icon="inbox" tint="navy"
                                    :delta="$performance['received']['delta']" :delta-label="$periodLabel" :spark="$performance['received']['spark']" />
                    </div>
                    <div class="anim-rise" style="animation-delay:300ms">
                        <x-kpi-card label="Validated" :count="$performance['validated']['count']" icon="check" tint="green"
                                    :delta="$performance['validated']['delta']" :delta-label="$periodLabel" :spark="$performance['validated']['spark']" />
                    </div>
                    <div class="anim-rise" style="animation-delay:360ms">
                        <x-kpi-card label="Returned for Correction" :count="$performance['returned']['count']" icon="return" tint="red"
                                    :delta="$performance['returned']['delta']" :delta-label="$periodLabel" :delta-inverse="true" :spark="$performance['returned']['spark']" />
                    </div>
                    <div class="anim-rise" style="animation-delay:420ms">
                        <x-kpi-card label="Avg. Review Turnaround" :count="$performance['turnaround']['hours']" suffix="hrs" icon="clock" tint="amber"
                                    :delta="$performance['turnaround']['delta']" :delta-label="$periodLabel" :delta-inverse="true"
                                    :subtitle="$performance['turnaround']['hours'] === null ? 'No reviews in this period' : null" />
                    </div>
                </div>
            </section>

            {{-- ===== Trend + validation rate ===== --}}
            <div class="grid lg:grid-cols-12 gap-4">
                <x-chart-card class="anim-rise lg:col-span-8"
                              title="Submitted vs. Validated" subtitle="Daily volume, last {{ $days }} days"
                              chart-id="trendChart" height="h-72"
                              :empty="array_sum($trendData) + array_sum($validatedData) === 0" />

                <x-card class="anim-rise lg:col-span-4" title="Validation Rate"
                        subtitle="Validated ÷ all review decisions, last {{ $days }} days">
                    <div class="relative h-40" data-chart-body>
                        <canvas id="rateGauge" role="img" aria-label="Validation rate {{ $validationRate ?? 'not available' }} percent"></canvas>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-lg bg-green-50 px-3 py-2.5">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-green-700">Validated</dt>
                            <dd class="mt-0.5 text-lg font-bold text-navy-900 tabular-nums">{{ $performance['validated']['count'] }}</dd>
                        </div>
                        <div class="rounded-lg bg-red-50 px-3 py-2.5">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-red-700">Returned</dt>
                            <dd class="mt-0.5 text-lg font-bold text-navy-900 tabular-nums">{{ $performance['returned']['count'] }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-center text-xs text-slate-500">Target: 80% or higher on first review</p>
                </x-card>
            </div>

            {{-- ===== Backlog aging + report type mix ===== --}}
            <div class="grid lg:grid-cols-12 gap-4">
                <x-chart-card class="anim-rise lg:col-span-7"
                              title="Pending Reports by Age" subtitle="Time since submission for reports still awaiting a decision"
                              chart-id="agingChart" height="h-60" :empty="$aging['total'] === 0"
                              empty-text="No pending reports — the queue is clear.">
                    <x-slot name="actions">
                        @if ($aging['overdue'] > 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                                <x-icon name="alert" class="h-3.5 w-3.5" /> {{ $aging['overdue'] }} over 3 days
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-200">
                                <x-icon name="check" class="h-3.5 w-3.5" /> None overdue
                            </span>
                        @endif
                    </x-slot>
                </x-chart-card>

                <x-chart-card class="anim-rise lg:col-span-5"
                              title="Report Type Mix" subtitle="Reports in the review pipeline"
                              chart-id="typeChart" height="h-60" :empty="array_sum($typeData) === 0" />
            </div>

            {{-- ===== Activity heatmap + top units ===== --}}
            <div class="grid lg:grid-cols-12 gap-4">
                <x-card class="anim-rise lg:col-span-7" title="When Reports Come In"
                        subtitle="Submissions by day and hour, last {{ $days }} days">
                    @if ($heatmap['total'] === 0)
                        <div class="flex h-56 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/60 text-center">
                            <x-icon name="activity" class="h-7 w-7 text-slate-300" />
                            <p class="mt-2 text-sm text-slate-500">No data for this period yet.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <div class="min-w-[560px]">
                                <div class="grid grid-cols-[36px_repeat(24,minmax(0,1fr))] gap-[3px]">
                                    @foreach ($heatmap['grid'] as $dow => $hours)
                                        <div class="flex items-center text-[11px] font-medium text-slate-500">{{ $dayNames[$dow] }}</div>
                                        @foreach ($hours as $hr => $n)
                                            @php
                                                $level = $n === 0 ? 0 : (int) ceil($n / max($heatmap['max'], 1) * 4);
                                                $cell = ['bg-slate-100', 'bg-navy-100', 'bg-navy-300', 'bg-navy-500', 'bg-navy-800'][$level];
                                            @endphp
                                            <div class="aspect-square rounded-[3px] {{ $cell }}"
                                                 title="{{ $dayNames[$dow] }} {{ str_pad($hr, 2, '0', STR_PAD_LEFT) }}:00 — {{ $n }} {{ \Illuminate\Support\Str::plural('report', $n) }}"></div>
                                        @endforeach
                                    @endforeach
                                </div>
                                <div class="mt-1 grid grid-cols-[36px_repeat(24,minmax(0,1fr))] gap-[3px] text-[10px] text-slate-400">
                                    <div></div>
                                    @foreach (range(0, 23) as $hr)
                                        <div class="text-center">{{ $hr % 3 === 0 ? ($hr === 0 ? '12a' : ($hr < 12 ? $hr.'a' : ($hr === 12 ? '12p' : ($hr - 12).'p'))) : '' }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @php
                            // Quick reading of the grid for commanders who won't study it
                            $peak = ['n' => -1];
                            foreach ($heatmap['grid'] as $d => $hrs) { foreach ($hrs as $h => $n) { if ($n > $peak['n']) { $peak = ['n' => $n, 'd' => $d, 'h' => $h]; } } }
                            $dayTotals = array_map('array_sum', $heatmap['grid']);
                            $busiestDay = array_search(max($dayTotals), $dayTotals);
                            $hourTotals = array_map(fn ($h) => array_sum(array_column($heatmap['grid'], $h)), range(0, 23));
                            $busiestHour = array_search(max($hourTotals), $hourTotals);
                            $fmtHour = fn ($h) => \Illuminate\Support\Carbon::createFromTime($h)->format('g A');
                        @endphp
                        <dl class="mt-5 grid grid-cols-3 gap-3 border-t border-slate-100 pt-4 text-sm">
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Busiest time</dt>
                                <dd class="mt-0.5 font-semibold text-navy-900">{{ $dayNames[$peak['d']] }} {{ $fmtHour($peak['h']) }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Busiest day</dt>
                                <dd class="mt-0.5 font-semibold text-navy-900">{{ ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$busiestDay] }}</dd>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Peak hour</dt>
                                <dd class="mt-0.5 font-semibold text-navy-900">{{ $fmtHour($busiestHour) }}–{{ $fmtHour(($busiestHour + 1) % 24) }}</dd>
                            </div>
                        </dl>
                        <div class="mt-3 flex items-center justify-end gap-2 text-[11px] text-slate-500">
                            Fewer
                            @foreach (['bg-slate-100', 'bg-navy-100', 'bg-navy-300', 'bg-navy-500', 'bg-navy-800'] as $c)
                                <span class="h-3 w-3 rounded-[3px] {{ $c }}"></span>
                            @endforeach
                            More
                        </div>
                    @endif
                </x-card>

                <x-chart-card class="anim-rise lg:col-span-5"
                              title="Reports by Unit" subtitle="Top 10 stations, last {{ $days }} days"
                              chart-id="unitChart" height="h-72" :empty="array_sum($unitChart['data']) === 0" />
            </div>

            {{-- ===== Latest incoming + archive search ===== --}}
            <div class="grid lg:grid-cols-12 gap-4">
                <x-card class="anim-rise lg:col-span-8" title="Latest Incoming"
                        subtitle="Newest reports awaiting action" :padded="false">
                    <x-slot name="actions">
                        <a href="{{ route('command.incoming') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-navy-600 hover:text-navy-900">
                            View queue <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                        </a>
                    </x-slot>
                    <div class="divide-y divide-slate-100">
                        @forelse ($recent as $sitrep)
                            <a href="{{ route('command.sitreps.show', $sitrep) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-slate-50 transition">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-navy-900 truncate">{{ $sitrep->reference_number }}</p>
                                        <span class="hidden sm:inline-flex rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600">{{ $sitrep->reportType->name }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 truncate">
                                        {{ $sitrep->unit->name }} ·
                                        <time datetime="{{ ($sitrep->submitted_at ?? $sitrep->created_at)->toIso8601String() }}"
                                              title="{{ ($sitrep->submitted_at ?? $sitrep->created_at)->format('M d, Y g:i A') }}">
                                            {{ ($sitrep->submitted_at ?? $sitrep->created_at)->diffForHumans() }}
                                        </time>
                                    </p>
                                </div>
                                <x-status-badge :status="$sitrep->currentStatus->name" class="shrink-0" />
                            </a>
                        @empty
                            <div class="px-5 py-8"><x-empty-state>No incoming reports.</x-empty-state></div>
                        @endforelse
                    </div>
                </x-card>

                <x-card class="anim-rise lg:col-span-4 print:hidden" title="Search the Archive" subtitle="Find past SITREPs by unit, type or date">
                    <form method="GET" action="{{ route('command.reports.index') }}" class="space-y-3">
                        <div>
                            <label for="f-unit" class="mb-1 block text-xs font-semibold text-slate-600">Unit</label>
                            <select id="f-unit" name="unit_id" class="w-full min-h-[44px] rounded-lg border-slate-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                                <option value="">All units</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="f-type" class="mb-1 block text-xs font-semibold text-slate-600">Report type</label>
                            <select id="f-type" name="report_type_id" class="w-full min-h-[44px] rounded-lg border-slate-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                                <option value="">All types</option>
                                @foreach ($reportTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="f-from" class="mb-1 block text-xs font-semibold text-slate-600">From</label>
                                <input id="f-from" type="date" name="date_from" class="w-full min-h-[44px] rounded-lg border-slate-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                            </div>
                            <div>
                                <label for="f-to" class="mb-1 block text-xs font-semibold text-slate-600">To</label>
                                <input id="f-to" type="date" name="date_to" class="w-full min-h-[44px] rounded-lg border-slate-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                            </div>
                        </div>
                        <x-btn type="submit" class="w-full">Filter Archive</x-btn>
                    </form>
                </x-card>
            </div>

        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            const C = window.SitrepCharts;
            C.multiLine('trendChart', @json($trendLabels), [
                { label: 'Submitted', data: @json($trendData), color: C.colors.blue },
                { label: 'Validated', data: @json($validatedData), color: C.colors.green, dashed: true },
            ]);
            C.gauge('rateGauge', @json($validationRate), { label: 'Validation rate' });
            C.stackedBar('agingChart', @json($aging['buckets']), @json($agingSets));
            C.doughnut('typeChart', @json($typeLabels), @json($typeData), { centerLabel: 'In pipeline' });
            C.bar('unitChart', @json($unitChart['labels']), @json($unitChart['data']), { horizontal: true });
        });
    </script>
</x-app-layout>
