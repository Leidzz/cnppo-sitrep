@props(['current' => 7, 'options' => \App\Services\DashboardMetricsService::RANGES])

{{-- Plain links (?range=N), so it works without JavaScript, can be
     bookmarked, and the browser Back button behaves as expected. --}}
<nav {{ $attributes->merge(['class' => 'inline-flex rounded-lg border border-slate-300 bg-white p-0.5 shadow-sm print:hidden dark:border-white/10 dark:bg-white/5']) }} aria-label="Date range">
    @foreach ($options as $days)
        <a href="{{ request()->fullUrlWithQuery(['range' => $days]) }}"
           @if ($days === $current) aria-current="true" @endif
           class="min-h-[40px] inline-flex items-center rounded-md px-3 text-sm font-semibold transition
                  {{ $days === $current ? 'bg-navy-800 text-white shadow-sm dark:bg-gold-400 dark:text-navy-900' : 'text-slate-600 hover:bg-slate-100 hover:text-navy-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white' }}">
            {{ $days }}<span class="sr-only sm:not-sr-only sm:ml-1">days</span>
        </a>
    @endforeach
</nav>
