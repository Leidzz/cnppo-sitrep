<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Edit SITREP') }}</h2>
    </x-slot>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto">

            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="list-disc pl-5 mt-1 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-6">
                <p class="text-sm text-gray-500">Editing</p>
                <h1 class="text-xl font-bold text-gray-900">{{ $sitrep->reference_number }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Type: <span class="font-medium">{{ $sitrep->reportType->name }}</span>
                    <span class="text-gray-400">(locked)</span>
                </p>
            </div>

            {{-- PUT method required by Laravel for updates; the report type
                 travels as a hidden input so validation still sees it --}}
            <form method="POST" action="{{ route('reporting.sitreps.update', $sitrep) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="report_type_id" value="{{ $sitrep->report_type_id }}">

                {{-- Only the sitrep's own template renders --}}
                @php
                    $rt = $reportTypes->firstWhere('id', $sitrep->report_type_id);
                @endphp
                <div class="space-y-5">
                    @foreach ($rt->templateFields as $field)
                        <div>
                            <label for="df_{{ $field->field_key }}" class="block text-sm font-medium text-gray-700">
                                {{ $field->label }}
                                @if($field->is_required) <span class="text-red-600">*</span> @endif
                            </label>

                            @php
                                $value = old('dynamic_fields.'.$field->field_key, $sitrep->dynamic_fields[$field->field_key] ?? '');
                            @endphp

                            @if ($field->field_type === 'textarea')
                                <textarea id="df_{{ $field->field_key }}" name="dynamic_fields[{{ $field->field_key }}]" rows="4"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $value }}</textarea>
                            @else
                                <input type="text" id="df_{{ $field->field_key }}" name="dynamic_fields[{{ $field->field_key }}]"
                                       value="{{ $value }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-[44px]">
                            @endif

                            @error('dynamic_fields.'.$field->field_key)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                {{-- GPS: prefill from existing values; officer may re-capture --}}
                <div class="mt-8" x-data="{
                    lat: {{ json_encode(old('latitude', $sitrep->latitude)) }},
                    lng: {{ json_encode(old('longitude', $sitrep->longitude)) }},
                    accuracy: {{ json_encode(old('gps_accuracy_meters', $sitrep->gps_accuracy_meters)) }},
                    capturedAt: {{ json_encode(old('location_captured_at', $sitrep->location_captured_at?->toIso8601String())) }},
                    locating: false,
                    geoError: null,
                    map: null,
                    marker: null,

                    capture() {
                        if (! navigator.geolocation) {
                            this.geoError = 'Geolocation is not supported by this browser.';
                            return;
                        }
                        this.locating = true;
                        this.geoError = null;
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                this.locating = false;
                                this.lat = pos.coords.latitude;
                                this.lng = pos.coords.longitude;
                                this.accuracy = pos.coords.accuracy;
                                this.capturedAt = new Date(pos.timestamp).toISOString();
                                this.$nextTick(() => this.renderMap());
                            },
                            (err) => { this.locating = false; this.geoError = err.message; },
                            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                        );
                    },

                    renderMap() {
                        if (this.map) {
                            this.marker.setLatLng([this.lat, this.lng]);
                            this.map.setView([this.lat, this.lng], 17);
                            return;
                        }
                        this.map = L.map('gps-map').setView([this.lat, this.lng], 17);
                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '© OpenStreetMap contributors',
                        }).addTo(this.map);
                        this.marker = L.marker([this.lat, this.lng]).addTo(this.map);
                        setTimeout(() => this.map.invalidateSize(), 100);
                    },
                }">
                    <h3 class="text-lg font-semibold text-gray-900">Location Documentation</h3>

                    <div class="mt-3 flex items-center gap-3">
                        <button type="button" @click="capture()" :disabled="locating"
                                class="min-h-[44px] px-5 py-3 rounded-lg bg-gray-800 text-white text-sm font-semibold hover:bg-gray-700 disabled:opacity-50">
                            <span x-show="!locating">Re-capture Location</span>
                            <span x-show="locating" x-cloak>Locating…</span>
                        </button>
                        <button type="button" x-show="lat !== null" x-cloak @click="lat = lng = accuracy = capturedAt = null; geoError = null"
                                class="min-h-[44px] px-4 py-3 text-sm font-medium text-red-600">
                            Clear
                        </button>
                    </div>

                    <p x-show="geoError" x-cloak class="mt-2 text-sm text-red-600" x-text="geoError"></p>

                    <input type="hidden" name="latitude" x-model="lat">
                    <input type="hidden" name="longitude" x-model="lng">
                    <input type="hidden" name="gps_accuracy_meters" x-model="accuracy">
                    <input type="hidden" name="location_captured_at" x-model="capturedAt">

                    <div x-show="lat !== null" x-cloak class="mt-3 space-y-3">
                        <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-sm text-blue-900">
                            <p class="font-semibold">Location Captured — for validation</p>
                            <p class="mt-1">Latitude: <span x-text="lat"></span> · Longitude: <span x-text="lng"></span></p>
                            <p>Reported accuracy: ±<span x-text="accuracy"></span> m</p>
                        </div>
                        <div id="gps-map" class="h-64 w-full rounded-lg border border-gray-300 z-0"></div>
                    </div>

                    {{-- Existing location_description --}}
                    <div class="mt-4">
                        <label for="location_description" class="block text-sm font-medium text-gray-700">Location Description <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" id="location_description" name="location_description"
                               value="{{ old('location_description', $sitrep->location_description) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-[44px]">
                        @error('location_description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-8 flex flex-col-reverse sm:flex-row gap-3">
                    <button type="submit" name="action" value="draft"
                            class="min-h-[44px] px-6 py-3 rounded-lg border border-gray-300 bg-white text-gray-700 font-semibold hover:bg-gray-50">
                        Save Draft
                    </button>
                    <button type="submit" name="action" value="submit"
                            class="min-h-[44px] px-6 py-3 rounded-lg bg-blue-700 text-white font-semibold hover:bg-blue-800">
                        Submit SITREP
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>