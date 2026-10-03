<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Operations Map') }}</h2>
    </x-slot>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        {{-- Marker clustering: nearby/overlapping markers collapse into a numbered badge --}}
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
        <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    @endpush

    <div x-data="{
        map: null,
        layer: null,
        loading: false,
        empty: false,

        init() {
            // Center on Camarines Norte, wide zoom first; fitted to markers after load
            this.map = L.map('ops-map').setView([14.1, 122.9], 10);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors',
            }).addTo(this.map);
            // Clustering: 20 reports at one checkpoint show as ONE badge
            // with a count, instead of stacked invisible pins.
            this.layer = L.markerClusterGroup().addTo(this.map);
            this.load(null);
        },

        async load(status) {
            this.loading = true;
            const url = '{{ route('command.map.data') }}' + (status ? '?status=' + encodeURIComponent(status) : '');
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const { markers } = await res.json();

            this.layer.clearLayers();
            this.empty = markers.length === 0;

            const bounds = [];
            for (const m of markers) {
                const marker = L.marker([m.lat, m.lng]).addTo(this.layer);
                marker.bindPopup(`
                    <div style='font-size:13px; min-width:180px'>
                        <strong>${m.reference}</strong><br>
                        ${m.unit} · ${m.type}<br>
                        Status: ${m.status}<br>
                        Location captured (device): ${m.captured_at ?? '—'}<br>
                        <a href='${m.url}'>Open report</a>
                    </div>`);

                // Accuracy circle: honest uncertainty. A ±150m desktop fix
                // renders as a ~300m circle, NOT a falsely-precise pin.
                if (m.accuracy) {
                    L.circle([m.lat, m.lng], {
                        radius: m.accuracy,
                        color: '#3b82f6',
                        weight: 1,
                        opacity: 0.5,
                        fillColor: '#3b82f6',
                        fillOpacity: 0.08,
                    }).addTo(this.layer);
                }

                bounds.push([m.lat, m.lng]);
            }
            if (bounds.length > 0) this.map.fitBounds(bounds, { padding: [30, 30], maxZoom: 14 });
            this.loading = false;
        },
    }" class="h-full flex flex-col">

        {{-- Filter bar --}}
        <div class="px-4 py-3 bg-white border-b border-gray-200 flex items-center gap-2 overflow-x-auto">
            <button @click="load(null)"
                    class="shrink-0 min-h-[44px] px-4 py-2 rounded-full text-sm font-medium border border-gray-300">All</button>
            @foreach ($statuses as $status)
                <button @click="load('{{ $status }}')"
                        class="shrink-0 min-h-[44px] px-4 py-2 rounded-full text-sm font-medium border border-gray-300">
                    {{ $status }}
                </button>
            @endforeach
            <span x-show="loading" x-cloak class="text-sm text-gray-400 ml-2">Loading…</span>
        </div>

        {{-- Map fills remaining height; short screens still usable --}}
        <div id="ops-map" class="flex-1 min-h-[60vh] z-0"></div>

        <div x-show="empty" x-cloak
             class="absolute inset-x-4 top-24 mx-auto max-w-sm rounded-lg bg-white border border-gray-200 p-4 text-center text-sm text-gray-500 shadow">
            No location-captured reports in this filter.
        </div>
    </div>
</x-app-layout>