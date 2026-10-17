{{-- Filter bar for staff lists. Expects $query (StudentRecordQuery), $action (URL) and $statuses (key => label). --}}
@php
    use App\Support\Form5;

    $filters = $query->filters();
    $selectClasses = 'mt-1 block w-full glass-input focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:focus:border-indigo-400 dark:focus:ring-indigo-400 text-sm';
@endphp

<form method="GET" action="{{ $action }}" class="glass-panel sm:rounded-lg p-4" role="search">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-1">
            <x-input-label for="q" :value="__('Search')" />
            <x-text-input id="q" name="q" type="search" class="mt-1 block w-full text-sm" :value="$filters['q']" placeholder="{{ __('Name or student no.') }}" />
        </div>

        <div>
            <x-input-label for="college_id" :value="__('College')" />
            <select id="college_id" name="college_id" class="{{ $selectClasses }}">
                <option value="">{{ __('All my colleges') }}</option>
                @foreach ($query->colleges() as $college)
                    <option value="{{ $college->id }}" @selected($filters['college_id'] === $college->id)>{{ $college->code }} – {{ $college->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="program_id" :value="__('Program')" />
            <select id="program_id" name="program_id" class="{{ $selectClasses }}">
                <option value="">{{ __('All programs') }}</option>
                @foreach ($query->programs() as $program)
                    <option value="{{ $program->id }}" @selected($filters['program_id'] === $program->id)>{{ $program->code }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="year_level" :value="__('Year level')" />
            <select id="year_level" name="year_level" class="{{ $selectClasses }}">
                <option value="">{{ __('All year levels') }}</option>
                @foreach (Form5::YEAR_LEVELS as $level => $label)
                    <option value="{{ $level }}" @selected($filters['year_level'] === $level)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select id="status" name="status" class="{{ $selectClasses }}">
                <option value="">{{ __('Any status') }}</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="sata" :value="__('SATA')" />
            <select id="sata" name="sata" class="{{ $selectClasses }}">
                <option value="">{{ __('Any') }}</option>
                @foreach (\App\Support\StudentRecordQuery::SATA_FILTERS as $key => $label)
                    <option value="{{ $key }}" @selected($filters['sata'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <x-primary-button>{{ __('Apply filters') }}</x-primary-button>
        @if (array_filter($filters))
            <a href="{{ $action }}" class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100">{{ __('Clear') }}</a>
        @endif
        <a href="{{ route('staff.records.export', request()->only(['college_id', 'program_id', 'year_level', 'status', 'sata', 'q'])) }}"
           class="ms-auto inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
            {{ __('Export CSV') }}
        </a>
    </div>
</form>
