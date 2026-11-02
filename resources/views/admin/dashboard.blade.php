<x-app-layout>
    <x-slot name="header"><h2 class="text-lg font-semibold text-white truncate">Admin Dashboard</h2></x-slot>

    @push('head')
        <script src="{{ asset('vendor/charts/chart.umd.js') }}"></script>
    @endpush

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-[1400px] mx-auto">
            <x-flash />

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="anim-rise"><x-stat-card label="Users" :count="$userCount" icon="users" tint="blue" :href="route('admin.users.index')" /></div>
                <div class="anim-rise" style="animation-delay:70ms"><x-stat-card label="Units" :count="$unitCount" icon="flag" tint="green" :href="route('admin.units.index')" /></div>
                <div class="anim-rise" style="animation-delay:140ms"><x-stat-card label="Total SITREPs" :count="$sitrepCount" icon="doc" tint="amber" /></div>
                <div class="anim-rise" style="animation-delay:210ms"><x-stat-card label="Roles" :count="$roles->count()" icon="cog" tint="gray" /></div>
            </div>

            {{-- Analytics row --}}
            <div class="mt-6 grid lg:grid-cols-3 gap-4">
                <div class="anim-rise lg:col-span-2 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" style="animation-delay:280ms">
                    <h3 class="text-sm font-semibold text-gray-900">Submissions — Last 7 Days (all units)</h3>
                    <div class="mt-3 h-56"><canvas id="trendChart"></canvas></div>
                </div>
                <div class="anim-rise rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" style="animation-delay:350ms">
                    <h3 class="text-sm font-semibold text-gray-900">By Status</h3>
                    <div class="mt-3 h-56"><canvas id="statusChart"></canvas></div>
                </div>
                <div class="anim-rise rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" style="animation-delay:420ms">
                    <h3 class="text-sm font-semibold text-gray-900">By Report Type</h3>
                    <div class="mt-3 h-64"><canvas id="typeChart"></canvas></div>
                </div>
                <div class="anim-rise rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" style="animation-delay:490ms">
                    <h3 class="text-sm font-semibold text-gray-900">By Unit</h3>
                    <div class="mt-3 h-64"><canvas id="unitChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    @php
        // Precompute chart data OUTSIDE the script — multiline closures in
        // @json arguments break Blade's directive parser (learned in Phase 5).
        $statusJson = ['labels' => $byStatus->keys()->all(), 'data' => $byStatus->values()->all()];
        $typeJson = ['labels' => $byType->keys()->all(), 'data' => $byType->values()->all()];
        $unitJson = ['labels' => $byUnit->keys()->all(), 'data' => $byUnit->values()->all()];
    @endphp

    <script>
        window.addEventListener('load', () => {
            const palette = ['#2563EB', '#10B981', '#F59E0B', '#EF4444', '#16233F', '#8B5CF6', '#14B8A6'];
            const grid = { color: '#F1F5F9' };
            const tick = { color: '#64748B', font: { size: 11 } };
            const noData = (c) => c.data.labels.length === 0;

            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: { labels: @json($trendLabels), datasets: [{ label: 'SITREPs', data: @json($trendData),
                    borderColor: '#2563EB', backgroundColor: 'rgba(37,99,235,0.08)', fill: true, tension: 0.35, borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#2563EB' }] },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } },
                    scales: { x: { grid: { display: false }, ticks: tick }, y: { beginAtZero: true, ticks: { ...tick, precision: 0 }, grid } } },
            });

            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: { labels: @json($statusJson['labels']), datasets: [{ data: @json($statusJson['data']),
                    backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }] },
                options: { maintainAspectRatio: false, cutout: '60%',
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 }, color: '#64748B' } } } },
            });

            new Chart(document.getElementById('typeChart'), {
                type: 'bar',
                data: { labels: @json($typeJson['labels']), datasets: [{ data: @json($typeJson['data']),
                    backgroundColor: '#2563EB', borderRadius: 6 }] },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } },
                    scales: { x: { grid: { display: false }, ticks: { ...tick, autoSkip: false, maxRotation: 45 } }, y: { beginAtZero: true, ticks: { ...tick, precision: 0 }, grid } } },
            });

            new Chart(document.getElementById('unitChart'), {
                type: 'bar',
                data: { labels: @json($unitJson['labels']), datasets: [{ data: @json($unitJson['data']),
                    backgroundColor: '#10B981', borderRadius: 6 }] },
                options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { ...tick, precision: 0 }, grid }, y: { grid: { display: false }, ticks: tick } } },
            });
        });
    </script>
</x-app-layout>