<x-guest-layout>
    {{-- Session status (password reset confirmation etc.) --}}
    <x-auth-session-status class="mb-4 text-sm text-green-300" :status="session('status')" />

    <h1 class="text-3xl font-bold text-white">Log in</h1>
    <p class="mt-2 mb-8 text-sm text-white/50">Enter your assigned credentials to continue.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-semibold text-white/90">Email</label>
            <div class="relative mt-2">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-white/40" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 8l9 6 9-6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                </svg>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       placeholder="Enter your email"
                       class="block w-full min-h-[48px] rounded-xl border-white/10 bg-white/5 pl-11 text-white placeholder-white/30
                              focus:border-amber-400 focus:ring-amber-400">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-white/90">Password</label>
            <div class="relative mt-2">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-white/40" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M7 11V8a5 5 0 0110 0v3M6 11h12a1 1 0 011 1v8a1 1 0 01-1 1H6a1 1 0 01-1-1v-8a1 1 0 011-1z" />
                </svg>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       placeholder="Enter your password"
                       class="block w-full min-h-[48px] rounded-xl border-white/10 bg-white/5 pl-11 text-white placeholder-white/30
                              focus:border-amber-400 focus:ring-amber-400">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-white/20 bg-white/10 text-amber-400 focus:ring-amber-400">
                <span class="ms-2 text-sm text-white/60">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-amber-300 hover:text-amber-200 hover:underline" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <button type="submit"
                class="w-full min-h-[48px] rounded-xl bg-amber-500 hover:bg-amber-400 text-[#16233F] font-bold text-sm tracking-wide transition shadow-lg shadow-amber-500/20">
            LOG IN
        </button>
    </form>
</x-guest-layout>