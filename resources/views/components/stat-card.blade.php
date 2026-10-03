@props(['label', 'count', 'icon' => 'doc', 'tint' => 'blue', 'subtitle' => null, 'href' => null])

@php
    // v3: white card, one strong accent (icon chip + colored count), hover
    // elevation, and a count-up animation on load. The real number is still
    // rendered server-side first ({{ $count }}), so tests that assertSee()
    // the count keep passing — Alpine then animates 0 -> N.
    $tints = [
        'blue'  => ['icon' => 'bg-blue-100 text-blue-700',    'count' => 'text-blue-700'],
        'amber' => ['icon' => 'bg-amber-100 text-amber-700',  'count' => 'text-amber-700'],
        'green' => ['icon' => 'bg-green-100 text-green-700',  'count' => 'text-green-700'],
        'red'   => ['icon' => 'bg-red-100 text-red-700',      'count' => 'text-red-700'],
        'gray'  => ['icon' => 'bg-gray-100 text-gray-600',    'count' => 'text-gray-900'],
    ];
    $t = $tints[$tint] ?? $tints['blue'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'block bg-white rounded-2xl border border-gray-200 p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition']) }}>
    <div class="flex items-center gap-4">
        <div class="h-12 w-12 shrink-0 rounded-xl {{ $t['icon'] }} flex items-center justify-center">
            <x-icon name="{{ $icon }}" class="w-6 h-6" />
        </div>
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
            <p class="text-3xl font-bold {{ $t['count'] }} leading-tight"
               x-data="{ v: 0 }"
               x-init="let t = {{ (int) $count }}; let s = Math.max(1, Math.ceil(t / 25)); let i = setInterval(() => { v = Math.min(t, v + s); if (v >= t) clearInterval(i); }, 25)"
               x-text="v">{{ $count }}</p>
        </div>
        @if($href)
            <x-icon name="chevron" class="w-5 h-5 ml-auto text-gray-300" />
        @endif
    </div>
    @if($subtitle)
        <p class="mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">{{ $subtitle }}</p>
    @endif
</{{ $tag }}>