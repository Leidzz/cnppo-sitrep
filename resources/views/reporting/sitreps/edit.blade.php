<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Edit SITREP') }}</h2>
    </x-slot>

    @push('head')
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
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

            {{-- Filing station: correctable while the report is editable
                 (client-confirmed station selection). Defaults to the
                 station the report was filed under. --}}
            <div class="mb-6">
                <label for="unit_id" class="block text-sm font-medium text-gray-700">
                    Police Station / Unit <span class="text-red-600">*</span>
                </label>
                <select id="unit_id" name="unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-[44px]">
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected((string) old('unit_id', $sitrep->unit_id) === (string) $unit->id)>
                            {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
                @error('unit_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- PUT method required by Laravel for updates; enctype REQUIRED
                 for file uploads; report type travels as a hidden input --}}
            <form method="POST" action="{{ route('reporting.sitreps.update', $sitrep) }}" enctype="multipart/form-data">
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
                            @elseif ($field->field_type === 'date')
                                <input type="date" id="df_{{ $field->field_key }}" name="dynamic_fields[{{ $field->field_key }}]"
                                       value="{{ $value }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-[44px]">
                            @elseif ($field->field_type === 'time')
                                <input type="time" id="df_{{ $field->field_key }}" name="dynamic_fields[{{ $field->field_key }}]"
                                       value="{{ $value }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-[44px]">
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

                {{-- Checkpoint Team Composition (client-confirmed). Prefilled
                     from stored rows; SAVING REPLACES the list with what is
                     shown below. --}}
                @if ($rt->has_personnel)
                    <div class="mt-8"
                         x-data="{ personnel: {{ json_encode(old('personnel', $sitrep->personnel->map(fn ($p) => ['name' => $p->name, 'position' => $p->position])->all())) }} }">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Checkpoint Team Composition</h3>
                        <p class="mt-1 text-xs text-gray-400">Saving replaces the team list with what is shown below.</p>

                        <div class="mt-3 space-y-2">
                            <template x-for="(p, i) in personnel" :key="i">
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <input type="text" :name="`personnel[${i}][name]`" x-model="p.name"
                                           placeholder="Name of personnel"
                                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-[44px]">
                                    <select :name="`personnel[${i}][position]`" x-model="p.position"
                                            class="sm:w-56 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-[44px]">
                                        <option value="">Select position…</option>
                                        @foreach ($positions as $pos)
                                            <option value="{{ $pos }}">{{ $pos }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="personnel.splice(i, 1)"
                                            class="min-h-[44px] px-3 text-red-600 text-lg font-bold" aria-label="Remove row">×</button>
                                </div>
                            </template>
                        </div>

                        <button type="button"
                                @click="personnel.push({ name: '', position: '' })"
                                :disabled="personnel.length >= 15"
                                class="mt-3 min-h-[44px] px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                            + Add personnel
                        </button>

                        @error('personnel.*.name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @error('personnel.*.position') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- GPS: prefill from existing values; watch-and-refine re-capture
                     keeps the BEST fix for up to 12s; auto-stops at ≤10m --}}
                <div class="mt-8" x-data="{
                    lat: {{ json_encode(old('latitude', $sitrep->latitude)) }},
                    lng: {{ json_encode(old('longitude', $sitrep->longitude)) }},
                    accuracy: {{ json_encode(old('gps_accuracy_meters', $sitrep->gps_accuracy_meters)) }},
                    capturedAt: {{ json_encode(old('location_captured_at', $sitrep->location_captured_at?->toIso8601String())) }},
                    locating: false,
                    geoError: null,
                    map: null,
                    marker: null,
                    watchId: null,
                    bestFix: null,
                    timer: null,

                    capture() {
                        if (! navigator.geolocation) {
                            this.geoError = 'Geolocation is not supported by this browser.';
                            return;
                        }
                        this.locating = true;
                        this.geoError = null;
                        this.bestFix = null;

                        this.timer = setTimeout(() => this.finish(), 12000);

                        this.watchId = navigator.geolocation.watchPosition(
                            (pos) => {
                                if (! this.bestFix || pos.coords.accuracy < this.bestFix.accuracy) {
                                    this.bestFix = pos;
                                    this.lat = pos.coords.latitude;
                                    this.lng = pos.coords.longitude;
                                    this.accuracy = pos.coords.accuracy;
                                    this.capturedAt = new Date(pos.timestamp).toISOString();
                                    this.$nextTick(() => this.renderMap());
                                }
                                if (pos.coords.accuracy <= 10) this.finish();
                            },
                            (err) => {
                                this.locating = false;
                                clearTimeout(this.timer);
                                navigator.geolocation.clearWatch(this.watchId);
                                this.geoError = err.message;
                            },
                            { enableHighAccuracy: true, maximumAge: 0 }
                        );
                    },

                    finish() {
                        clearTimeout(this.timer);
                        if (this.watchId) navigator.geolocation.clearWatch(this.watchId);
                        this.locating = false;
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
                            <span x-show="locating" x-cloak>Improving fix… ±<span x-text="accuracy ?? '—'"></span> m</span>
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

                {{-- Photo documentation: attach ADDITIONAL photos to this draft.
                     File inputs cannot be prefilled, so existing photos are not
                     shown here — they remain attached and appear on the detail page. --}}
                <div class="mt-8" x-data="{
                    previews: [],
                    maxPhotos: 5,

                    handleFiles(event) {
                        // No syncInput() here — the input already holds these
                        // files natively; FileReader.onload is async, so
                        // rebuilding now would wipe them. Rebuild only on REMOVE.
                        [...event.target.files].forEach((file) => {
                            if (this.previews.length >= this.maxPhotos) return;
                            const reader = new FileReader();
                            reader.onload = (e) => this.previews.push({ file, url: e.target.result });
                            reader.readAsDataURL(file);
                        });
                    },

                    remove(index) {
                        this.previews.splice(index, 1);
                        this.syncInput();
                    },

                    syncInput() {
                        const dt = new DataTransfer();
                        this.previews.forEach((p) => dt.items.add(p.file));
                        this.$refs.photosInput.files = dt.files;
                    },
                }">
                    <h3 class="text-lg font-semibold text-gray-900">Add Photos</h3>
                    <p class="mt-1 text-sm text-gray-500">Attach additional photos. Existing photos stay attached. JPEG, PNG, or WebP · min 3 / max 5 total · max 5 MB each.</p>

                    <label for="photos" class="mt-4 flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-50">
                        Choose Photos
                        <input id="photos" type="file" name="photos[]" multiple
                               accept="image/jpeg,image/png,image/webp"
                               x-ref="photosInput"
                               @change="handleFiles($event)"
                               class="sr-only">
                    </label>

                    <div class="mt-4 grid grid-cols-3 gap-2" x-show="previews.length > 0" x-cloak>
                        <template x-for="(p, i) in previews" :key="i">
                            <div class="relative">
                                <img :src="p.url" alt="photo preview" class="h-24 w-full object-cover rounded-lg border border-gray-200">
                                <button type="button" @click="remove(i)"
                                        class="absolute -top-2 -right-2 h-7 w-7 rounded-full bg-red-600 text-white text-sm font-bold shadow"
                                        aria-label="Remove photo">×</button>
                            </div>
                        </template>
                    </div>

                    @error('photos') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('photos.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
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