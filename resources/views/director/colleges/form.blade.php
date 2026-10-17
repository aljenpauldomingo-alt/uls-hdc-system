@php
    $editing = $college->exists;
@endphp

<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('director.colleges.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100">&larr; {{ __('All colleges') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $editing ? $college->code.' – '.$college->name : __('Add college') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <form method="POST" action="{{ $editing ? route('director.colleges.update', $college) : route('director.colleges.store') }}"
                class="glass-panel sm:rounded-lg p-4 sm:p-6 space-y-6">

                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('College details') }}</h3>
                <div class="grid gap-6 sm:grid-cols-3">
                    <x-form.text name="code" label="Code" :value="$college->code" required maxlength="20" pattern="[A-Za-z0-9\-]+" title="Letters, numbers and dashes only." placeholder="CIT" hint="Must be unique." />
                    <x-form.text name="name" label="Name" :value="$college->name" required maxlength="255" class="sm:col-span-2" placeholder="College of Information Technology" />
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700 pt-6">
                    <a href="{{ route('director.colleges.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ $editing ? __('Save college') : __('Create college') }}</x-primary-button>
                </div>

                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
            </form>

            @if ($editing)
                {{-- Programs --}}
                <section class="glass-panel sm:rounded-lg overflow-hidden" aria-labelledby="programs-heading">
                    <h3 id="programs-heading" class="border-b border-gray-200 dark:border-gray-700 px-4 py-3 text-base font-semibold text-gray-900 dark:text-gray-100 sm:px-6">{{ __('Programs') }}</h3>

                    @if ($college->programs->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/40">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 sm:px-6">{{ __('Code') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Name') }}</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Students') }}</th>
                                        <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300 sm:px-6"><span class="sr-only">{{ __('Actions') }}</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($college->programs as $program)
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 sm:px-6">{{ $program->code }}</td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $program->name }}</td>
                                            <td class="px-4 py-3 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $program->students_count }}</td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap sm:px-6">
                                                <a href="{{ route('director.programs.edit', $program) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('Edit') }}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400 sm:px-6">{{ __('No programs yet.') }}</p>
                    @endif

                    <form method="POST" action="{{ route('director.programs.store') }}" class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-4 py-4 sm:px-6">
                        @csrf
                        <input type="hidden" name="college_id" value="{{ $college->id }}">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Add a program') }}</p>
                        <div class="mt-3 grid gap-4 sm:grid-cols-[10rem_1fr_auto] sm:items-start">
                            <div>
                                <x-input-label for="program_code" :value="__('Code')" />
                                <x-text-input id="program_code" name="code" class="mt-1 block w-full" :value="$errors->program->any() ? old('code') : ''" placeholder="BSIT" required />
                                <x-input-error :messages="$errors->program->get('code')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="program_name" :value="__('Name')" />
                                <x-text-input id="program_name" name="name" class="mt-1 block w-full" :value="$errors->program->any() ? old('name') : ''" placeholder="Bachelor of Science in Information Technology" required />
                                <x-input-error :messages="$errors->program->get('name')" class="mt-2" />
                            </div>
                            <x-primary-button class="sm:mt-6 justify-center">{{ __('Add program') }}</x-primary-button>
                        </div>
                    </form>
                </section>

                {{-- Counselors --}}
                <section class="glass-panel sm:rounded-lg p-4 sm:p-6" aria-labelledby="counselors-heading">
                    <h3 id="counselors-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Assigned counselors') }}</h3>
                    @forelse ($college->counselors as $counselor)
                        <p class="mt-2 text-sm text-gray-900 dark:text-gray-100">{{ $counselor->name }} <span class="text-gray-500 dark:text-gray-400">· {{ $counselor->email }}</span></p>
                    @empty
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('No active counselor assigned.') }}</p>
                    @endforelse
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Assign counselors from') }} <a href="{{ route('director.counselors.index') }}" class="text-indigo-600 dark:text-indigo-400 underline hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('the counselors page') }}</a>.
                    </p>
                </section>

                {{-- Delete --}}
                <section class="glass-panel sm:rounded-lg p-4 sm:p-6" aria-labelledby="delete-college-heading">
                    <h3 id="delete-college-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Delete college') }}</h3>
                    @if ($college->programs->isNotEmpty())
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('This college still has programs, so it cannot be deleted. Move or delete its programs first.') }}</p>
                    @else
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('This college has no programs and can be deleted.') }}</p>
                    @endif
                    <form method="POST" action="{{ route('director.colleges.destroy', $college) }}" class="mt-4"
                        x-data x-on:submit="if (! confirm(@js(__('Delete :code? This cannot be undone.', ['code' => $college->code])))) $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <x-danger-button :disabled="$college->programs->isNotEmpty()" class="disabled:cursor-not-allowed disabled:opacity-50">{{ __('Delete college') }}</x-danger-button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
