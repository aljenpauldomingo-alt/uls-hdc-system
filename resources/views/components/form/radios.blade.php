{{-- Radio group. Pass `model` to bind the choice to an Alpine variable for conditional fields. --}}
@props(['name', 'label', 'options', 'value' => null, 'required' => false, 'model' => null])

@php
    $current = session()->hasOldInput() ? old($name) : $value;
    $current = is_bool($current) ? (int) $current : $current;
@endphp

<fieldset {{ $attributes->only(['class', 'x-show', 'x-cloak']) }}>
    <legend class="block font-medium text-sm text-gray-700 dark:text-gray-300">
        {{ $label }} @if ($required)<span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>@endif
    </legend>
    <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2">
        @foreach ($options as $optionValue => $optionLabel)
            <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                <input type="radio" name="{{ \App\Support\FormName::html($name) }}" value="{{ $optionValue }}"
                    class="border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-indigo-500 dark:focus:ring-indigo-400 dark:focus:ring-offset-gray-800"
                    @checked($current !== null && (string) $current === (string) $optionValue)
                    @if ($model) x-model="{{ $model }}" @endif
                    @if ($required) required @endif>
                {{ $optionLabel }}
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</fieldset>
