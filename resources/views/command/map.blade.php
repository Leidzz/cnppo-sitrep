<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Operations Map') }}</h2>
    </x-slot>

    @push('head')
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/MarkerCluster.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/MarkerCluster.Default.css') }}">
        <script src="{{ asset('vendor/leaflet/leaflet.markercluster.js') }}"></script>
        <style>
            /* Status-colored pin (divIcon) */
            .sitrep-pin { width: 28px; height: 28px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg);
                          border: 3px solid #fff; box-shadow: 0 2px 6px rgba(15,26,48,.45); display: flex; align-items: center; justify-content: center; }
            .sitrep-pin span { width: 8px; height: 8px; border-radius: 50%; background: #fff; transform: rotate(45deg); }
            /* Popup */
            .leaflet-popup-content-wrapper { border-radius: 12px; }
            .leaflet-popup-content { margin: 12px 14px; font-family: "Public Sans", ui-sans-serif, system-ui, sans-serif; }
            /* Layer switcher reads as part of the UI */
            .leaflet-control-layers { border-radius: 10px !important; border: 1px solid rgba(15,26,48,.12) !important; box-shadow: 0 4px 12px rgba(15,26,48,.12) !important; }
            .leaflet-control-layers-expanded { padding: 8px 12px; font: 600 12px "Public Sans", ui-sans-serif, system-ui, sans-serif; }
        </style>
    @endpush

    @php
        // Same status colors as the badges and charts (tailwind `status` tokens)
        $statusColors = [
            'Submitted' => '#2563EB',
            'Under Review' => '#D97706',
            'Validated' => '#16A34A',
            'Returned for Correction' => '#DC2626',
        ];
    @endphp

    <script>
        // Operations map (Alpine component). Kept in a <script> rather than an
        // x-data="" attribute: browsers decode HTML entities inside attributes,
        // which would silently break the popup escaping helper.
        document.addEventListener('alpine:init', () => {
            Alpine.data('opsMap', (colors, dataUrl) => ({
            map: null,
            clusters: null,
            accuracy: null,
            status: null,
            count: 0,
            loading: false,
            empty: false,
            emptyTimer: null,
            colors,

            init() {
                // ---- Base maps ------------------------------------------------
                // Streets: OpenStreetMap standard (most detailed street labels)
                // Satellite: Esri World Imagery (aerial photos)
                // Hybrid: satellite + roads + place names (default — most detail)
                const esri = 'https://server.arcgisonline.com/ArcGIS/rest/services/';
                const esriAttr = 'Imagery © Esri, Maxar, Earthstar Geographics';
                const streets = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 20, maxNativeZoom: 19, attribution: '© OpenStreetMap contributors',
                });
                const imagery = () => L.tileLayer(esri + 'World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 20, maxNativeZoom: 19, attribution: esriAttr,
                });
                const satellite = imagery();
                const hybrid = L.layerGroup([
                    imagery(),
                    L.tileLayer(esri + 'Reference/World_Transportation/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20, maxNativeZoom: 19 }),
                    L.tileLayer(esri + 'Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20, maxNativeZoom: 19 }),
                ]);
                const bases = { 'Hybrid (satellite + labels)': hybrid, 'Satellite': satellite, 'Streets': streets };

                // Remember the officer's last choice
                let saved = null;
                try { saved = localStorage.getItem('map-base'); } catch (e) {}
                const start = bases[saved] ?? hybrid;

                // Center on Camarines Norte, wide zoom first; fitted to markers after load
                this.map = L.map('ops-map', { layers: [start], zoomControl: true }).setView([14.1, 122.9], 10);
                L.control.layers(bases, null, { position: 'topright', collapsed: window.innerWidth < 640 }).addTo(this.map);
                L.control.scale({ imperial: false }).addTo(this.map);
                this.map.on('baselayerchange', (e) => { try { localStorage.setItem('map-base', e.name); } catch (err) {} });

                // ---- Report layers --------------------------------------------
                // Clustering: many reports at one checkpoint show as ONE badge with
                // a count. Clusters break apart from zoom 17, and markers at the
                // exact same spot fan out ("spiderfy") when the badge is clicked.
                this.clusters = L.markerClusterGroup({
                    disableClusteringAtZoom: 17,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    maxClusterRadius: 50,
                }).addTo(this.map);

                // Accuracy circles live in their OWN layer. (They used to sit in the
                // cluster group, which counted each circle as an extra report.)
                // Shown only when zoomed in enough for the radius to mean something.
                this.accuracy = L.layerGroup();
                const syncAccuracy = () => {
                    if (this.map.getZoom() >= 15) this.accuracy.addTo(this.map);
                    else this.accuracy.remove();
                };
                this.map.on('zoomend', syncAccuracy);
                syncAccuracy();

                this.load(null);
            },

            pin(color) {
                return L.divIcon({
                    className: '',
                    html: `<div class='sitrep-pin' style='background:${color}'><span></span></div>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 28],
                    popupAnchor: [0, -26],
                });
            },

            // Escape server values before putting them into popup HTML
            esc(v) {
                const div = document.createElement('div');
                div.textContent = String(v ?? '');
                return div.innerHTML;
            },

            async load(status) {
                this.status = status;
                this.loading = true;
                const url = dataUrl + (status ? '?status=' + encodeURIComponent(status) : '');
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const { markers } = await res.json();

                this.clusters.clearLayers();
                this.accuracy.clearLayers();
                this.count = markers.length;

                // Empty state behaves like a toast: shows for 5s, then fades out.
                clearTimeout(this.emptyTimer);
                this.empty = markers.length === 0;
                if (this.empty) {
                    this.emptyTimer = setTimeout(() => { this.empty = false; }, 5000);
                }

                const bounds = [];
                for (const m of markers) {
                    const color = this.colors[m.status] ?? '#64748B';
                    const marker = L.marker([m.lat, m.lng], { icon: this.pin(color), title: m.reference });
                    marker.bindPopup(`
                        <div style='min-width:210px; font-size:13px; line-height:1.45'>
                            <div style='font-weight:700; color:#0F1A30; font-size:14px'>${this.esc(m.reference)}</div>
                            <div style='color:#475569'>${this.esc(m.unit)} · ${this.esc(m.type)}</div>
                            <div style='margin:8px 0 6px'>
                                <span style='display:inline-flex; align-items:center; gap:6px; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:700; color:${color}; background:${color}1A; border:1px solid ${color}40'>
                                    <span style='width:6px; height:6px; border-radius:50%; background:${color}'></span>${this.esc(m.status)}
                                </span>
                            </div>
                            <div style='color:#64748B; font-size:12px'>
                                Location captured: ${this.esc(m.captured_at ?? '—')}<br>
                                ${m.accuracy ? 'GPS accuracy: ±' + Math.round(m.accuracy) + ' m<br>' : ''}
                                ${Number(m.lat).toFixed(5)}, ${Number(m.lng).toFixed(5)}
                            </div>
                            <a href='${m.url}' style='display:inline-block; margin-top:8px; font-weight:700; color:#2563EB; text-decoration:none'>Open report →</a>
                        </div>`);
                    this.clusters.addLayer(marker);

                    // Accuracy circle: honest uncertainty. A ±150m desktop fix
                    // renders as a ~300m circle, NOT a falsely-precise pin.
                    if (m.accuracy) {
                        L.circle([m.lat, m.lng], {
                            radius: m.accuracy,
                            color,
                            weight: 1,
                            opacity: 0.6,
                            fillColor: color,
                            fillOpacity: 0.08,
                            interactive: false,
                        }).addTo(this.accuracy);
                    }

                    bounds.push([m.lat, m.lng]);
                }
                // Zoom in close enough to see streets and buildings around the reports
                if (bounds.length > 0) this.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 17 });
                this.loading = false;
            },
            }));
        });
    </script>

    <div x-data="opsMap(@js($statusColors), @js(route('command.map.data')))" class="flex h-[calc(100vh-4rem)] min-h-[520px] flex-col">

        {{-- Filter bar --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 bg-white px-4 py-3">
            <div class="flex gap-2 overflow-x-auto">
                <button @click="load(null)"
                        class="shrink-0 min-h-[40px] px-4 rounded-full text-sm font-medium border transition"
                        :class="status === null ? 'bg-navy-800 text-white border-navy-800 dark:bg-gold-400 dark:text-navy-900 dark:border-gold-400' : 'border-gray-300 text-gray-700 hover:border-gray-400'">
                    All
                </button>
                @foreach ($statuses as $status)
                    <button @click="load(@js($status))"
                            class="shrink-0 inline-flex min-h-[40px] items-center gap-2 px-4 rounded-full text-sm font-medium border transition"
                            :class="status === @js($status) ? 'bg-navy-800 text-white border-navy-800 dark:bg-gold-400 dark:text-navy-900 dark:border-gold-400' : 'border-gray-300 text-gray-700 hover:border-gray-400'">
                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $statusColors[$status] ?? '#64748B' }}"></span>
                        {{ $status }}
                    </button>
                @endforeach
            </div>
            <div class="ml-auto flex items-center gap-3 text-sm">
                <span x-show="loading" x-cloak class="text-gray-400">Loading…</span>
                <span x-show="! loading" class="text-gray-500">
                    <span class="font-semibold text-gray-900 tabular-nums" x-text="count"></span>
                    <span x-text="count === 1 ? 'report on map' : 'reports on map'"></span>
                </span>
            </div>
        </div>

        {{-- Map fills the remaining height --}}
        <div class="relative flex-1">
            <div id="ops-map" class="absolute inset-0 z-0"></div>

            {{-- Hint: accuracy circles appear when zoomed in --}}
            <div class="pointer-events-none absolute bottom-8 left-3 z-[400] hidden rounded-lg bg-white/90 px-3 py-1.5 text-[11px] text-gray-600 shadow sm:block dark:bg-[#111A2E]/90">
                Circles show GPS accuracy (visible when zoomed in) · Click a number to expand a group
            </div>

            {{-- Empty state: 5-second toast, then fades (x-transition) --}}
            <div x-show="empty" x-cloak x-transition
                 class="absolute inset-x-4 top-4 z-[500] mx-auto max-w-sm rounded-lg border border-gray-200 bg-white p-4 text-center text-sm text-gray-500 shadow">
                No location-captured reports in this filter.
            </div>
        </div>
    </div>
</x-app-layout>
