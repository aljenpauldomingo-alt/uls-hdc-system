<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Appointments') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            @if (Auth::user()->isStudent())
                <section class="glass-panel sm:rounded-lg p-6 space-y-5 max-w-2xl mx-auto" aria-labelledby="request-appointment-heading">
                    <div>
                        <h3 id="request-appointment-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('Request an appointment') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Choose an HDC counselor or director and your preferred time. Your request will be sent as pending.') }}
                        </p>
                    </div>

                    @if ($staff->isEmpty())
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('There are no HDC staff members available for your college yet. Please contact the HDC for assistance.') }}
                        </p>
                    @else
                        <form method="POST" action="{{ route('appointments.store') }}" class="space-y-6">
                            @csrf

                            <x-form.select name="staff_id" :label="__('HDC staff member')" :required="true" :placeholder="__('Select a staff member')">
                                @foreach ($staff as $staffMember)
                                    <option value="{{ $staffMember->id }}" @selected((string) old('staff_id') === (string) $staffMember->id)>
                                        {{ $staffMember->name }} ({{ $staffMember->role->label() }})
                                    </option>
                                @endforeach
                            </x-form.select>

                            <x-form.text
                                name="scheduled_at"
                                :label="__('Preferred date and time')"
                                type="datetime-local"
                                :required="true"
                            />

                            <x-form.text
                                name="purpose"
                                :label="__('Reason for appointment')"
                                :required="true"
                                maxlength="255"
                            />

                            <x-form.textarea
                                name="notes"
                                :label="__('Additional details')"
                                :hint="__('Only the HDC staff member handling your appointment can see these details.')"
                                maxlength="2000"
                            />

                            <div class="pt-4">
                                <x-primary-button>{{ __('Send appointment request') }}</x-primary-button>
                            </div>
                        </form>
                    @endif
                </section>

                <section class="glass-panel sm:rounded-lg overflow-hidden" aria-labelledby="my-appointments-heading">
                    <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                        <h3 id="my-appointments-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('My appointment requests') }}
                        </h3>
                    </div>

                    @if ($appointments->isEmpty())
                        <p class="p-6 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('You have not requested an appointment yet.') }}
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/40">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('HDC staff member') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date and time') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Reason') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($appointments as $appointment)
                                        <tr>
                                            <th scope="row" class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100">
                                                {{ $appointment->staff->name }}
                                            </th>
                                            <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">
                                                {{ $appointment->scheduled_at->format('M j, Y g:i A') }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $appointment->purpose }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium capitalize text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                    {{ $appointment->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @else
                <div class="space-y-6">
                    @include('appointments.partials.filters', ['action' => route('appointments.index')])

                    <section class="glass-panel sm:rounded-lg overflow-hidden" aria-labelledby="appointment-requests-heading">
                        <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between gap-4">
                            <h3 id="appointment-requests-heading" class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                {{ __('Appointment requests') }}
                            </h3>
                            <a href="{{ route('appointments.export', request()->only(['status', 'q', 'date'])) }}" class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700/60">
                                {{ __('Export CSV') }}
                            </a>
                        </div>

                        <div x-data="realtimeTable(window.location.href)" data-realtime-wrapper="appointments-table">
                            @if ($appointments->isEmpty())
                                <p class="p-6 text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('There are no appointment requests to display.') }}
                                </p>
                            @else
                                <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Student') }}</th>
                                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('HDC staff member') }}</th>
                                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Date and time') }}</th>
                                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Reason') }}</th>
                                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Additional details') }}</th>
                                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                                            <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">{{ __('Update status') }}</th>
                                            <th scope="col" class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($appointments as $appointment)
                                            <tr>
                                                <th scope="row" class="px-4 py-3 text-left font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $appointment->student->name }}
                                                    @if ($appointment->student->program)
                                                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                                                            {{ $appointment->student->program->code }} · {{ $appointment->student->program->college->name }}
                                                        </span>
                                                    @endif
                                                </th>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $appointment->staff->name }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">
                                                    {{ $appointment->scheduled_at->format('M j, Y g:i A') }}
                                                </td>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $appointment->purpose }}</td>
                                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $appointment->notes ?: '—' }}</td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium capitalize text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                        {{ $appointment->status }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <form method="POST" action="{{ route('appointments.status', $appointment) }}" class="flex items-center gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="status" aria-label="{{ __('Status for :student', ['student' => $appointment->student->name]) }}" class="glass-input rounded-md text-sm">
                                                            @foreach (['pending' => __('Pending'), 'approved' => __('Approved'), 'completed' => __('Completed'), 'rejected' => __('Rejected')] as $status => $label)
                                                                <option value="{{ $status }}" @selected($appointment->status === $status)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                                                    </form>
                                                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <a href="{{ route('appointments.show', $appointment) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">{{ __('View') }}</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>