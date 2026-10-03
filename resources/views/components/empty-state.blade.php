@props(['action' => null])

<div class="rounded-xl border-2 border-dashed border-gray-200 p-8 text-center">
    <p class="text-sm text-gray-500">{{ $slot }}</p>
    {{ $action }}
</div>