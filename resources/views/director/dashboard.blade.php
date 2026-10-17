<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Director Dashboard') }}
        </h2>
    </x-slot>

    <x-welcome-card>
        {{ __('You have access to all colleges.') }}
    </x-welcome-card>

    <x-staff-stats :stats="$stats" />

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Overall analysis: completion rate and SATA encoding progress --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="glass-panel sm:rounded-lg p-5">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Overall completion rate') }}</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $completionRate }}%</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Submitted records out of all students across every college.') }}</p>
                </div>
                <div class="glass-panel sm:rounded-lg p-5">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Students without a SATA result') }}</p>
                    <p class="mt-1 text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($withoutSata) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Entrance test results still to be encoded.') }}</p>
                </div>
            </div>

            {{-- Records by college and year level (all colleges) --}}
            <section class="glass-panel sm:rounded-lg overflow-hidden" aria-labelledby="director-counts-heading">
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <h3 id="director-counts-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Records by college and year level') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Overall figures across all colleges.') }}</p>
                </div>

                @if ($colleges->isEmpty())
                    <p class="p-6 text-sm text-gray-600 dark:text-gray-400">{{ __('No colleges have been set up yet.') }}</p>
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
                                                {{ App\Support\Form5::YEAR_LEVELS[$row['year_level']] ?? __('No year level') }}
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

            {{-- Export actions --}}
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('director.export.analysis') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-100 dark:text-gray-900 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white">
                    {{ __('Export analysis (CSV)') }}
                </a>
                <a href="{{ route('director.export.logs') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700/60">
                    {{ __('Export activity log (CSV)') }}
                </a>
            </div>

            {{-- User activity log --}}
            <section class="glass-panel sm:rounded-lg overflow-hidden" aria-labelledby="log-heading">
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <h3 id="log-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('User activity log') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Most recent actions across the system.') }}</p>
                </div>

                <div x-data="realtimeTable(window.location.href)" data-realtime-wrapper="director-logs-table">
                @if ($logs->isEmpty())
                    <p class="p-6 text-sm text-gray-600 dark:text-gray-400">{{ __('No activity recorded yet.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/40">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('When') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('User') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Action') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('IP') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($logs as $log)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap tabular-nums text-gray-700 dark:text-gray-300">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $log->user?->name ?? __('—') }}</td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">{{ $log->action }}</span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $log->description ?? __('—') }}</td>
                                        <td class="px-4 py-2 tabular-nums text-gray-500 dark:text-gray-400">{{ $log->ip ?? __('—') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                        {{ $logs->links() }}
                    </div>
                @endif
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
