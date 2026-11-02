<x-guest-layout>
    {{-- Session status (password reset confirmation etc.) --}}
    <x-auth-session-status class="mb-4 text-sm text-green-300" :status="session('status')" />

    <h1 class="text-lg font-bold text-white">Log in</h1>
    <p class="mt-1 mb-6 text-sm text-white/50">Enter your assigned credentials to continue.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-white/80">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1 block w-full rounded-lg border-white/10 bg-white/5 text-white placeholder-white/30
                          focus:border-amber-400 focus:ring-amber-400 min-h-[44px]">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-white/80">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="mt-1 block w-full rounded-lg border-white/10 bg-white/5 text-white placeholder-white/30
                          focus:border-amber-400 focus:ring-amber-400 min-h-[44px]">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-white/20 bg-white/10 text-amber-400 focus:ring-amber-400">
                <span class="ms-2 text-sm text-white/60">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-amber-300 hover:text-amber-200 hover:underline" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <button type="submit"
                class="w-full min-h-[48px] rounded-lg bg-amber-500 hover:bg-amber-400 text-[#16233F] font-bold text-sm tracking-wide transition shadow-lg shadow-amber-500/20">
            LOG IN
        </button>
    </form>
</x-guest-layout>