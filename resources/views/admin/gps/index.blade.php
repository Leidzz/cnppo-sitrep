<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white truncate">GPS Accuracy Study</h2>
    </x-slot>

    @push('head')
        <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
        <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    @endpush

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">

            <x-flash />

            <h1 class="text-xl font-bold text-gray-900">Reference Points</h1>
            <p class="mt-1 text-sm text-gray-500">Known ground-truth coordinates for the accuracy study (spec Section 18). Click the map to place a point, then save.</p>

            {{-- Create form + click-to-place map --}}
            <form method="POST" action="{{ route('admin.gps.points.store') }}"
                  class="anim-rise mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                  x-data="{ lat: null, lng: null, map: null, marker: null,
                             init() {
                                 this.map = L.map('point-map').setView([14.1, 122.9], 10);
                                 L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                     maxZoom: 19, attribution: '© OpenStreetMap contributors',
                                 }).addTo(this.map);
                                 this.map.on('click', (e) => {
                                     this.lat = e.latlng.lat.toFixed(7);
                                     this.lng = e.latlng.lng.toFixed(7);
                                     if (this.marker) this.marker.setLatLng(e.latlng);
                                     else this.marker = L.marker(e.latlng).addTo(this.map);
                                 });
                                 setTimeout(() => this.map.invalidateSize(), 100);
                             } }">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="label" class="block text-sm font-medium text-gray-700">Label <span class="text-red-600">*</span></label>
                        <input type="text" id="label" name="label" value="{{ old('label') }}" required
                               placeholder="e.g., Basud Municipal Hall flagpole"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-[44px]">
                        @error('label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description <span class="text-gray-400 font-normal">(how ground truth was established)</span></label>
                        <input type="text" id="description" name="description" value="{{ old('description') }}"
                               placeholder="e.g., pinned from satellite imagery"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 min-h-[44px]">
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Latitude <span class="text-red-600">*</span></label>
                        <input type="text" name="latitude" x-model="lat" readonly required
                               class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm min-h-[44px]">
                        @error('latitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Longitude <span class="text-red-600">*</span></label>
                        <input type="text" name="longitude" x-model="lng" readonly required
                               class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm min-h-[44px]">
                        @error('longitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div id="point-map" class="mt-4 h-72 w-full rounded-lg border border-gray-300 z-0"></div>
                <p class="mt-1 text-xs text-gray-400">Click the map to drop the pin — lat/long fill in automatically.</p>

                <div class="mt-4">
                    <x-btn type="submit">Add Reference Point</x-btn>
                </div>
            </form>

            {{-- Points table --}}
            <div class="anim-rise mt-6 rounded-2xl border border-gray-200 bg-white shadow-sm" style="animation-delay:120ms">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Existing Points ({{ $points->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($points as $point)
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $point->label }}</p>
                                <p class="text-xs text-gray-500">{{ $point->latitude }}, {{ $point->longitude }} · {{ $point->observations_count }} observation(s)</p>
                                @if ($point->description)
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $point->description }}</p>
                                @endif
                            </div>
                            {{-- Actions: Record (primary) + Delete --}}
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('admin.gps.capture', $point) }}"
                                   class="min-h-[44px] inline-flex items-center px-4 rounded-lg bg-blue-700 text-white text-sm font-semibold hover:bg-blue-800">
                                    Record
                                </a>
                                <form method="POST" action="{{ route('admin.gps.points.destroy', $point) }}"
                                      onsubmit="return confirm('Delete this reference point?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="min-h-[44px] px-2 text-xs font-semibold text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-8"><x-empty-state>No reference points yet — add the first one above.</x-empty-state></div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>