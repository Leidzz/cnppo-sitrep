<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white truncate">Command Center</h2>
    </x-slot>

    @push('head')
        <script src="{{ asset('vendor/charts/chart.umd.js') }}"></script>
    @endpush

    @php
        $tintFor = ['Submitted' => 'blue', 'Under Review' => 'amber', 'Validated' => 'green', 'Returned for Correction' => 'red'];
        $iconFor = ['Submitted' => 'doc', 'Under Review' => 'clock', 'Validated' => 'check', 'Returned for Correction' => 'return'];
        $subFor  = ['Submitted' => 'For initial review', 'Under Review' => 'Under validation', 'Validated' => 'Approved SITREPs', 'Returned for Correction' => 'For revision'];
    @endphp

    <div x-data class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-[1400px] mx-auto">

            <x-flash />

            {{-- KPI row --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($statuses as $status)
                    <div class="anim-rise" style="animation-delay: {{ $loop->index * 70 }}ms">
                        <x-stat-card
                            :label="$labels[$status->name] ?? $status->name"
                            :count="$counts[$status->id] ?? 0"
                            :icon="$iconFor[$status->name]"
                            :tint="$tintFor[$status->name]"
                            :subtitle="$subFor[$status->name]"
                            :href="route('command.incoming', ['status' => $status->name])" />
                    </div>
                @endforeach
            </div>

            {{-- Quick filter band --}}
            <form method="GET" action="{{ route('command.reports.index') }}"
                  class="anim-rise mt-6 flex flex-col sm:flex-row gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <select name="unit_id" class="min-h-[44px] rounded-lg border-gray-300 text-sm">
                    <option value="">All units</option>
                    @foreach (\App\Models\Unit::orderBy('name')->get() as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                <select name="report_type_id" class="min-h-[44px] rounded-lg border-gray-300 text-sm">
                    <option value="">All types</option>
                    @foreach (\App\Models\ReportType::where('is_active', true)->orderBy('name')->get() as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" class="min-h-[44px] rounded-lg border-gray-300 text-sm">
                <input type="date" name="date_to" class="min-h-[44px] rounded-lg border-gray-300 text-sm">
                <x-btn type="submit">Filter Archive</x-btn>
            </form>

            {{-- Charts row --}}
            <div class="mt-6 grid lg:grid-cols-3 gap-4">
                <div class="anim-rise lg:col-span-2 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" style="animation-delay: 280ms">
                    <h3 class="text-sm font-semibold text-gray-900">SITREP Submission Trend — Last 7 Days</h3>
                    <div class="mt-3 h-64"><canvas x-ref="trend"></canvas></div>
                </div>
                <div class="anim-rise rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" style="animation-delay: 350ms">
                    <h3 class="text-sm font-semibold text-gray-900">By Report Type</h3>
                    <div class="mt-3 h-64"><canvas x-ref="types"></canvas></div>
                </div>
            </div>

            {{-- Latest incoming --}}
            <div class="anim-rise mt-6 rounded-2xl border border-gray-200 bg-white shadow-sm" style="animation-delay: 420ms">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Latest Incoming</h3>
                    <a href="{{ route('command.incoming') }}" class="text-xs font-semibold text-blue-700 hover:underline">View queue →</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($recent as $sitrep)
                        <a href="{{ route('command.sitreps.show', $sitrep) }}" class="flex items-center justify-between gap-3 px-5 py-3.5 hover:bg-gray-50 transition">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $sitrep->reference_number }}</p>
                                <p class="text-xs text-gray-500">{{ $sitrep->unit->name }} · {{ $sitrep->reportType->name }} · {{ $sitrep->created_at->format('M d, g:i A') }}</p>
                            </div>
                            <x-status-badge :status="$sitrep->currentStatus->name" class="shrink-0" />
                        </a>
                    @empty
                        <div class="px-5 py-8"><x-empty-state>No incoming reports.</x-empty-state></div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('noop', () => ({}));
        });

        // Charts render after Alpine/DOM is ready
        window.addEventListener('load', () => {
            const brand = { blue: '#2563EB', amber: '#F59E0B', green: '#10B981', red: '#EF4444', navy: '#16233F', slate: '#64748B' };

            new Chart(document.querySelector('[x-ref="trend"]'), {
                type: 'line',
                data: {
                    labels: @json($trendLabels),
                    datasets: [{
                        label: 'SITREPs submitted',
                        data: @json($trendData),
                        borderColor: brand.blue,
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        fill: true, tension: 0.35, borderWidth: 2,
                        pointRadius: 3, pointBackgroundColor: brand.blue,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: brand.slate, font: { size: 11 } } },
                        y: { beginAtZero: true, ticks: { precision: 0, color: brand.slate, font: { size: 11 } }, grid: { color: '#F1F5F9' } },
                    },
                },
            });

            new Chart(document.querySelector('[x-ref="types"]'), {
                type: 'doughnut',
                data: {
                    labels: @json($typeLabels),
                    datasets: [{ data: @json($typeData),
                        backgroundColor: [brand.blue, brand.green, brand.amber, brand.red, brand.navy, '#8B5CF6', '#14B8A6'],
                        borderWidth: 2, borderColor: '#ffffff' }],
                },
                options: {
                    maintainAspectRatio: false, cutout: '62%',
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 }, color: brand.slate } } },
                },
            });
        });
    </script>
</x-app-layout>