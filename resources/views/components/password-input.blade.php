{{-- Password input with a show/hide (eye) button. Wrapper gets `class`; everything else goes on the input.
     The toggle is plain JavaScript (resources/js/password-toggle.js), so it works without Alpine. --}}
@props(['id', 'invalid' => false])

<div {{ $attributes->only('class')->merge(['class' => 'relative']) }} data-password-field>
    <x-text-input :id="$id" type="password" :invalid="$invalid" class="block w-full pe-11"
        {{ $attributes->except('class') }} />
    <button type="button" data-password-toggle aria-controls="{{ $id }}" aria-pressed="false"
        aria-label="{{ __('Show password') }}" title="{{ __('Show password') }}"
        data-show-label="{{ __('Show password') }}" data-hide-label="{{ __('Hide password') }}"
        class="absolute inset-y-0 end-0 flex w-10 items-center justify-center rounded-e-md text-gray-500 transition hover:text-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500 disabled:hidden dark:text-gray-400 dark:hover:text-gray-100">
        {{-- Eye: password is hidden, click to show --}}
        <svg data-icon="show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        {{-- Eye-slash: password is visible, click to hide --}}
        <svg data-icon="hide" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
        </svg>
    </button>
</div>
