<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Create SITREP') }}</h2>
    </x-slot>

    {{-- Leaflet map library (OpenStreetMap tiles) — Part B --}}
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto"
             x-data="{ type: {{ old('report_type_id') ?? 'null' }} }">

            {{-- Validation summary (mobile: first thing seen on error) --}}
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

            {{-- enctype REQUIRED for file uploads — browsers silently drop
                 files without it --}}
            <form method="POST" action="{{ route('reporting.sitreps.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- 1. Report type picker: big tappable cards, one column on mobile --}}
                <fieldset>
                    <legend class="text-base font-semibold text-gray-900">Report Type</legend>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        @foreach ($reportTypes as $rt)
                            <label class="flex items-center gap-3 rounded-lg border p-4 cursor-pointer min-h-[44px]
                                          has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 has-[:checked]:ring-1 has-[:checked]:ring-blue-600">
                                <input type="radio" name="report_type_id" value="{{ $rt->id }}"
                                       x-model.number="type" class="sr-only">
                                <span class="text-sm font-medium text-gray-900">{{ $rt->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('report_type_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </fieldset>

                {{-- 2. Dynamic fields: one block per report type, Alpine shows the chosen one.
                       NOTHING here is hardcoded — labels, keys, required, and input type
                       all come from template_fields.
                       Inputs of unselected types are DISABLED so the browser never
                       submits them (duplicate names would overwrite each other). --}}
                @foreach ($reportTypes as $rt)
                    <div x-show="type === {{ $rt->id }}" x-cloak class="mt-8 space-y-5">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $rt->name }} Details</h3>

                        @foreach ($rt->templateFields as $field)
                            <div>
                                <label for="df_{{ $field->field_key }}_{{ $rt->id }}"
                                       class="block text-sm font-medium text-gray-700">
                                    {{ $field->label }}
                                    @if($field->is_required) <span class="text-red-600">*</span> @endif
                                </label>

                                @if ($field->field_type === 'textarea')
                                    <textarea id="df_{{ $field->field_key }}_{{ $rt->id }}"
                                              name="dynamic_fields[{{ $field->field_key }}]"
                                              rows="4"
                                              :disabled="type !== {{ $rt->id }}"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                                     focus:border-blue-500 focus:ring-blue-500
                                                     disabled:bg-gray-50 disabled:text-gray-400">{{ old('dynamic_fields.'.$field->field_key) }}</textarea>
                                @else
                                    <input type="text" id="df_{{ $field->field_key }}_{{ $rt->id }}"
                                           name="dynamic_fields[{{ $field->field_key }}]"
                                           value="{{ old('dynamic_fields.'.$field->field_key) }}"
                                           :disabled="type !== {{ $rt->id }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                                  focus:border-blue-500 focus:ring-blue-500 min-h-[44px]
                                                  disabled:bg-gray-50 disabled:text-gray-400">
                                @endif

                                @error('dynamic_fields.'.$field->field_key)
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                @endforeach

                {{-- 3. Location documentation (spec Sections 7-8). Part B.
                       Neutral labeling: 'Location Captured' — NEVER 'Verified'. --}}
                <div class="mt-8" x-data="{
                    lat: {{ json_encode(old('latitude')) }},
                    lng: {{ json_encode(old('longitude')) }},
                    accuracy: {{ json_encode(old('gps_accuracy_meters')) }},
                    capturedAt: {{ json_encode(old('location_captured_at')) }},
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
                                // Full precision — we never round here (rule 8).
                                this.lat  = pos.coords.latitude;
                                this.lng  = pos.coords.longitude;
                                this.accuracy = pos.coords.accuracy;      // meters, device-reported
                                this.capturedAt = new Date(pos.timestamp).toISOString(); // DEVICE clock
                                this.$nextTick(() => this.renderMap());
                            },
                            (err) => {
                                this.locating = false;
                                this.geoError = err.message;
                            },
                            // maximumAge: 0 — never accept a cached fix; the point
                            // must be captured NOW, not recycled from minutes ago.
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
                        // Fix tiles not rendering when the map was initialized
                        // while its container was hidden
                        setTimeout(() => this.map.invalidateSize(), 100);
                    },
                }">
                    <h3 class="text-lg font-semibold text-gray-900">Location Documentation</h3>
                    <p class="mt-1 text-sm text-gray-500">Capture the location where the activity was conducted. Location data supports validation by authorized reviewers.</p>

                    <div class="mt-4 flex items-center gap-3">
                        <button type="button" @click="capture()" :disabled="locating"
                                class="min-h-[44px] px-5 py-3 rounded-lg bg-gray-800 text-white text-sm font-semibold hover:bg-gray-700 disabled:opacity-50">
                            <span x-show="!locating">Capture Location</span>
                            <span x-show="locating" x-cloak>Locating…</span>
                        </button>
                        <button type="button" x-show="lat !== null" x-cloak @click="lat = lng = accuracy = capturedAt = null; geoError = null"
                                class="min-h-[44px] px-4 py-3 text-sm font-medium text-red-600">
                            Clear
                        </button>
                    </div>

                    <p x-show="geoError" x-cloak class="mt-2 text-sm text-red-600" x-text="geoError"></p>

                    {{-- Hidden fields: travel with the normal form POST --}}
                    <input type="hidden" name="latitude" x-model="lat">
                    <input type="hidden" name="longitude" x-model="lng">
                    <input type="hidden" name="gps_accuracy_meters" x-model="accuracy">
                    <input type="hidden" name="location_captured_at" x-model="capturedAt">

                    {{-- Confirmation + map, only after capture --}}
                    <div x-show="lat !== null" x-cloak class="mt-4 space-y-3">
                        <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-sm text-blue-900">
                            <p class="font-semibold">Location Captured — for validation</p>
                            <p class="mt-1">Latitude: <span x-text="lat"></span> · Longitude: <span x-text="lng"></span></p>
                            <p>Reported accuracy: ±<span x-text="accuracy"></span> m · Captured at (device time): <span x-text="capturedAt"></span></p>
                        </div>
                        <div id="gps-map" class="h-64 w-full rounded-lg border border-gray-300 z-0"></div>
                    </div>

                    {{-- Manual location text (spec Section 7: allow reported address text) --}}
                    <div class="mt-4">
                        <label for="location_description" class="block text-sm font-medium text-gray-700">
                            Location Description <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <input type="text" id="location_description" name="location_description"
                               value="{{ old('location_description') }}"
                               placeholder="e.g., Brgy. Poblacion, near the church"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-[44px]">
                        @error('location_description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- 4. Photo documentation (spec Section 9). Preview + remove BEFORE
                       submission, entirely client-side; server stores only what remains. --}}
                <div class="mt-8" x-data="{
                    previews: [],
                    maxPhotos: 5,

                    handleFiles(event) {
                        [...event.target.files].forEach((file) => {
                            if (this.previews.length >= this.maxPhotos) return;
                            const reader = new FileReader();
                            reader.onload = (e) => this.previews.push({ file, url: e.target.result });
                            reader.readAsDataURL(file);
                        });
                        this.syncInput();
                    },

                    remove(index) {
                        this.previews.splice(index, 1);
                        this.syncInput();
                    },

                    // Rebuild the file input from what remains in previews, so
                    // removed photos are truly NOT submitted (input.files is
                    // read-only; DataTransfer is the standard workaround)
                    syncInput() {
                        const dt = new DataTransfer();
                        this.previews.forEach((p) => dt.items.add(p.file));
                        this.$refs.photosInput.files = dt.files;
                    },
                }">
                    <h3 class="text-lg font-semibold text-gray-900">Photo Documentation</h3>
                    <p class="mt-1 text-sm text-gray-500">JPEG, PNG, or WebP · max 5 MB each · up to 5 photos. GPS Map Camera output may be attached as a photo.</p>

                    <label for="photos" class="mt-4 flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700 cursor-pointer hover:bg-gray-50">
                        Choose Photos
                        <input id="photos" type="file" name="photos[]" multiple
                               accept="image/jpeg,image/png,image/webp"
                               x-ref="photosInput"
                               @change="handleFiles($event)"
                               class="sr-only">
                    </label>

                    {{-- Previews: 3 columns, thumbs with remove buttons --}}
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

                {{-- 5. Actions: full-width on mobile, side by side on desktop.
                       'action' tells the server which path to take. --}}
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