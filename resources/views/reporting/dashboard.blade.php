<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white truncate">Dashboard</h2>
    </x-slot>

    @push('head')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @endpush

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-[1400px] mx-auto">

            <x-flash />

            <h1 class="text-xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ auth()->user()->unit?->name ?? 'No unit assigned' }}</p>

            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('reporting.sitreps.create') }}"
                   class="inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-3 rounded-lg text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    <x-icon name="plus" class="w-4 h-4" /> Create SITREP
                </a>
                <a href="{{ route('reporting.sitreps.index') }}"
                   class="inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-3 rounded-lg text-sm font-semibold border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition">
                    My Reports
                </a>
            </div>

            {{-- KPI cards --}}
            @php
                $tintFor = ['Draft' => 'gray', 'Submitted' => 'blue', 'Under Review' => 'amber', 'Validated' => 'green', 'Returned for Correction' => 'red'];
                $iconFor = ['Draft' => 'file', 'Submitted' => 'doc', 'Under Review' => 'clock', 'Validated' => 'check', 'Returned for Correction' => 'return'];
            @endphp
            <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                @foreach ($statuses as $status)
                    <div class="anim-rise" style="animation-delay: {{ $loop->index * 70 }}ms">
                        <x-stat-card
                            :label="$labels[$status->name] ?? $status->name"
                            :count="$counts[$status->id] ?? 0"
                            :icon="$iconFor[$status->name]"
                            :tint="$tintFor[$status->name]"
                            :href="route('reporting.sitreps.index', ['status' => $status->name])" />
                    </div>
                @endforeach
            </div>

            {{-- Personal submission trend (Design v3) --}}
            <div class="anim-rise mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" style="animation-delay: 350ms">
                <h3 class="text-sm font-semibold text-gray-900">Your Submissions — Last 7 Days</h3>
                <div class="mt-3 h-56"><canvas id="trend"></canvas></div>
            </div>

            <h2 class="mt-8 text-base font-semibold text-gray-900">Recent Reports</h2>
            <div class="mt-3 space-y-2">
                @forelse ($recent as $sitrep)
                    <a href="{{ route('reporting.sitreps.show', $sitrep) }}"
                       class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 hover:border-blue-400 hover:shadow-sm transition">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $sitrep->reference_number }}</p>
                            <p class="text-xs text-gray-500">{{ $sitrep->reportType->name }} · {{ $sitrep->created_at->format('M d, g:i A') }}</p>
                        </div>
                        <x-status-badge :status="$sitrep->currentStatus->name" class="shrink-0" />
                    </a>
                @empty
                    <x-empty-state>
                        No reports yet — create your first SITREP.
                        <x-slot name="action">
                            <a href="{{ route('reporting.sitreps.create') }}" class="mt-3 inline-block text-sm font-semibold text-blue-700">Create SITREP →</a>
                        </x-slot>
                    </x-empty-state>
                @endforelse
            </div>

        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            new Chart(document.getElementById('trend'), {
                type: 'line',
                data: {
                    labels: @json($trendLabels),
                    datasets: [{
                        label: 'Submitted',
                        data: @json($trendData),
                        borderColor: '#2563EB',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        fill: true, tension: 0.35, borderWidth: 2,
                        pointRadius: 3, pointBackgroundColor: '#2563EB',
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#64748B', font: { size: 11 } } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: '#64748B', font: { size: 11 } }, grid: { color: '#F1F5F9' } },
                    },
                },
            });
        });
    </script>
</x-app-layout>