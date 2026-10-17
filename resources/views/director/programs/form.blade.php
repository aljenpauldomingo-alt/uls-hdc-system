<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('director.colleges.edit', $program->college_id) }}" class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100">&larr; {{ __('Back to college') }}</a>
        <h2 class="mt-1 font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Edit program :code', ['code' => $program->code]) }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            <form method="POST" action="{{ route('director.programs.update', $program) }}" class="glass-panel sm:rounded-lg p-4 sm:p-6 space-y-6">

                <div>
                    <x-input-label for="college_id">{{ __('College') }} <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span></x-input-label>
                    <select id="college_id" name="college_id" class="mt-1 block w-full glass-input focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:focus:border-indigo-400 dark:focus:ring-indigo-400">
                        @foreach ($colleges as $college)
                            <option value="{{ $college->id }}" @selected((string) old('college_id', $program->college_id) === (string) $college->id)>{{ $college->code }} – {{ $college->name }}</option>
                        @endforeach
                    </select>
                    @if ($program->students_count)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Moving this program also moves its :count student(s) to the new college, which changes which counselors can see them.', ['count' => $program->students_count]) }}</p>
                    @endif
                    <x-input-error :messages="$errors->program->get('college_id')" class="mt-2" />
                </div>

                <div class="grid gap-6 sm:grid-cols-3">
                    <div>
                        <x-input-label for="code">{{ __('Code') }} <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span></x-input-label>
                        <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $program->code)" maxlength="20" pattern="[A-Za-z0-9\-]+" title="Letters, numbers and dashes only." required />
                        <x-input-error :messages="$errors->program->get('code')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="name">{{ __('Name') }} <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span></x-input-label>
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $program->name)" maxlength="255" required />
                        <x-input-error :messages="$errors->program->get('name')" class="mt-2" />
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700 pt-6">
                    <a href="{{ route('director.colleges.edit', $program->college_id) }}" class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Save program') }}</x-primary-button>
                </div>

                @csrf
                @method('PUT')
            </form>

            <section class="glass-panel sm:rounded-lg p-4 sm:p-6" aria-labelledby="delete-program-heading">
                <h3 id="delete-program-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Delete program') }}</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    @if ($program->students_count)
                        {{ trans_choice('This program has :count enrolled student, so it cannot be deleted.|This program has :count enrolled students, so it cannot be deleted.', $program->students_count) }}
                    @else
                        {{ __('No students are enrolled in this program, so it can be deleted.') }}
                    @endif
                </p>
                <form method="POST" action="{{ route('director.programs.destroy', $program) }}" class="mt-4"
                    x-data x-on:submit="if (! confirm(@js(__('Delete :code? This cannot be undone.', ['code' => $program->code])))) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <x-danger-button :disabled="$program->students_count > 0" class="disabled:cursor-not-allowed disabled:opacity-50">{{ __('Delete program') }}</x-danger-button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
