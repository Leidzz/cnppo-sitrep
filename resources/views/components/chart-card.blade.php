@props([
    'title',
    'chartId',
    'subtitle' => null,
    'height' => 'h-64',
    // Pass true when the server already knows there is nothing to plot;
    // the canvas is skipped and a friendly empty state is shown instead.
    'empty' => false,
    'emptyText' => 'No data for this period yet.',
])

<x-card :title="$title" :subtitle="$subtitle" {{ $attributes }}>
    @isset($actions)
        <x-slot name="actions">{{ $actions }}</x-slot>
    @endisset

    <div class="relative {{ $height }}" data-chart-body>
        @if ($empty)
            <div class="flex h-full flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50/60 text-center">
                <x-icon name="chart" class="h-7 w-7 text-slate-300" />
                <p class="mt-2 text-sm text-slate-500">{{ $emptyText }}</p>
            </div>
        @else
            {{-- Skeleton bars until chart-theme.js sets data-ready on this element --}}
            <div class="chart-skeleton" aria-hidden="true">
                <span style="height:35%"></span><span style="height:60%"></span><span style="height:45%"></span>
                <span style="height:75%"></span><span style="height:55%"></span><span style="height:85%"></span><span style="height:40%"></span>
            </div>
            <canvas id="{{ $chartId }}" role="img" aria-label="{{ $title }}"></canvas>
        @endif
    </div>

    @isset($footer)
        <x-slot name="footer">{{ $footer }}</x-slot>
    @endisset
</x-card>
