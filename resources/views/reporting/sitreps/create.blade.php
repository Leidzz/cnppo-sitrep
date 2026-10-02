<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('Create SITREP') }}</h2>
    </x-slot>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
        <div class="max-w-xl mx-auto"
             x-data="{ type: {{ old('report_type_id') ?? 'null' }} }">

            {{-- Validation summary (mobile: first thing seen on error) --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="list-disc pl-5 mt-1 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('reporting.sitreps.store') }}">
                @csrf

                {{-- 1. Report type picker: big tappable cards, one column on mobile --}}
                <fieldset>
                    <legend class="text-base font-semibold text-gray-900">Report Type</legend>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        @foreach ($reportTypes as $rt)
                            <label class="flex items-center gap-3 rounded-lg border p-4 cursor-pointer min-h-[44px]
                                          has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50 has-[:checked]:ring-1 has-[:checked]:ring-blue-600">
                                <input type="radio" name="report_type_id" value="{{ $rt->id }}"
                                       x-model.number="type" class="sr-only">
                                <span class="text-sm font-medium text-gray-900">{{ $rt->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('report_type_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </fieldset>

                {{-- 2. Dynamic fields: one block per report type, Alpine shows the chosen one.
                       NOTHING here is hardcoded — labels, keys, required, and input type
                       all come from template_fields.
                       FIX: inputs of unselected types are DISABLED, so the browser
                       never submits them (duplicate names would overwrite each other). --}}
                @foreach ($reportTypes as $rt)
                    <div x-show="type === {{ $rt->id }}" x-cloak class="mt-8 space-y-5">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $rt->name }} Details</h3>

                        @foreach ($rt->templateFields as $field)
                            <div>
                                <label for="df_{{ $field->field_key }}_{{ $rt->id }}"
                                       class="block text-sm font-medium text-gray-700">
                                    {{ $field->label }}
                                    @if($field->is_required) <span class="text-red-600">*</span> @endif
                                </label>

                                @if ($field->field_type === 'textarea')
                                    <textarea id="df_{{ $field->field_key }}_{{ $rt->id }}"
                                              name="dynamic_fields[{{ $field->field_key }}]"
                                              rows="4"
                                              :disabled="type !== {{ $rt->id }}"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                                     focus:border-blue-500 focus:ring-blue-500
                                                     disabled:bg-gray-50 disabled:text-gray-400">{{ old('dynamic_fields.'.$field->field_key) }}</textarea>
                                @else
                                    <input type="text" id="df_{{ $field->field_key }}_{{ $rt->id }}"
                                           name="dynamic_fields[{{ $field->field_key }}]"
                                           value="{{ old('dynamic_fields.'.$field->field_key) }}"
                                           :disabled="type !== {{ $rt->id }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                                  focus:border-blue-500 focus:ring-blue-500 min-h-[44px]
                                                  disabled:bg-gray-50 disabled:text-gray-400">
                                @endif

                                @error('dynamic_fields.'.$field->field_key)
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                @endforeach

                {{-- 3. Placeholders for the next parts (kept visible so the layout
                       doesn't jump later, honestly labeled) --}}
                <div class="mt-8 rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500">
                    Location capture (GPS) and photo attachments arrive in the next development step.
                </div>

                {{-- 4. Actions: full-width on mobile, side by side on desktop.
                       'action' tells the server which path to take. --}}
                <div class="mt-8 flex flex-col-reverse sm:flex-row gap-3">
                    <button type="submit" name="action" value="draft"
                            class="min-h-[44px] px-6 py-3 rounded-lg border border-gray-300 bg-white text-gray-700 font-semibold hover:bg-gray-50">
                        Save Draft
                    </button>
                    <button type="submit" name="action" value="submit"
                            class="min-h-[44px] px-6 py-3 rounded-lg bg-blue-700 text-white font-semibold hover:bg-blue-800">
                        Submit SITREP
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>