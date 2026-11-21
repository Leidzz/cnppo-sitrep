<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Theme + sidebar state are applied BEFORE first paint, so there is
             no flash of the wrong theme. Saved choice wins; otherwise follow
             the operating system setting. --}}
        <script>
            (function () {
                var root = document.documentElement;
                try {
                    var saved = localStorage.getItem('theme');
                    var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    root.classList.toggle('dark', dark);
                    root.classList.toggle('sb-collapsed', localStorage.getItem('sidebar-collapsed') === '1');
                } catch (e) {}

                window.setTheme = function (mode) {
                    root.classList.toggle('dark', mode === 'dark');
                    try { localStorage.setItem('theme', mode); } catch (e) {}
                    window.dispatchEvent(new CustomEvent('theme-changed', { detail: mode }));
                };

                // Always print in light mode (paper is white)
                var wasDark = false;
                window.addEventListener('beforeprint', function () {
                    wasDark = root.classList.contains('dark');
                    if (wasDark) { root.classList.remove('dark'); window.dispatchEvent(new CustomEvent('theme-changed', { detail: 'light' })); }
                });
                window.addEventListener('afterprint', function () {
                    if (wasDark) { root.classList.add('dark'); window.dispatchEvent(new CustomEvent('theme-changed', { detail: 'dark' })); }
                });
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=public-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased bg-canvas text-slate-800 dark:bg-[#0B1220] dark:text-slate-200 transition-colors">
    @php
        $user = auth()->user();
        $unreadCount = $user->notifications()->where('is_read', false)->count();

        // Role-based menus. First matching role wins — same spirit as the
        // middleware gates: what you SEE is driven by who you ARE.
        $menus = [
            'reporting-personnel' => [
                ['label' => 'Dashboard', 'route' => 'reporting.dashboard', 'icon' => 'home', 'match' => 'reporting.dashboard'],
                ['label' => 'Create SITREP', 'route' => 'reporting.sitreps.create', 'icon' => 'plus', 'match' => 'reporting.sitreps.create'],
                // Explicit route list (NOT a wildcard): the old
                // 'reporting.sitreps.*' matched CREATE too, so both nav
                // items highlighted. Only these routes count as My Reports.
                ['label' => 'My Reports', 'route' => 'reporting.sitreps.index', 'icon' => 'doc',
                 'match' => ['reporting.sitreps.index', 'reporting.sitreps.show', 'reporting.sitreps.edit']],
            ],
            'command-center' => [
                ['label' => 'Dashboard', 'route' => 'command.dashboard', 'icon' => 'home', 'match' => 'command.dashboard'],
                ['label' => 'Incoming SITREPs', 'route' => 'command.incoming', 'icon' => 'inbox', 'match' => 'command.incoming'],
                ['label' => 'All Reports', 'route' => 'command.reports.index', 'icon' => 'doc', 'match' => 'command.reports.*'],
                ['label' => 'Map View', 'route' => 'command.map', 'icon' => 'pin', 'match' => 'command.map*'],
            ],
            'administrator' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home', 'match' => 'admin.dashboard'],
                ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'user', 'match' => 'admin.users.*'],
                ['label' => 'Units', 'route' => 'admin.units.index', 'icon' => 'building', 'match' => 'admin.units.*'],
                ['label' => 'GPS Accuracy Study', 'route' => 'admin.gps.index', 'icon' => 'target',
                 'match' => ['admin.gps.index', 'admin.gps.points.*', 'admin.gps.capture', 'admin.gps.observations.*']],
                ['label' => 'Study Results', 'route' => 'admin.gps.results', 'icon' => 'chart', 'match' => 'admin.gps.results'],
            ],
        ];
        $menu = collect($menus)->first(fn ($items, $role) => $user->hasRole($role)) ?? [];

        // Friendly role label for the sidebar identity block
        $roleLabels = ['administrator' => 'Administrator', 'command-center' => 'Command Center', 'reporting-personnel' => 'Reporting Personnel'];
        $roleLabel = collect($roleLabels)->first(fn ($label, $role) => $user->hasRole($role)) ?? 'User';
    @endphp

    {{-- Layout state lives in Alpine; the collapsed flag mirrors the
         `sb-collapsed` class on <html>, which drives the [.sb-collapsed_&]
         utilities below (so the first paint is already correct). --}}
    <div x-data="{
            sidebarOpen: false,
            confirmLogout: false,
            collapsed: document.documentElement.classList.contains('sb-collapsed'),
            dark: document.documentElement.classList.contains('dark'),
            toggleCollapse() {
                this.collapsed = ! this.collapsed;
                document.documentElement.classList.toggle('sb-collapsed', this.collapsed);
                try { localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0'); } catch (e) {}
                setTimeout(() => window.dispatchEvent(new Event('resize')), 220); // let charts re-fit
            },
            theme(mode) { this.dark = mode === 'dark'; window.setTheme(mode); },
         }"
         @theme-changed.window="dark = $event.detail === 'dark'"
         class="min-h-screen">

        {{-- ============ SIDEBAR ============ --}}
        <div x-show="sidebarOpen" x-transition.opacity x-cloak
             class="fixed inset-0 z-30 bg-navy-950/60 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false"></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-[17rem] flex-col print:hidden
                      border-r border-slate-200/80 bg-white
                      dark:border-white/[0.06] dark:bg-[#080E1B]
                      transform transition-[transform,width] duration-200 -translate-x-full lg:translate-x-0
                      lg:[.sb-collapsed_&]:w-[5.25rem]"
               :class="sidebarOpen ? 'translate-x-0' : ''"
               aria-label="Sidebar">

            {{-- Brand --}}
            <div class="flex h-16 shrink-0 items-center gap-3 px-5 lg:[.sb-collapsed_&]:justify-center lg:[.sb-collapsed_&]:px-0">
                {{-- Seal placeholder: swap for the official PNP logo image when CNPPO provides it --}}
                <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-navy-700 to-navy-900 text-[11px] font-extrabold tracking-wide text-gold-400 shadow-md shadow-navy-900/20 ring-1 ring-gold-400/30">
                    PNP
                </div>
                <div class="min-w-0 leading-tight lg:[.sb-collapsed_&]:hidden">
                    <p class="flex items-center gap-1.5 text-[15px] font-bold tracking-tight text-navy-900 dark:text-white">
                        CNPPO
                        <span class="rounded-md bg-gold-400/15 px-1.5 py-0.5 text-[10px] font-bold tracking-wider text-gold-700 dark:text-gold-300">SITREP</span>
                    </p>
                    <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">Camarines Norte Police Provincial Office</p>
                </div>
            </div>

            {{-- Navigation --}}
            @php
                // Group the role menu: first item is the dashboard, the rest are work screens
                $groups = ['Overview' => array_slice($menu, 0, 1), 'Workspace' => array_slice($menu, 1)];
            @endphp
            <nav class="flex-1 overflow-y-auto px-3 pb-4 pt-2 lg:[.sb-collapsed_&]:px-3" aria-label="Main">
                @foreach ($groups as $groupLabel => $items)
                    @continue(empty($items))
                    <p class="px-3 pb-2 {{ $loop->first ? 'pt-2' : 'pt-6' }} text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500 lg:[.sb-collapsed_&]:hidden">{{ $groupLabel }}</p>
                    @unless ($loop->first)
                        <div class="mx-auto my-3 hidden h-px w-8 bg-slate-200 dark:bg-white/10 lg:[.sb-collapsed_&]:block"></div>
                    @endunless
                    <div class="space-y-1">
                        @foreach ($items as $item)
                            @php $active = request()->routeIs($item['match']); @endphp
                            <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif
                               title="{{ $item['label'] }}"
                               class="group relative flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors
                                      lg:[.sb-collapsed_&]:justify-center lg:[.sb-collapsed_&]:px-0
                                      {{ $active
                                          ? 'bg-navy-800 text-white shadow-md shadow-navy-900/15 dark:bg-white/[0.08] dark:shadow-none dark:ring-1 dark:ring-white/10'
                                          : 'text-slate-600 hover:bg-slate-100 hover:text-navy-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white' }}">
                                <x-icon name="{{ $item['icon'] }}"
                                        class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-gold-400' : 'text-slate-400 group-hover:text-navy-700 dark:text-slate-500 dark:group-hover:text-slate-200' }}" />
                                <span class="flex-1 truncate lg:[.sb-collapsed_&]:hidden">{{ $item['label'] }}</span>
                                @if ($active)
                                    <span class="h-1.5 w-1.5 rounded-full bg-gold-400 lg:[.sb-collapsed_&]:absolute lg:[.sb-collapsed_&]:right-1.5 lg:[.sb-collapsed_&]:top-1.5" aria-hidden="true"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            {{-- Footer: signed-in user --}}
            <div class="shrink-0 border-t border-slate-200/80 p-3 dark:border-white/[0.06]">
                {{-- User --}}
                <div class="flex items-center gap-3 rounded-xl px-2 py-1.5 lg:[.sb-collapsed_&]:flex-col lg:[.sb-collapsed_&]:px-0">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-gold-300 to-gold-500 text-sm font-bold text-navy-900" title="{{ $user->name }}">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1 lg:[.sb-collapsed_&]:hidden">
                        <p class="truncate text-sm font-semibold text-navy-900 dark:text-white">{{ $user->name }}</p>
                        <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $roleLabel }}</p>
                    </div>
                    <button type="button" @click="confirmLogout = true" title="Log out" aria-label="Log out"
                            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                        <x-icon name="logout" class="h-[18px] w-[18px]" />
                    </button>
                </div>
            </div>
        </aside>

        {{-- ============ MAIN COLUMN ============ --}}
        <div class="flex min-h-screen flex-col transition-[padding] duration-200 lg:pl-[17rem] lg:[.sb-collapsed_&]:pl-[5.25rem] print:pl-0">

            {{-- Top bar --}}
            <header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/80 backdrop-blur-md print:hidden
                           dark:border-white/[0.06] dark:bg-[#0B1220]/80">
                <div class="flex h-16 items-center gap-2 px-4 sm:px-6">
                    <button @click="sidebarOpen = true" class="-ml-2 rounded-lg p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5 lg:hidden" aria-label="Open menu">
                        <x-icon name="menu" class="h-5 w-5" />
                    </button>
                    <button @click="toggleCollapse()" class="-ml-2 hidden rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-navy-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white lg:inline-flex"
                            :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'" :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                        <x-icon name="panel" class="h-5 w-5" />
                    </button>
                    <div class="mx-1 hidden h-6 w-px bg-slate-200 dark:bg-white/10 lg:block"></div>

                    {{-- Page titles are written as white text in each view; recolor them for this bar --}}
                    <div class="min-w-0 flex-1 [&_h2]:!text-base [&_h2]:!text-navy-900 dark:[&_h2]:!text-white">
                        {{ $header ?? '' }}
                    </div>

                    <p class="hidden 2xl:block text-[10px] font-semibold tracking-[0.25em] text-slate-400 dark:text-slate-500">SERVICE · INTEGRITY · PEACE AND ORDER</p>

                    {{-- Quick theme toggle --}}
                    <button type="button" @click="theme(dark ? 'light' : 'dark')"
                            class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-navy-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-gold-300"
                            :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'" :title="dark ? 'Light mode' : 'Dark mode'">
                        <x-icon name="moon" class="h-5 w-5" x-show="! dark" />
                        <x-icon name="sun" class="h-5 w-5" x-show="dark" x-cloak />
                    </button>

                    {{-- Bell (sole entry point to notifications) --}}
                    <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-navy-900 dark:text-slate-400 dark:hover:bg-white/5 dark:hover:text-white" aria-label="Notifications">
                        <x-icon name="bell" class="h-5 w-5" />
                        @if ($unreadCount > 0)
                            <span class="absolute -right-0.5 -top-0.5 inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-white dark:ring-[#0B1220]">{{ $unreadCount }}</span>
                        @endif
                    </a>

                    {{-- User dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-lg p-1.5 text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-white/5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-gold-300 to-gold-500 text-sm font-bold text-navy-900">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <span class="hidden text-sm font-medium md:block">{{ $user->name }}</span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition
                             class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm text-slate-700 shadow-lg dark:border-white/10 dark:bg-[#111A2E] dark:text-slate-200">
                            <div class="border-b border-slate-100 px-4 py-2.5 dark:border-white/10">
                                <p class="truncate font-semibold text-navy-900 dark:text-white">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $roleLabel }}@if($user->unit) · {{ $user->unit->name }}@endif</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-slate-50 dark:hover:bg-white/5">Profile</a>
                            <button type="button" @click="open = false; confirmLogout = true"
                                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                                <x-icon name="logout" class="h-4 w-4" /> Log Out
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>

        {{-- ============ LOGOUT CONFIRMATION ============
             Both logout buttons (sidebar + user menu) open this dialog; only
             the "Log out" button here actually submits the POST /logout form. --}}
        <div x-show="confirmLogout" x-cloak
             x-effect="if (confirmLogout) $nextTick(() => $refs.cancelLogout.focus())"
             @keydown.escape.window="confirmLogout = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 print:hidden"
             role="dialog" aria-modal="true" aria-labelledby="logout-title" aria-describedby="logout-desc">
            <div x-show="confirmLogout" x-transition.opacity class="absolute inset-0 bg-navy-950/60 backdrop-blur-sm" @click="confirmLogout = false"></div>

            <div x-show="confirmLogout"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-white/10 dark:bg-[#111A2E]">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400">
                    <x-icon name="logout" class="h-6 w-6" />
                </div>
                <h2 id="logout-title" class="mt-4 text-lg font-semibold text-navy-900 dark:text-white">Log out of CNPPO SITREP?</h2>
                <p id="logout-desc" class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
                    You'll need to sign in again to continue. Any unsaved changes on this page will be lost.
                </p>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" x-ref="cancelLogout" @click="confirmLogout = false"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/15 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10">
                        Cancel
                    </button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                            <x-icon name="logout" class="h-4 w-4" /> Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </body>
</html>
