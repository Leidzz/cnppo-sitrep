@props(['variant' => 'primary', 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-3 rounded-lg text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 transition';
    $variants = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        'outline' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-gray-400',
        'danger'  => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'success' => 'bg-green-700 text-white hover:bg-green-800 focus:ring-green-500',
    ];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $base.' '.($variants[$variant] ?? $variants['primary'])]) }}>
    {{ $slot }}
</button>