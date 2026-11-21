@props([
    'title' => null,
    'subtitle' => null,
    // false = body has no padding (for tables / edge-to-edge lists)
    'padded' => true,
])

<section {{ $attributes->merge(['class' => 'flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white shadow-card print:break-inside-avoid dark:border-white/[0.07] dark:bg-[#111A2E] dark:shadow-none']) }}>
    @if ($title || isset($actions))
        <header class="flex items-start justify-between gap-4 px-5 pt-4 {{ $padded ? '' : 'pb-4 border-b border-slate-100 dark:border-white/[0.06]' }}">
            <div class="min-w-0">
                @if ($title)
                    <h3 class="text-sm font-semibold text-navy-900 dark:text-white">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2 print:hidden">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div class="flex-1 {{ $padded ? 'p-5 pt-3' : '' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 dark:border-white/[0.06] dark:text-slate-400">
            {{ $footer }}
        </footer>
    @endisset
</section>
