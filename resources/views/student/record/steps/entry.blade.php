@php use App\Support\Form5; @endphp

<div class="grid gap-6 sm:grid-cols-2">
    <x-form.select name="month_of_entry" label="Month of entry" :options="Form5::MONTHS" :value="$record->month_of_entry" required />
    <x-form.text name="school_year" label="School year" :value="$record->school_year" placeholder="2026-2027" required />
</div>

<x-form.radios name="semester" label="Semester" :options="Form5::SEMESTERS" :value="$record->semester" required />

<x-form.radios name="student_type" label="Student type" :options="Form5::STUDENT_TYPES" :value="$record->student_type" required />

<div x-data="{
        preview: @js($record->photo_path ? route('student.record.photo', $record) : null),
        error: null,
        pick(event) {
            const file = event.target.files[0];
            this.error = null;
            if (! file) { return; }
            if (! ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                this.error = 'Choose a JPG, PNG or WEBP image.';
                event.target.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                this.error = 'The photo must be 2 MB or smaller.';
                event.target.value = '';
                return;
            }
            this.preview = URL.createObjectURL(file);
        }
    }">
    <x-input-label for="photo" :value="__('Photo (2x2) *')" />
    <div class="mt-2 flex items-center gap-4">
        <img x-show="preview" x-cloak :src="preview" alt="{{ __('Your 2x2 photo') }}" class="h-24 w-24 rounded border border-gray-200 object-cover dark:border-gray-600">
        <div x-show="! preview" class="flex h-24 w-24 items-center justify-center rounded border border-dashed border-gray-400 px-2 text-center text-xs text-gray-500 dark:border-gray-500 dark:text-gray-400">{{ __('No photo selected') }}</div>
    </div>
    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" @change="pick($event)" @if (! $record->photo_path) required @endif
        class="mt-2 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-300 dark:file:bg-indigo-500/20 dark:file:text-indigo-200 dark:hover:file:bg-indigo-500/30">
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Required. JPG, PNG or WEBP, up to 2 MB.') }}</p>
    <p x-show="error" x-cloak x-text="error" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
</div>
