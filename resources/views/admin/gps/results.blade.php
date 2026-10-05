<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-white truncate">Study Results</h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-5xl mx-auto">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Positional Error by Reference Point</h1>
                    <p class="mt-1 text-sm text-gray-500">Descriptive statistics per point (spec Section 18). This table is your thesis results table.</p>
                </div>
                <a href="{{ route('admin.gps.export') }}"
                   class="min-h-[44px] inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-700 text-white text-sm font-semibold hover:bg-blue-800">
                    ⬇ Export CSV
                </a>
            </div>

            <div class="anim-rise mt-6 rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                <th class="px-5 py-3">Point</th>
                                <th class="px-5 py-3 text-right">N</th>
                                <th class="px-5 py-3 text-right">Mean (m)</th>
                                <th class="px-5 py-3 text-right">SD (m)</th>
                                <th class="px-5 py-3 text-right">Min (m)</th>
                                <th class="px-5 py-3 text-right">Max (m)</th>
                                <th class="px-5 py-3 text-right">Mean Accuracy (m)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($points as $row)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-gray-900">{{ $row['point']->label }}</td>
                                    <td class="px-5 py-3 text-right text-gray-600">{{ $row['count'] }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-blue-700">{{ $row['mean'] ?? '—' }}</td>
                                    <td class="px-5 py-3 text-right text-gray-600">{{ $row['std_dev'] ?? '—' }}</td>
                                    <td class="px-5 py-3 text-right text-gray-600">{{ $row['min'] ?? '—' }}</td>
                                    <td class="px-5 py-3 text-right text-gray-600">{{ $row['max'] ?? '—' }}</td>
                                    <td class="px-5 py-3 text-right text-gray-600">{{ $row['mean_device_accuracy'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-8 text-center text-gray-500">No reference points yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="mt-4 text-xs text-gray-400">
                <strong>Reading this table:</strong> "Mean" is the average positional error (measured vs. reference) in meters.
                Compare it against "Mean Accuracy" — the device's own ± claim — to test whether reported accuracy matches reality.
                Export the raw observations for further analysis in Excel/SPSS.
            </p>

            <div class="mt-6">
                <a href="{{ route('admin.gps.index') }}" class="text-sm font-semibold text-blue-700">← Back to reference points</a>
            </div>
        </div>
    </div>
</x-app-layout>