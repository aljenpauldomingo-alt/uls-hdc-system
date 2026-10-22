{{-- Paper-style "Question? [Yes] [No]" toggle pair. Leaving both unselected means "not answered". --}}
@props(['name', 'label', 'value' => null, 'required' => false])

@php
    $current = session()->hasOldInput() ? old($name) : $value;
    $current = is_bool($current) ? (int) $current : $current;
    $labelId = $name.'_label';
    $hasError = $errors->has($name);
    $choiceClasses = 'block cursor-pointer px-3.5 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-white/70 dark:text-gray-300 dark:hover:bg-slate-700/70 peer-focus-visible:ring-2 peer-focus-visible:ring-inset peer-focus-visible:ring-indigo-500';
@endphp

<div {{ $attributes->merge(['class' => 'py-1.5']) }}>
    <div class="flex items-center justify-between gap-3" role="radiogroup" aria-labelledby="{{ $labelId }}" @if ($required) aria-required="true" @endif @if ($hasError) aria-invalid="true" @endif>
        <span id="{{ $labelId }}" class="text-sm text-gray-800 dark:text-gray-200">{{ $label }}?</span>
        <span @class([
            'inline-flex shrink-0 overflow-hidden rounded-md border bg-white/50 shadow-sm dark:bg-slate-800/50',
            'border-gray-300 dark:border-white/10' => ! $hasError,
            'border-red-500 dark:border-red-500' => $hasError,
        ])>
            <label class="border-e border-gray-300 dark:border-white/10">
                <input type="radio" name="{{ $name }}" value="1" class="peer sr-only" @checked($current !== null && (string) $current === '1') @required($required)>
                <span class="{{ $choiceClasses }} peer-checked:bg-indigo-600 peer-checked:text-white dark:peer-checked:bg-indigo-500">{{ __('Yes') }}</span>
            </label>
            <label>
                <input type="radio" name="{{ $name }}" value="0" class="peer sr-only" @checked($current !== null && (string) $current === '0') @required($required)>
                <span class="{{ $choiceClasses }} peer-checked:bg-gray-700 peer-checked:text-white dark:peer-checked:bg-gray-200 dark:peer-checked:text-gray-900">{{ __('No') }}</span>
            </label>
        </span>
    </div>
    <x-input-error :messages="$errors->get($name)" class="mt-1 text-end" />
</div>
