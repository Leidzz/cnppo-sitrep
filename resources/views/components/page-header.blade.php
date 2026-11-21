@props([
    'title',
    'subtitle' => null,
    // Small label above the title, e.g. "Command Center"
    'eyebrow' => null,
    // Show "As of <date, time>" so printed copies carry a timestamp
    'asOf' => true,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between']) }}>
    <div class="min-w-0 flex-1">
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-wider text-gold-600 dark:text-gold-400">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-navy-900 dark:text-white">{{ $title }}</h1>
        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
            @if ($subtitle)
                <span>{{ $subtitle }}</span>
            @endif
            @if ($asOf)
                @if ($subtitle)<span class="hidden sm:inline text-slate-300" aria-hidden="true">•</span>@endif
                <span class="inline-flex items-center gap-1.5">
                    <x-icon name="clock" class="h-4 w-4 text-slate-400" />
                    As of {{ now()->format('M j, Y · g:i A') }}
                </span>
            @endif
        </div>
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2 print:hidden">{{ $actions }}</div>
    @endisset
</div>
