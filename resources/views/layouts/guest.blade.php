<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CNPPO SITREP') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=public-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <style>
            @keyframes rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
            .anim-rise { animation: rise .45s cubic-bezier(.22,.9,.35,1) both; }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased min-h-screen flex items-center justify-center px-4 py-8 bg-gradient-to-br from-[#0F1A2E] via-[#16233F] to-[#0F1A2E] text-white">

        {{-- Split panel: branding (left, desktop only) + form (right) --}}
        <div class="anim-rise w-full max-w-5xl grid lg:grid-cols-2 overflow-hidden rounded-3xl border border-white/10 bg-[#16233F]/70 shadow-2xl backdrop-blur">

            {{-- ============ LEFT: BRANDING ============ --}}
            <aside class="relative hidden lg:flex flex-col items-center justify-center gap-6 border-r border-white/10 bg-gradient-to-br from-[#1E2E52] via-[#16233F] to-[#0F1A2E] p-12 text-center">
                {{-- soft amber glow --}}
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(245,158,11,0.12),transparent_55%)]"></div>

                <div class="relative">
                    {{-- Seal placeholder: swap for the official PNP logo image when CNPPO provides it --}}
                    <div class="flex h-44 w-44 items-center justify-center rounded-full border border-dashed border-white/25">
                        <div class="flex h-32 w-32 items-center justify-center rounded-full border border-amber-400/40 bg-white/10 text-3xl font-bold text-amber-400">PNP</div>
                    </div>
                </div>

                <div class="relative">
                    <p class="text-3xl font-bold tracking-wide">CNPPO</p>
                    <p class="mt-1 text-[11px] uppercase tracking-wider text-white/60">Camarines Norte Police Provincial Office</p>
                    <div class="mx-auto mt-4 h-0.5 w-12 bg-amber-400"></div>
                    <p class="mt-4 text-sm font-semibold tracking-wide text-white/90">SITREP Monitoring System</p>
                </div>

                <p class="absolute bottom-6 text-[10px] font-medium tracking-[0.25em] text-white/40">
                    SERVICE · INTEGRITY · PEACE AND ORDER
                </p>
            </aside>

            {{-- ============ RIGHT: FORM ============ --}}
            <main class="flex flex-col justify-center p-6 sm:p-10 lg:p-14">
                {{-- Compact wordmark: only when the left panel is hidden (mobile / tablet) --}}
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-amber-400/40 bg-white/10 text-base font-bold text-amber-400">PNP</div>
                    <div>
                        <p class="text-lg font-bold leading-tight tracking-wide">CNPPO</p>
                        <p class="text-[10px] uppercase tracking-wider text-white/50">SITREP Monitoring System</p>
                    </div>
                </div>

                <div class="mx-auto w-full max-w-sm">
                    {{ $slot }}
                </div>

                <p class="mt-10 text-center text-[10px] font-medium tracking-[0.25em] text-white/40 lg:hidden">
                    SERVICE · INTEGRITY · PEACE AND ORDER
                </p>
            </main>
        </div>
    </body>
</html>