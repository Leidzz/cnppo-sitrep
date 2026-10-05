<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white truncate">Record Observations</h2>
    </x-slot>

    @php
        // Precompute OUTSIDE the @json directive — multiline arrow functions
        // inside directive arguments break Blade's balanced-paren parser.
        $recentJson = $recent->map(fn ($o) => [
            'id' => $o->id,
            'error' => $o->computed_error_meters,
            'time' => $o->created_at->format('g:i:s A'),
        ])->values();
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto" x-data="captureTool({{ $point->id }})">

            <p class="text-sm text-gray-500">Reference point</p>
            <h1 class="text-xl font-bold text-gray-900">{{ $point->label }}</h1>
            <p class="mt-1 text-xs text-gray-500">{{ $point->latitude }}, {{ $point->longitude }}
                @if($point->description) · {{ $point->description }} @endif</p>

            {{-- Session metadata (constant across the batch) --}}
            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="device_label" class="block text-xs font-medium text-gray-500">Device</label>
                    <input type="text" id="device_label" x-model="deviceLabel" placeholder="e.g., Samsung A54"
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm min-h-[44px]">
                </div>
                <div>
                    <label for="conditions" class="block text-xs font-medium text-gray-500">Conditions</label>
                    <input type="text" id="conditions" x-model="conditions" placeholder="e.g., outdoor open sky"
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm min-h-[44px]">
                </div>
            </div>

            {{-- Live stats --}}
            <div class="mt-6 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold text-gray-900" x-text="count">0</p>
                    <p class="text-xs text-gray-500">Recorded</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold text-blue-700"><span x-text="mean">—</span><span class="text-sm"> m</span></p>
                    <p class="text-xs text-gray-500">Mean error</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-2xl font-bold" :class="lastError <= 10 ? 'text-green-700' : 'text-amber-700'">
                        <span x-text="lastError ?? '—'"></span><span class="text-sm"> m</span></p>
                    <p class="text-xs text-gray-500">Last error</p>
                </div>
            </div>

            {{-- THE button --}}
            <button @click="captureAndRecord()" :disabled="locating || saving"
                    class="mt-6 w-full min-h-[56px] rounded-2xl bg-blue-700 text-white text-lg font-bold hover:bg-blue-800 disabled:opacity-50">
                <span x-show="!locating && !saving">📍 Capture & Record</span>
                <span x-show="locating" x-cloak>Improving fix… ±<span x-text="accuracy ?? '—'"></span> m</span>
                <span x-show="saving" x-cloak>Saving…</span>
            </button>
            <p x-show="error" x-cloak class="mt-2 text-sm text-red-600" x-text="error"></p>

            {{-- Recent observations --}}
            <h2 class="mt-8 text-sm font-semibold text-gray-900">Recent</h2>
            <div class="mt-2 space-y-2">
                <template x-for="obs in recent" :key="obs.id">
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm">
                        <span class="text-gray-500" x-text="obs.time"></span>
                        <span class="font-semibold" :class="obs.error <= 10 ? 'text-green-700' : 'text-amber-700'">
                            <span x-text="obs.error"></span> m error
                        </span>
                    </div>
                </template>
            </div>

            <div class="mt-6">
                <a href="{{ route('admin.gps.index') }}" class="text-sm font-semibold text-blue-700">← Back to reference points</a>
            </div>
        </div>
    </div>

    <script>
        function captureTool(pointId) {
            return {
                pointId,
                deviceLabel: '',
                conditions: '',
                count: {{ $stats['count'] ?? 0 }},
                mean: {{ $stats['count'] ? $stats['mean'] : 'null' }},
                lastError: null,
                recent: @json($recentJson),
                locating: false,
                saving: false,
                error: null,
                watchId: null,
                bestFix: null,
                timer: null,
                accuracy: null,

                captureAndRecord() {
                    if (!navigator.geolocation) { this.error = 'Geolocation unsupported'; return; }
                    this.locating = true;
                    this.error = null;
                    this.bestFix = null;
                    this.timer = setTimeout(() => this.finish(), 12000);

                    this.watchId = navigator.geolocation.watchPosition(
                        (pos) => {
                            if (!this.bestFix || pos.coords.accuracy < this.bestFix.accuracy) {
                                this.bestFix = pos;
                                this.accuracy = pos.coords.accuracy;
                            }
                            if (pos.coords.accuracy <= 10) this.finish();
                        },
                        (err) => { this.locating = false; clearTimeout(this.timer); navigator.geolocation.clearWatch(this.watchId); this.error = err.message; },
                        { enableHighAccuracy: true, maximumAge: 0 }
                    );
                },

                async finish() {
                    clearTimeout(this.timer);
                    navigator.geolocation.clearWatch(this.watchId);
                    this.locating = false;
                    if (!this.bestFix) return;

                    this.saving = true;
                    const res = await fetch('{{ route('admin.gps.observations.store', $point) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({
                            latitude: this.bestFix.coords.latitude,
                            longitude: this.bestFix.coords.longitude,
                            accuracy: this.bestFix.coords.accuracy,
                            device_timestamp: new Date(this.bestFix.timestamp).toISOString(),
                            device_label: this.deviceLabel || null,
                            conditions: this.conditions || null,
                        }),
                    });

                    if (!res.ok) { this.error = 'Save failed'; this.saving = false; return; }

                    const data = await res.json();
                    this.count = data.stats.count;
                    this.mean = data.stats.mean;
                    this.lastError = data.error;
                    this.recent.unshift({ id: data.id, error: data.error, time: new Date().toLocaleTimeString() });
                    this.recent = this.recent.slice(0, 10);
                    this.saving = false;
                },
            };
        }
    </script>
</x-app-layout>