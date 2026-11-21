@props(['status'])

@php
    // Single source of truth for status presentation: color + label, never
    // color alone. Dot colors match the `status` tokens in tailwind.config.js
    // and STATUS_COLORS in public/vendor/charts/chart-theme.js.
    $styles = [
        'Draft'                   => ['pill' => 'bg-slate-100 text-slate-700 ring-slate-200',  'dot' => 'bg-status-draft'],
        'Submitted'               => ['pill' => 'bg-blue-50 text-blue-800 ring-blue-200',      'dot' => 'bg-status-submitted'],
        'Under Review'            => ['pill' => 'bg-amber-50 text-amber-800 ring-amber-200',   'dot' => 'bg-status-review'],
        'Validated'               => ['pill' => 'bg-green-50 text-green-800 ring-green-200',   'dot' => 'bg-status-validated'],
        'Returned for Correction' => ['pill' => 'bg-red-50 text-red-800 ring-red-200',         'dot' => 'bg-status-returned'],
    ];
    $s = $styles[$status] ?? $styles['Draft'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '.$s['pill']]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}" aria-hidden="true"></span>
    {{ $status }}
</span>
