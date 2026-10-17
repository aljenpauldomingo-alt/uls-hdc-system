<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Appointment Details') }}
            </h2>
            <a href="{{ route('appointments.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                &larr; {{ __('Back to Appointments') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="glass-panel sm:rounded-lg p-6 sm:p-8 space-y-8">

                <!-- Status Banner -->
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Appointment #{{ $appointment->id }}
                    </h3>
                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm font-medium capitalize text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        {{ $appointment->status }}
                    </span>
                </div>

                <!-- Main Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Student') }}</span>
                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $appointment->student->name }}</p>
                        @if ($appointment->student->program)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $appointment->student->program->code }} · {{ $appointment->student->program->college->name }}</p>
                        @endif
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('HDC Staff Member') }}</span>
                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">{{ $appointment->staff->name }}</p>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Date and Time') }}</span>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $appointment->scheduled_at->format('F j, Y \a\t g:i A') }}</p>
                    </div>
                </div>

                <!-- Detailed Sections -->
                <div class="space-y-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Reason for Appointment') }}</span>
                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $appointment->purpose }}</p>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Additional Details') }}</span>
                        <div class="mt-2 prose prose-sm max-w-none text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/40 p-4 rounded-md border border-gray-100 dark:border-gray-800">
                            {!! nl2br(e($appointment->notes ?: __('No additional details provided.'))) !!}
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                @if (! Auth::user()->isStudent())
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6 flex justify-end">
                        <form method="POST" action="{{ route('appointments.status', $appointment) }}" class="flex items-center gap-3">
                            @csrf
                            @method('PATCH')
                            <label for="status" class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Update Status:') }}</label>
                            <select name="status" id="status" class="glass-input rounded-md text-sm">
                                @foreach (['pending' => __('Pending'), 'approved' => __('Approved'), 'completed' => __('Completed'), 'rejected' => __('Rejected')] as $status => $label)
                                    <option value="{{ $status }}" @selected($appointment->status === $status)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                        </form>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>