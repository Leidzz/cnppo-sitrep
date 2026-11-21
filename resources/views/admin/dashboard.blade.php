<x-app-layout>
    <x-slot name="header"><h2 class="text-lg font-semibold text-white truncate">Admin Dashboard</h2></x-slot>

    @push('head')
        <script src="{{ asset('vendor/charts/chart.umd.js') }}"></script>
        <script src="{{ asset('vendor/charts/chart-theme.js') }}"></script>
    @endpush

    @php
        // Precompute chart data OUTSIDE the script — multiline closures in
        // @json arguments break Blade's directive parser (learned in Phase 5).
        $statusJson = ['labels' => $byStatus->keys()->all(), 'data' => $byStatus->values()->all()];
        $typeJson = ['labels' => $byType->keys()->all(), 'data' => $byType->values()->all()];
        $roleJson = ['labels' => $roles->map(fn ($r) => \Illuminate\Support\Str::headline($r->name))->all(), 'data' => $roles->pluck('users_count')->all()];
        $hasSitreps = $sitrepCount > 0;
        $inactiveUsers = $userCount - $activeUsers;

        // Unit table rows as plain arrays for Alpine sorting
        $unitRows = $unitPerformance->map(fn ($u) => (array) $u)->values()->all();

        $actionLabels = [
            'created' => 'created',
            'updated' => 'updated',
            'status_changed' => 'changed status of',
        ];
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-[1400px] mx-auto space-y-6">
            <x-flash />

            <x-page-header eyebrow="System Administration" title="Overview"
                           subtitle="Province-wide activity across all units">
                <x-slot name="actions">
                    <x-range-toggle :current="$days" />
                    <x-btn variant="outline" onclick="window.print()"><x-icon name="printer" class="w-4 h-4" /> Print</x-btn>
                </x-slot>
            </x-page-header>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="anim-rise"><x-kpi-card label="Users" :count="$userCount" icon="users" tint="navy"
                    :subtitle="$activeUsers.' active · '.$inactiveUsers.' inactive'" :href="route('admin.users.index')" /></div>
                <div class="anim-rise" style="animation-delay:60ms"><x-kpi-card label="Units" :count="$unitCount" icon="building" tint="green"
                    subtitle="Stations and companies" :href="route('admin.units.index')" /></div>
                <div class="anim-rise" style="animation-delay:120ms"><x-kpi-card label="Total SITREPs" :count="$sitrepCount" icon="doc" tint="blue"
                    :delta="$receivedDelta" :delta-label="$received.' received in '.$days.' days'"
                    :subtitle="$received.' received in last '.$days.' days'" /></div>
                <div class="anim-rise" style="animation-delay:180ms"><x-kpi-card label="GPS-Tagged Reports" :count="$gpsPct" suffix="%" icon="pin" tint="amber"
                    :subtitle="$gpsPct === null ? 'No submitted reports yet' : 'Submitted reports with a location'" /></div>
            </div>

            {{-- Trend + roles --}}
            <div class="grid lg:grid-cols-12 gap-4">
                <x-chart-card class="anim-rise lg:col-span-8"
                              title="Submissions — Last {{ $days }} Days" subtitle="All units, by submission date"
                              chart-id="trendChart" height="h-64" :empty="array_sum($trendData) === 0" />

                <x-chart-card class="anim-rise lg:col-span-4"
                              title="Users by Role" subtitle="Accounts per access level"
                              chart-id="roleChart" height="h-64" :empty="array_sum($roleJson['data']) === 0" />
            </div>

            {{-- Funnel + status + type --}}
            <div class="grid lg:grid-cols-3 gap-4">
                <x-card class="anim-rise" title="Workflow Funnel" subtitle="How far reports get, all time">
                    @if (! $hasSitreps)
                        <div class="flex h-56 flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/60 text-center">
                            <x-icon name="chart" class="h-7 w-7 text-slate-300" />
                            <p class="mt-2 text-sm text-slate-500">No data for this period yet.</p>
                        </div>
                    @else
                        <ol class="space-y-4">
                            @foreach ($funnel as $i => $stage)
                                <li>
                                    <div class="flex items-baseline justify-between text-sm">
                                        <span class="font-medium text-navy-900">{{ $stage['label'] }}</span>
                                        <span class="tabular-nums text-slate-600">
                                            <span class="font-semibold text-navy-900">{{ number_format($stage['count']) }}</span>
                                            <span class="text-xs text-slate-400">· {{ rtrim(rtrim(number_format($stage['pct'], 1), '0'), '.') }}%</span>
                                        </span>
                                    </div>
                                    <div class="mt-1.5 h-2.5 rounded-full bg-slate-100">
                                        <div class="h-2.5 rounded-full {{ ['bg-navy-300', 'bg-status-submitted', 'bg-status-review', 'bg-status-validated'][$i] }}" style="width: {{ max($stage['pct'], 1) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                        @php $drafts = $funnel[0]['count'] - $funnel[1]['count']; @endphp
                        <p class="mt-5 border-t border-slate-100 pt-3 text-xs text-slate-500">
                            {{ number_format($drafts) }} {{ \Illuminate\Support\Str::plural('draft', $drafts) }} never submitted.
                        </p>
                    @endif
                </x-card>

                <x-chart-card class="anim-rise" title="By Status" subtitle="Current workflow stage"
                              chart-id="statusChart" height="h-64" :empty="! $hasSitreps" />

                <x-chart-card class="anim-rise" title="By Report Type" subtitle="All SITREPs"
                              chart-id="typeChart" height="h-64" :empty="! $hasSitreps" />
            </div>

            {{-- Unit performance + activity --}}
            <div class="grid lg:grid-cols-12 gap-4">
                <x-card class="anim-rise lg:col-span-8" title="Unit Performance"
                        subtitle="Reports submitted in the last {{ $days }} days · click a column to sort" :padded="false">
                    <div x-data="unitTable(@js($unitRows))" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    @foreach (['name' => 'Unit', 'total' => 'Total', 'validated' => 'Validated', 'returned' => 'Returned', 'pending' => 'Pending', 'rate' => 'Valid. rate', 'avg_hours' => 'Avg. review'] as $key => $head)
                                        <th scope="col" class="px-4 py-2.5 {{ $key === 'name' ? '' : 'text-right' }}">
                                            <button type="button" @click="sort('{{ $key }}')" class="inline-flex items-center gap-1 uppercase hover:text-navy-900">
                                                {{ $head }}
                                                <span class="text-slate-300" x-text="key === '{{ $key }}' ? (dir === 'asc' ? '▲' : '▼') : ''"></span>
                                            </button>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 tabular-nums">
                                {{-- Rows rendered and re-ordered by Alpine (unitTable below) --}}
                                <template x-for="u in sorted" :key="u.name">
                                    <tr class="hover:bg-slate-50" :class="u.total > 0 && u.rate !== null && u.rate < 60 ? 'bg-red-50/40' : ''">
                                        <td class="min-w-[12rem] px-4 py-2.5 font-medium text-navy-900" x-text="u.name"></td>
                                        <td class="px-4 py-2.5 text-right font-semibold text-navy-900" x-text="u.total"></td>
                                        <td class="px-4 py-2.5 text-right text-slate-700" x-text="u.validated"></td>
                                        <td class="px-4 py-2.5 text-right text-slate-700" x-text="u.returned"></td>
                                        <td class="px-4 py-2.5 text-right text-slate-700" x-text="u.pending"></td>
                                        <td class="px-4 py-2.5 text-right">
                                            <span x-show="u.rate !== null" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                                  :class="u.rate >= 80 ? 'bg-green-50 text-green-700' : (u.rate >= 60 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700')"
                                                  x-text="u.rate + '%'"></span>
                                            <span x-show="u.rate === null" class="text-slate-300">—</span>
                                        </td>
                                        <td class="px-4 py-2.5 whitespace-nowrap text-right text-slate-700" x-text="u.avg_hours === null ? '—' : u.avg_hours + ' hrs'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <x-slot name="footer">
                        Validation rate = validated ÷ (validated + returned). Rows under 60% are highlighted.
                    </x-slot>
                </x-card>

                <x-card class="anim-rise lg:col-span-4" title="Recent System Activity" subtitle="Latest audit log entries" :padded="false">
                    <ul class="divide-y divide-slate-100">
                        @forelse ($activity as $log)
                            <li class="flex gap-3 px-5 py-3">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full
                                    {{ match ($log->new_status) {
                                        'Validated' => 'bg-status-validated',
                                        'Returned for Correction' => 'bg-status-returned',
                                        'Under Review' => 'bg-status-review',
                                        'Submitted' => 'bg-status-submitted',
                                        default => 'bg-status-draft',
                                    } }}"></span>
                                <div class="min-w-0 text-sm">
                                    <p class="text-slate-700">
                                        <span class="font-semibold text-navy-900">{{ $log->user?->name ?? 'System' }}</span>
                                        {{ $actionLabels[$log->action] ?? str_replace('_', ' ', $log->action) }}
                                        <span class="font-medium text-navy-900 whitespace-nowrap">{{ $log->sitrep?->reference_number ?? 'a report' }}</span>
                                        @if ($log->action === 'status_changed' && $log->new_status)
                                            → {{ $log->new_status }}
                                        @endif
                                    </p>
                                    @php
                                        // AuditLog has $timestamps = false and no cast, so created_at arrives as a string
                                        $at = $log->created_at ? \Illuminate\Support\Carbon::parse($log->created_at) : null;
                                    @endphp
                                    @if ($at)
                                        <p class="mt-0.5 text-xs text-slate-400">
                                            <time datetime="{{ $at->toIso8601String() }}" title="{{ $at->format('M d, Y g:i A') }}">{{ $at->diffForHumans() }}</time>
                                        </p>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-8"><x-empty-state>No activity recorded yet.</x-empty-state></li>
                        @endforelse
                    </ul>
                </x-card>
            </div>
        </div>
    </div>

    <script>
        // Sortable unit table (Alpine). Null values always sort last.
        document.addEventListener('alpine:init', () => {
            Alpine.data('unitTable', (rows) => ({
                rows, key: 'total', dir: 'desc',
                sort(k) { this.dir = this.key === k && this.dir === 'desc' ? 'asc' : 'desc'; this.key = k; },
                get sorted() {
                    const { key, dir } = this;
                    return [...this.rows].sort((a, b) => {
                        const x = a[key], y = b[key];
                        if (x === null) return 1;
                        if (y === null) return -1;
                        const c = typeof x === 'string' ? x.localeCompare(y) : x - y;
                        return dir === 'asc' ? c : -c;
                    });
                },
            }));
        });

        window.addEventListener('load', () => {
            const C = window.SitrepCharts;
            C.line('trendChart', @json($trendLabels), @json($trendData), { label: 'SITREPs' });
            C.doughnut('roleChart', @json($roleJson['labels']), @json($roleJson['data']), { centerLabel: 'Users' });
            C.doughnut('statusChart', @json($statusJson['labels']), @json($statusJson['data']), { statusColors: true, centerLabel: 'Total' });
            C.bar('typeChart', @json($typeJson['labels']), @json($typeJson['data']));
        });
    </script>
</x-app-layout>
