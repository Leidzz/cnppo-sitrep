<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('SITREP Detail') }}</h2>
    </x-slot>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    @php
        $badge = match($sitrep->currentStatus->name) {
            'Draft' => 'bg-gray-100 text-gray-700',
            'Submitted' => 'bg-blue-100 text-blue-800',
            'Under Review' => 'bg-amber-100 text-amber-800',
            'Validated' => 'bg-green-100 text-green-800',
            'Returned for Correction' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-700',
        };
    @endphp

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto space-y-8">

            {{-- Header: reference + status --}}
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $sitrep->reference_number }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $sitrep->reportType->name }}</p>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $badge }}">
                    {{ $sitrep->currentStatus->name }}
                </span>
            </div>

            {{-- Report information --}}
            <section class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Report Information</h3>
                <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-gray-500">Reporting Unit</dt><dd class="font-medium text-gray-900">{{ $sitrep->unit->name }}</dd></div>
                    <div><dt class="text-gray-500">Submitted By</dt><dd class="font-medium text-gray-900">{{ $sitrep->creator->name }}</dd></div>
                    <div><dt class="text-gray-500">Date Created</dt><dd class="font-medium text-gray-900">{{ $sitrep->created_at->format('M d, Y g:i A') }}</dd></div>
                    <div><dt class="text-gray-500">Date Submitted</dt><dd class="font-medium text-gray-900">{{ $sitrep->submitted_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                </dl>
            </section>

            {{-- Operational details: labels come from the template, so this
                 page always matches whatever the form collected --}}
            <section class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Operational Details</h3>
                <dl class="mt-3 space-y-3 text-sm">
                    @foreach ($sitrep->reportType->templateFields as $field)
                        <div>
                            <dt class="text-gray-500">{{ $field->label }}</dt>
                            <dd class="mt-0.5 font-medium text-gray-900 whitespace-pre-line">{{ $sitrep->dynamic_fields[$field->field_key] ?? '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            {{-- Location documentation (spec Section 7) — neutral labels only --}}
            <section class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Location Documentation</h3>
                @if ($sitrep->latitude !== null)
                    <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div><dt class="text-gray-500">Latitude</dt><dd class="font-medium text-gray-900">{{ $sitrep->latitude }}</dd></div>
                        <div><dt class="text-gray-500">Longitude</dt><dd class="font-medium text-gray-900">{{ $sitrep->longitude }}</dd></div>
                        <div><dt class="text-gray-500">Reported Accuracy</dt><dd class="font-medium text-gray-900">±{{ $sitrep->gps_accuracy_meters }} m</dd></div>
                        <div><dt class="text-gray-500">Captured At (device time)</dt><dd class="font-medium text-gray-900">{{ $sitrep->location_captured_at?->format('M d, Y g:i A') }}</dd></div>
                        @if ($sitrep->location_description)
                            <div class="sm:col-span-2"><dt class="text-gray-500">Location Description</dt><dd class="font-medium text-gray-900">{{ $sitrep->location_description }}</dd></div>
                        @endif
                    </dl>
                    <div id="detail-map" class="mt-4 h-64 w-full rounded-lg border border-gray-300 z-0"></div>
                    <p class="mt-2 text-xs text-gray-400">Location captured by device — for validation by authorized reviewers.</p>
                @else
                    <p class="mt-3 text-sm text-gray-500">No location captured.</p>
                @endif
            </section>

            {{-- Photo gallery (policy-checked serving route — never public URLs) --}}
            <section class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Supporting Documentation</h3>
                @if ($sitrep->attachments->isNotEmpty())
                    <div class="mt-3 grid grid-cols-3 sm:grid-cols-4 gap-2">
                        @foreach ($sitrep->attachments as $attachment)
                            <a href="{{ route('attachments.show', $attachment) }}" target="_blank">
                                <img src="{{ route('attachments.show', $attachment) }}" alt="attachment" class="h-24 w-full object-cover rounded-lg border border-gray-200">
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-sm text-gray-500">No photos attached.</p>
                @endif
            </section>

            {{-- Validation information (spec Section 14) --}}
            <section class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Validation Information</h3>
                <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-gray-500">Reviewed By</dt><dd class="font-medium text-gray-900">{{ $sitrep->reviewer?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Review Date</dt><dd class="font-medium text-gray-900">{{ $sitrep->reviewed_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                    @if ($sitrep->review_remarks)
                        <div class="sm:col-span-2"><dt class="text-gray-500">Reviewer Remarks</dt><dd class="font-medium text-gray-900 whitespace-pre-line">{{ $sitrep->review_remarks }}</dd></div>
                    @endif
                </dl>
                @if (! $sitrep->reviewed_at && $sitrep->currentStatus->name !== 'Draft')
                    <p class="mt-3 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">This report is awaiting review by the Command Center.</p>
                @endif
            </section>

            {{-- NEW (Phase 3 Step 2): Review actions — rendered ONLY for
                 command-center/admin on reviewable reports. Officers never
                 see this section; the Form Request re-checks on POST anyway. --}}
            @can('review', $sitrep)
                <section class="rounded-lg border-2 border-gray-900 bg-white p-4">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Review Decision</h3>

                    <form method="POST" action="{{ route('command.sitreps.decide', $sitrep) }}" class="mt-4 space-y-4">
                        @csrf

                        <div>
                            <label for="remarks" class="block text-sm font-medium text-gray-700">
                                Reviewer Remarks <span class="text-gray-400 font-normal">(required if returning)</span>
                            </label>
                            <textarea id="remarks" name="remarks" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('remarks') }}</textarea>
                            @error('remarks') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row gap-3">
                            <button type="submit" name="decision" value="returned"
                                    class="min-h-[44px] px-6 py-3 rounded-lg border border-red-300 bg-white text-red-700 font-semibold hover:bg-red-50">
                                Return for Correction
                            </button>
                            <button type="submit" name="decision" value="validated"
                                    class="min-h-[44px] px-6 py-3 rounded-lg bg-green-700 text-white font-semibold hover:bg-green-800">
                                Validate Report
                            </button>
                        </div>
                    </form>
                </section>
            @endcan

            {{-- Role-aware back link: officers go to My Reports, reviewers to the queue --}}
            <div class="pt-2">
                @if(auth()->user()->hasRole('reporting-personnel'))
                    <a href="{{ route('reporting.sitreps.index') }}" class="text-sm font-semibold text-blue-700">← Back to My Reports</a>
                @else
                    <a href="{{ route('command.incoming') }}" class="text-sm font-semibold text-blue-700">← Back to Incoming Queue</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Read-only Leaflet map --}}
    @if ($sitrep->latitude !== null)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const map = L.map('detail-map').setView([{{ $sitrep->latitude }}, {{ $sitrep->longitude }}], 16);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors',
                }).addTo(map);
                L.marker([{{ $sitrep->latitude }}, {{ $sitrep->longitude }}]).addTo(map);
            });
        </script>
    @endif
</x-app-layout>