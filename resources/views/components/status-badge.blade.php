@props(['status'])

@php
    // Single source of truth for status presentation: color + label, never
    // color alone. The status-badge is the ONLY place these classes exist.
    $styles = [
        'Draft'                   => 'bg-gray-100 text-gray-700',
        'Submitted'               => 'bg-blue-100 text-blue-800',
        'Under Review'            => 'bg-amber-100 text-amber-800',
        'Validated'               => 'bg-green-100 text-green-800',
        'Returned for Correction' => 'bg-red-100 text-red-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold '.($styles[$status] ?? $styles['Draft'])]) }}>
    {{ $status }}
</span>