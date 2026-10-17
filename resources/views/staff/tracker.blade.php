@php
    use App\Support\Form5;
    use App\Support\StudentRecordQuery;

    $statusLabels = StudentRecordQuery::STATUSES;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Submission Tracker') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('staff.partials.filters', [
                'action' => route('staff.tracker'),
                'statuses' => array_intersect_key($statusLabels, array_flip([StudentRecordQuery::NOT_STARTED, 'draft'])),
            ])

            <div x-data="realtimeTable(window.location.href)" data-realtime-wrapper="staff-tracker-wrapper">
            <div class="glass-panel sm:rounded-lg p-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($withoutSata) }}</span>
                    {{ trans_choice('student without a SATA result|students without a SATA result', $withoutSata) }}
                </p>
                @if ($withoutSata)
                    <a href="{{ route('staff.records.index', array_filter([...$query->filters(), 'status' => null, 'sata' => 'not_encoded'])) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('Show them and start encoding') }} &rarr;</a>
                @endif
            </div>

            {{-- Counts per college and year level --}}
            <section class="glass-panel sm:rounded-lg overflow-hidden" aria-labelledby="counts-heading">
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <h3 id="counts-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Records by college and year level') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Status and SATA filters do not apply to these counts.') }}</p>
                </div>

                @if ($colleges->isEmpty())
                    <p class="p-6 text-sm text-gray-600 dark:text-gray-400">{{ __('You are not assigned to any college yet.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/40">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('College / Year level') }}</th>
                                    @foreach ($statusLabels as $label)
                                        <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</th>
                                    @endforeach
                                    <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($colleges as $group)
                                    <tr class="bg-gray-50 dark:bg-gray-900/40">
                                        <th scope="row" class="px-4 py-2 text-left font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $group['college']->code }} – {{ $group['college']->name }}
                                        </th>
                                        @foreach (array_keys($statusLabels) as $status)
                                            <td class="px-4 py-2 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $group['totals'][$status] }}</td>
                                        @endforeach
                                        <td class="px-4 py-2 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $group['totals']['total'] }}</td>
                                    </tr>
                                    @forelse ($group['rows'] as $row)
                                        <tr>
                                            <th scope="row" class="px-4 py-2 pl-8 text-left font-normal text-gray-700 dark:text-gray-300">
                                                {{ Form5::YEAR_LEVELS[$row['year_level']] ?? __('No year level') }}
                                            </th>
                                            @foreach (array_keys($statusLabels) as $status)
                                                <td class="px-4 py-2 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $row['counts'][$status] }}</td>
                                            @endforeach
                                            <td class="px-4 py-2 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $row['counts']['total'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($statusLabels) + 2 }}" class="px-4 py-2 pl-8 text-gray-500 dark:text-gray-400">{{ __('No students.') }}</td>
                                        </tr>
                                    @endforelse
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-gray-300 dark:border-gray-600">
                                <tr>
                                    <th scope="row" class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">{{ __('All') }}</th>
                                    @foreach (array_keys($statusLabels) as $status)
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $grandTotals[$status] }}</td>
                                    @endforeach
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $grandTotals['total'] }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </section>

            {{-- Students who have not submitted --}}
            <section aria-labelledby="missing-heading" class="space-y-3">
                <div class="glass-panel sm:rounded-lg px-4 py-3 flex flex-wrap items-baseline justify-between gap-2">
                    <h3 id="missing-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Not yet submitted') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ trans_choice(':count student|:count students', $students->total()) }}</p>
                </div>

                @include('staff.partials.student-table', ['emptyMessage' => __('Everyone in this selection has submitted.')])
            </section>
        </div>
    </div>
</x-app-layout>
