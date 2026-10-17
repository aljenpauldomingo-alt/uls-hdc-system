@props(['name', 'label', 'value' => null, 'type' => 'text', 'required' => false, 'hint' => null])

@php
    $id = str_replace('.', '_', $name);
    $htmlName = \App\Support\FormName::html($name);
    $extra = $required ? ['required' => true] : [];
@endphp

<div {{ $attributes->only(['class', 'x-show', 'x-cloak']) }}>
    <x-input-label :for="$id">
        {{ $label }} @if ($required)<span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>@endif
    </x-input-label>
    <x-text-input :id="$id" :name="$htmlName" :type="$type" class="mt-1 block w-full"
        :value="old($name, $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value)"
        {{ $attributes->except(['class', 'x-show', 'x-cloak'])->merge($extra) }} />
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
