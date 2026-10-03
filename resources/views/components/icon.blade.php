@props(['name'])

@php
    $paths = [
        'home'   => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10',
        'plus'   => 'M12 5v14M5 12h14',
        'doc'    => 'M9 12h6M9 16h6M13 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V9l-6-6zM13 3v6h6',
        'file'   => 'M13 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V9l-6-6zM13 3v6h6',
        'inbox'  => 'M20 13V7a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4m16 0h-5l-1 2H10l-1-2H4',
        'pin'    => 'M12 21s-7-5.1-7-11a7 7 0 1114 0c0 5.9-7 11-7 11zM12 12a2 2 0 100-4 2 2 0 000 4z',
        'bell'   => 'M15 17h5l-1.4-1.4a2 2 0 01-.6-1.4V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        'menu'   => 'M4 6h16M4 12h16M4 18h16',
        'clock'  => 'M12 7v5l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'check'  => 'M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'return' => 'M9 14l-4-4 4-4M5 10h9a7 7 0 017 7v0a7 7 0 01-7 7h-3',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'w-5 h-5']) }} fill="none" stroke="currentColor" stroke-width="1.8"
     viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
    <path d="{{ $paths[$name] ?? 'M12 5v14M5 12h14' }}" />
</svg>