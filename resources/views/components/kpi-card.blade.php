@props([
    'label',
    'count',
    'icon' => 'doc',
    'tint' => 'blue',
    'subtitle' => null,
    'href' => null,
    // Optional trend vs previous period, as a percentage (e.g. 12.5 or -8).
    'delta' => null,
    'deltaLabel' => 'vs previous period',
    // Set true when an increase is bad news (e.g. Returned reports).
    'deltaInverse' => false,
    // Optional list of numbers for a mini sparkline (oldest first).
    'spark' => null,
    // Optional unit shown after the number (e.g. "hrs", "%").
    'suffix' => null,
])

@php
    // One accent per card: colored icon chip + thin top rule. The number
    // itself stays dark navy so a row of cards reads calm, not like a rainbow.
    $tints = [
        'blue'  => ['chip' => 'bg-blue-50 text-blue-700 ring-blue-100',    'rule' => 'bg-status-submitted', 'spark' => '#2563EB'],
        'amber' => ['chip' => 'bg-amber-50 text-amber-700 ring-amber-100', 'rule' => 'bg-status-review',    'spark' => '#D97706'],
        'green' => ['chip' => 'bg-green-50 text-green-700 ring-green-100', 'rule' => 'bg-status-validated', 'spark' => '#16A34A'],
        'red'   => ['chip' => 'bg-red-50 text-red-700 ring-red-100',       'rule' => 'bg-status-returned',  'spark' => '#DC2626'],
        'gray'  => ['chip' => 'bg-slate-100 text-slate-600 ring-slate-200','rule' => 'bg-status-draft',     'spark' => '#64748B'],
        'navy'  => ['chip' => 'bg-navy-50 text-navy-700 ring-navy-100',    'rule' => 'bg-navy-700',         'spark' => '#213458'],
    ];
    $t = $tints[$tint] ?? $tints['blue'];

    // Whole numbers count up from 0; decimals (e.g. 6.4 hrs) and missing
    // values (null -> "—") are shown as-is.
    $isWhole = is_int($count) || (is_string($count) && ctype_digit($count));
    $display = $count === null ? '—' : ($isWhole ? $count : rtrim(rtrim(number_format((float) $count, 1), '0'), '.'));
    $tag = $href ? 'a' : 'div';

    // Delta pill: green when the change is good, red when bad, gray when flat.
    $deltaClass = null;
    if ($delta !== null) {
        $good = $deltaInverse ? $delta < 0 : $delta > 0;
        $deltaClass = $delta == 0
            ? 'bg-slate-100 text-slate-600'
            : ($good ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700');
    }

    // Sparkline drawn server-side as a tiny SVG: no JavaScript needed.
    $sparkPath = $sparkArea = null;
    if (is_array($spark) && count($spark) > 1 && max($spark) > 0) { // all-zero sparkline says nothing; hide it
        $w = 100; $h = 28; $max = max(max($spark), 1); $n = count($spark) - 1;
        $pts = collect($spark)->values()->map(fn ($v, $i) => round($i / $n * $w, 2).','.round($h - ($v / $max) * ($h - 2) - 1, 2));
        $sparkPath = 'M'.$pts->implode(' L');
        $sparkArea = $sparkPath." L{$w},{$h} L0,{$h} Z";
    }
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-card transition print:break-inside-avoid'.($href ? ' hover:shadow-card-hover hover:border-slate-300 hover:-translate-y-0.5' : '')]) }}>
    <span class="absolute inset-x-0 top-0 h-[3px] {{ $t['rule'] }}" aria-hidden="true"></span>

    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 leading-snug">{{ $label }}</p>
        <span class="h-9 w-9 shrink-0 rounded-lg ring-1 {{ $t['chip'] }} flex items-center justify-center">
            <x-icon name="{{ $icon }}" class="w-[18px] h-[18px]" />
        </span>
    </div>

    <div class="mt-2 flex items-end justify-between gap-3">
        <p class="text-3xl font-bold tracking-tight text-navy-900 tabular-nums leading-none">
            {{-- Real number is rendered server-side first (tests assertSee() it);
                 Alpine then animates 0 -> N on load. --}}
            @if ($isWhole)
                <span x-data="{ v: 0 }"
                      x-init="let t = {{ (int) $count }}; let s = Math.max(1, Math.ceil(t / 25)); let i = setInterval(() => { v = Math.min(t, v + s); if (v >= t) clearInterval(i); }, 25)"
                      x-text="v">{{ $count }}</span>
            @else
                <span>{{ $display }}</span>
            @endif
            @if($suffix && $count !== null)<span class="ml-1 text-base font-semibold text-slate-400">{{ $suffix }}</span>@endif
        </p>

        @if ($sparkPath)
            <svg viewBox="0 0 100 28" preserveAspectRatio="none" class="h-8 w-24 shrink-0" aria-hidden="true">
                <path d="{{ $sparkArea }}" fill="{{ $t['spark'] }}" fill-opacity="0.08" />
                <path d="{{ $sparkPath }}" fill="none" stroke="{{ $t['spark'] }}" stroke-width="1.75" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            </svg>
        @endif
    </div>

    @if ($delta !== null || $subtitle || $href)
        <div class="mt-auto pt-4">
            <div class="flex min-h-[20px] items-center gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
                @if ($delta !== null)
                    <span class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 font-semibold tabular-nums {{ $deltaClass }}">
                        <x-icon name="{{ $delta < 0 ? 'trend-down' : 'trend-up' }}" class="w-3.5 h-3.5" />
                        {{ $delta > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($delta, 1), '0'), '.') }}%
                    </span>
                    <span class="truncate">{{ $deltaLabel }}</span>
                @elseif ($subtitle)
                    <span class="truncate">{{ $subtitle }}</span>
                @endif

                @if ($href)
                    <x-icon name="chevron" class="ml-auto w-4 h-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-navy-500" />
                @endif
            </div>
        </div>
    @endif
</{{ $tag }}>
