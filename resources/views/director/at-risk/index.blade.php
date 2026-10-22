@php
    use App\Enums\FollowUpStatus;
    use App\Support\AtRiskQuery;
    use App\Support\Form5;
    use App\Support\Form8;

    $selectClasses = 'mt-1 block w-full glass-input focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:focus:border-indigo-400 dark:focus:ring-indigo-400 text-sm';
    $badge = 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset';
    $sourceClasses = [
        'form5' => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/30',
        'form8' => 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-400/10 dark:text-violet-300 dark:ring-violet-400/30',
        'both' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-400/10 dark:text-indigo-300 dark:ring-indigo-400/30',
    ];
    $exportQuery = array_filter(request()->only(AtRiskQuery::QUERY_KEYS), fn ($value) => filled($value));
    $localDate = fn (?\DateTimeInterface $date, string $format = 'M j, Y') => $date ? \Carbon\CarbonImmutable::instance($date)->setTimezone(config('hdc.local_timezone'))->format($format) : '—';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('At Risk') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Students with at least one psychological concern on their latest submitted Form 5 or latest finalized Form 8. Director only; every view is recorded in the access log.') }}
                </p>
            </div>
            <form method="GET" action="{{ route('director.at-risk.export') }}"
                data-confirm="{{ __('Download this list as CSV? It contains confidential information about students\' concerns. Follow-up notes are never included.') }}"
                data-confirm-title="{{ __('Export At Risk list?') }}" data-confirm-button="{{ __('Download CSV') }}">
                @foreach ($exportQuery as $name => $value)
                    @foreach ((array) $value as $item)
                        <input type="hidden" name="{{ is_array($value) ? $name.'[]' : $name }}" value="{{ $item }}">
                    @endforeach
                @endforeach
                <x-secondary-button type="submit">{{ __('Download CSV') }}</x-secondary-button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Summary cards (within the selected period and colleges) --}}
            <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['label' => __('Total at risk'), 'value' => $summary['total'], 'query' => []],
                    ['label' => __('Urgent'), 'value' => $summary['urgent'], 'query' => ['urgent' => 1]],
                    ['label' => __('Not yet interviewed'), 'value' => $summary['not_interviewed'], 'query' => ['intake' => 'none']],
                    ['label' => __('Follow-up pending'), 'value' => $summary['follow_up_pending'], 'query' => []],
                ] as $card)
                    <a href="{{ route('director.at-risk.index', [...$scope->query(), ...$card['query']]) }}"
                        class="block glass-panel rounded-lg p-5 ring-1 ring-transparent transition hover:ring-indigo-300 dark:hover:ring-indigo-500/60" data-summary-card>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $card['label'] }}</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($card['value']) }}</p>
                    </a>
                @endforeach
            </div>

            {{-- Filters (kept in the URL) --}}
            <form method="GET" action="{{ route('director.at-risk.index') }}" class="glass-panel sm:rounded-lg p-4" role="search" aria-label="{{ __('At Risk filters') }}">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <x-input-label for="q" :value="__('Search')" />
                        <x-text-input id="q" name="q" type="search" class="mt-1 block w-full text-sm" :value="$filters['q']" placeholder="{{ __('Name or student no.') }}" />
                    </div>
                    <div>
                        <x-input-label for="school_year" :value="__('School year')" />
                        <select id="school_year" name="school_year" class="{{ $selectClasses }}">
                            <option value="">{{ __('All') }}</option>
                            @foreach ($schoolYears as $year)
                                <option value="{{ $year }}" @selected($scope->schoolYear === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="semester" :value="__('Semester')" />
                        <select id="semester" name="semester" class="{{ $selectClasses }}">
                            <option value="">{{ __('All') }}</option>
                            @foreach (Form5::SEMESTERS as $key => $label)
                                <option value="{{ $key }}" @selected($scope->semester === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="college_id" :value="__('College')" />
                        <select id="college_id" name="college_id" class="{{ $selectClasses }}">
                            <option value="">{{ __('All colleges') }}</option>
                            @foreach ($scope->collegeOptions() as $college)
                                <option value="{{ $college->id }}" @selected($scope->collegeId === $college->id)>{{ $college->code }} – {{ $college->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="program_id" :value="__('Course / program')" />
                        <select id="program_id" name="program_id" class="{{ $selectClasses }}">
                            <option value="">{{ __('All programs') }}</option>
                            @foreach ($scope->programOptions() as $program)
                                <option value="{{ $program->id }}" @selected($scope->programId === $program->id)>{{ $program->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="year_level" :value="__('Year level')" />
                        <select id="year_level" name="year_level" class="{{ $selectClasses }}">
                            <option value="">{{ __('All year levels') }}</option>
                            @foreach (Form5::YEAR_LEVELS as $level => $label)
                                <option value="{{ $level }}" @selected($scope->yearLevel === $level)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="student_type" :value="__('Student type')" />
                        <select id="student_type" name="student_type" class="{{ $selectClasses }}">
                            <option value="">{{ __('All student types') }}</option>
                            @foreach (Form5::STUDENT_TYPES as $type => $label)
                                <option value="{{ $type }}" @selected($filters['student_type'] === $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="source" :value="__('Source')" />
                        <select id="source" name="source" class="{{ $selectClasses }}">
                            <option value="">{{ __('Any') }}</option>
                            @foreach (AtRiskQuery::SOURCES as $key => $label)
                                <option value="{{ $key }}" @selected($filters['source'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="intake" :value="__('Intake status')" />
                        <select id="intake" name="intake" class="{{ $selectClasses }}">
                            <option value="">{{ __('Any') }}</option>
                            @foreach (AtRiskQuery::INTAKE_FILTERS as $key => $label)
                                <option value="{{ $key }}" @selected($filters['intake'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="follow_up" :value="__('Follow-up status')" />
                        <select id="follow_up" name="follow_up" class="{{ $selectClasses }}">
                            <option value="">{{ __('Any') }}</option>
                            @foreach (FollowUpStatus::options() as $value => $label)
                                <option value="{{ $value }}" @selected($filters['follow_up'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Concerns') }}</span>
                        <details class="group mt-1 rounded-md border border-gray-300 dark:border-white/10 bg-white/50 dark:bg-slate-800/50" @if ($filters['concerns']) open @endif>
                            <summary class="cursor-pointer select-none px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                {{ $filters['concerns'] ? trans_choice(':count concern selected|:count concerns selected', count($filters['concerns'])) : __('Any concern') }}
                            </summary>
                            <fieldset class="grid gap-x-4 gap-y-1 border-t border-gray-200 dark:border-white/10 px-3 py-2 sm:grid-cols-2">
                                <legend class="sr-only">{{ __('Concerns') }}</legend>
                                @foreach (Form8::CONCERNS as $key => $label)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" name="concerns[]" value="{{ $key }}" @checked(in_array($key, $filters['concerns'], true))
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </fieldset>
                        </details>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 pb-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="urgent" value="1" @checked($filters['urgent'])
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900">
                            {{ __('Urgent only') }}
                        </label>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <x-primary-button>{{ __('Apply filters') }}</x-primary-button>
                    @if ($query->hasFilters())
                        <a href="{{ route('director.at-risk.index') }}" class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100">{{ __('Clear filters') }}</a>
                    @endif
                    <p class="ms-auto text-sm text-gray-600 dark:text-gray-400" data-result-count>
                        {{ trans_choice(':count student|:count students', $students->total()) }}
                    </p>
                </div>
            </form>

            {{-- Results --}}
            <div class="glass-panel sm:rounded-lg overflow-hidden">
                @if ($students->isEmpty())
                    <p class="p-6 text-sm text-gray-600 dark:text-gray-400">{{ __('No students match these filters.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/40">
                                <tr>
                                    @foreach ([__('Student'), __('Program / College'), __('Year / Type'), __('Concerns'), __('Source'), __('Intake'), __('Latest'), __('Follow-up')] as $heading)
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $heading }}</th>
                                    @endforeach
                                    <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($students as $row)
                                    @php
                                        $student = $row->student;
                                        $fullName = trim("{$student->last_name}, {$student->first_name} {$student->middle_name}");
                                    @endphp
                                    <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-700/60" data-at-risk-row="{{ $student->id }}">
                                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                            @if ($row->record)
                                                <a href="{{ route('staff.records.show', $row->record) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ $fullName }}</a>
                                            @else
                                                <span class="font-medium">{{ $fullName }}</span>
                                            @endif
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $student->student_number }}</div>
                                            @if ($row->urgent)
                                                <span class="{{ $badge }} mt-1 bg-red-50 font-semibold text-red-700 ring-red-600/30 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-400/40" data-urgent>{{ __('Urgent') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                            {{ $student->program?->code }}
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $student->program?->college?->code }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                            {{ Form5::YEAR_LEVELS[$student->year_level] ?? '—' }}
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ Form5::STUDENT_TYPES[$row->student_type] ?? '—' }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex max-w-xs flex-wrap gap-1">
                                                @foreach ($row->concerns as $concern)
                                                    <span @class([$badge, 'bg-gray-100 text-gray-700 ring-gray-500/20 dark:bg-white/10 dark:text-gray-200 dark:ring-white/20' => ! in_array($concern, Form8::followUpConcerns(), true), 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-400/30' => in_array($concern, Form8::followUpConcerns(), true)])>{{ Form8::CONCERNS[$concern] }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3"><span class="{{ $badge }} {{ $sourceClasses[$row->source] }}">{{ AtRiskQuery::SOURCES[$row->source] }}</span></td>
                                        <td class="px-4 py-3"><x-intake-status :status="$row->intake_status" /></td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $localDate($row->latest_date) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="{{ $badge }} {{ $row->follow_up_status->badgeClasses() }}">{{ $row->follow_up_status->label() }}</span>
                                            @if ($row->follow_up)
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                    {{ $localDate($row->follow_up->updated_at, 'M j, Y g:i A') }} · {{ $row->follow_up->updater?->name ?? __('(removed user)') }}
                                                </div>
                                            @endif
                                            <details class="mt-2">
                                                <summary class="cursor-pointer text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('Update') }}</summary>
                                                <form method="POST" action="{{ route('director.at-risk.follow-up', $student) }}" class="mt-2 w-60 space-y-2"
                                                    data-confirm="{{ __('Save the follow-up status for :name?', ['name' => $student->name]) }}"
                                                    data-confirm-title="{{ __('Update follow-up?') }}" data-confirm-button="{{ __('Save') }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label for="status-{{ $student->id }}" class="sr-only">{{ __('Follow-up status') }}</label>
                                                    <select id="status-{{ $student->id }}" name="status" class="{{ $selectClasses }} mt-0">
                                                        @foreach (FollowUpStatus::cases() as $status)
                                                            <option value="{{ $status->value }}" @selected($row->follow_up_status === $status)>{{ $status->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                    <label for="note-{{ $student->id }}" class="block text-xs text-gray-600 dark:text-gray-400">{{ __('Private note (optional)') }}</label>
                                                    <textarea id="note-{{ $student->id }}" name="note" rows="2" maxlength="2000"
                                                        class="block w-full glass-input rounded-md text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $row->follow_up?->note }}</textarea>
                                                    <x-primary-button class="text-xs">{{ __('Save') }}</x-primary-button>
                                                </form>
                                            </details>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <a href="{{ route('staff.intake.create', $student) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('New intake interview') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if ($students->hasPages())
                <div>{{ $students->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
