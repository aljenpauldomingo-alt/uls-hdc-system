@php
    $editing = $counselor->exists;
@endphp

<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('director.counselors.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100">&larr; {{ __('All counselors') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $editing ? __('Edit :name', ['name' => $counselor->name]) : __('Add counselor') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            @if ($editing && ! $counselor->is_active)
                <div class="rounded-md bg-gray-100 dark:bg-gray-700 px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ __('This account is deactivated. Reactivate it from the counselors list to let them log in again.') }}</div>
            @endif

            <form method="POST" action="{{ $editing ? route('director.counselors.update', $counselor) : route('director.counselors.store') }}"
                class="glass-panel sm:rounded-lg p-4 sm:p-6 space-y-6">

                <div class="grid gap-6 sm:grid-cols-3">
                    <x-form.text name="last_name" label="Last name" :value="$counselor->last_name" required maxlength="100" pattern="[\p{L}\p{M} .'\-]+" title="Letters, spaces, periods, apostrophes and hyphens only." autocomplete="off" />
                    <x-form.text name="first_name" label="First name" :value="$counselor->first_name" required maxlength="100" pattern="[\p{L}\p{M} .'\-]+" title="Letters, spaces, periods, apostrophes and hyphens only." autocomplete="off" />
                    <x-form.text name="middle_name" label="Middle name" :value="$counselor->middle_name" maxlength="100" pattern="[\p{L}\p{M} .'\-]+" title="Letters, spaces, periods, apostrophes and hyphens only." autocomplete="off" />
                </div>

                <x-form.text name="email" label="Email" type="email" :value="$counselor->email" required maxlength="255" autocomplete="off" />

                <x-form.text name="password" type="password" minlength="8" maxlength="255" autocomplete="new-password" :required="! $editing"
                    :label="$editing ? 'Reset password (optional)' : 'Temporary password'"
                    :hint="$editing
                        ? 'Leave blank to keep the current password. If you set one, they must change it on next login.'
                        : 'Give this to the counselor. They must change it on first login. At least 8 characters, with upper and lower case letters and a number.'" />

                <x-form.checkboxes name="colleges" label="Assigned colleges" required :columns="2"
                    :options="$colleges->mapWithKeys(fn ($college) => [$college->id => $college->code.' – '.$college->name])->all()"
                    :value="$counselor->colleges?->pluck('id')->all() ?? []"
                    hint="The counselor will only see students in these colleges. Choose at least one." />

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700 pt-6">
                    <a href="{{ route('director.counselors.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ $editing ? __('Save changes') : __('Create counselor') }}</x-primary-button>
                </div>

                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
