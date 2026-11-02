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
    <body class="font-sans antialiased min-h-screen flex flex-col items-center justify-center px-4 py-10 bg-gradient-to-br from-[#0F1A2E] via-[#16233F] to-[#0F1A2E] text-white">

        {{-- Wordmark --}}
        <div class="anim-rise flex items-center gap-3 mb-6">
            <div class="h-12 w-12 rounded-full bg-white/10 flex items-center justify-center text-lg font-bold text-amber-400 border border-amber-400/40">PNP</div>
            <div>
                <p class="text-xl font-bold leading-tight tracking-wide">CNPPO</p>
                <p class="text-[10px] uppercase tracking-wider text-white/50">Camarines Norte Police Provincial Office</p>
            </div>
        </div>
        <div class="anim-rise mb-8 text-center" style="animation-delay:80ms">
            <p class="text-sm font-semibold tracking-wide text-white/90">SITREP Monitoring System</p>
            <div class="mx-auto mt-2 h-0.5 w-12 bg-amber-400"></div>
        </div>

        {{-- Card --}}
        <div class="anim-rise w-full max-w-md rounded-2xl border border-white/10 bg-[#1B2745]/80 backdrop-blur p-6 sm:p-8 shadow-2xl" style="animation-delay:160ms">
            {{ $slot }}
        </div>

        <p class="anim-rise mt-8 text-[10px] tracking-[0.25em] text-white/40 font-medium" style="animation-delay:240ms">
            SERVICE · INTEGRITY · PEACE AND ORDER
        </p>
    </body>
</html>