<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto">

            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                Welcome, {{ auth()->user()->name }}
            </h1>
            <p class="mt-2 text-base text-gray-600">
                {{ auth()->user()->unit?->name ?? 'No unit assigned' }}
            </p>

            <div class="mt-8">
                <a href="#"
                   class="flex sm:inline-flex items-center justify-center w-full sm:w-auto
                          min-h-[44px] px-6 py-3 rounded-lg bg-blue-700 text-white
                          text-base font-semibold shadow hover:bg-blue-800
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    + Create SITREP
                </a>
            </div>

            <p class="mt-6 text-sm text-gray-500">
                Your recent SITREPs and status counts will appear here in a later
                development step.
            </p>

        </div>
    </div>
</x-app-layout>