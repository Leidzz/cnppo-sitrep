<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=public-sans:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased bg-canvas text-slate-800">
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

    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        {{-- ============ SIDEBAR ============ --}}
        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>

        <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-navy-800 text-white flex flex-col print:hidden
                      transform transition-transform duration-200 -translate-x-full lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : ''">

            {{-- Wordmark --}}
            <div class="px-5 pt-5 pb-4 border-b border-white/10">
                <div class="flex items-center gap-3">
                    {{-- Seal placeholder: swap for the official PNP logo image when CNPPO provides it --}}
                    <div class="h-11 w-11 shrink-0 rounded-full bg-white/10 flex items-center justify-center text-sm font-bold tracking-wide text-gold-400 ring-1 ring-gold-400/50">PNP</div>
                    <div>
                        <p class="text-lg font-bold leading-tight tracking-wide">CNPPO</p>
                        <p class="text-[10px] uppercase tracking-wider text-white/60">Camarines Norte Police Provincial Office</p>
                    </div>
                </div>
                <p class="mt-3 text-sm font-semibold tracking-wide text-white/90">SITREP Monitoring System</p>
                <div class="mt-2 h-0.5 w-14 bg-gold-400"></div>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" aria-label="Main">
                <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-wider text-white/40">Menu</p>
                @foreach ($menu as $item)
                    @php $active = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}" @if($active) aria-current="page" @endif
                       class="relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition focus-visible:ring-offset-navy-800
                              {{ $active
                                 ? 'bg-white/10 text-white before:absolute before:-left-3 before:top-1.5 before:bottom-1.5 before:w-1 before:rounded-r before:bg-gold-400'
                                 : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                        <x-icon name="{{ $item['icon'] }}" class="w-5 h-5 shrink-0 {{ $active ? 'text-gold-400' : '' }}" />
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if (! empty($item['badge']))
                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-red-500 text-white text-xs font-bold">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            {{-- Footer: signed-in identity + version --}}
            <div class="px-4 py-4 border-t border-white/10">
                <div class="flex items-center gap-3 rounded-lg bg-white/5 px-3 py-2.5">
                    <div class="h-9 w-9 shrink-0 rounded-full bg-gold-400/15 ring-1 ring-gold-400/40 flex items-center justify-center text-gold-300 font-bold text-sm">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-white">{{ $user->name }}</p>
                        <p class="truncate text-[11px] text-white/50">{{ $roleLabel }}@if($user->unit) · {{ $user->unit->name }}@endif</p>
                    </div>
                </div>
                <p class="mt-3 px-1 text-[10px] tracking-wider text-white/30">WEB-BASED SITREP SYSTEM · v1.0</p>
            </div>
        </aside>

        {{-- ============ MAIN COLUMN ============ --}}
        <div class="lg:pl-64 print:pl-0 flex flex-col min-h-screen">
            {{-- Top bar --}}
            <header class="sticky top-0 z-20 bg-navy-800 text-white shadow print:hidden">
                <div class="flex items-center gap-3 px-4 sm:px-6 h-16">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2" aria-label="Open menu">
                        <x-icon name="menu" class="w-6 h-6" />
                    </button>

                    <div class="min-w-0 flex-1">
                        {{ $header ?? '' }}
                    </div>

                    <p class="hidden xl:block text-[11px] tracking-[0.25em] text-white/50 font-medium">SERVICE · INTEGRITY · PEACE AND ORDER</p>

                    {{-- Bell (sole entry point to notifications) --}}
                    <a href="{{ route('notifications.index') }}" class="relative p-2 text-white/80 hover:text-white" aria-label="Notifications">
                        <x-icon name="bell" class="w-6 h-6" />
                        @if ($unreadCount > 0)
                            <span class="absolute top-0.5 right-0.5 inline-flex items-center justify-center h-5 min-w-[20px] px-1 rounded-full bg-red-500 text-[11px] font-bold">{{ $unreadCount }}</span>
                        @endif
                    </a>

                    {{-- User dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-white/10">
                            <div class="h-8 w-8 rounded-full bg-gold-400/15 ring-1 ring-gold-400/40 flex items-center justify-center text-gold-300 font-bold text-sm">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <span class="hidden sm:block text-sm font-medium">{{ $user->name }}</span>
                            <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition
                             class="absolute right-0 mt-2 w-48 rounded-lg bg-white text-gray-800 shadow-lg py-1 text-sm">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="font-medium truncate">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">Log Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
    </div>
    </body>
</html>