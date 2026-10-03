@if (session('status'))
    <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800 flex items-center gap-2">
        <x-icon name="check" class="w-5 h-5 shrink-0 text-green-600" />
        {{ session('status') }}
    </div>
@endif