{{-- Filter bar for appointment lists. Expects $action (URL). --}}
@php
    $filters = request()->only(['q', 'status', 'date']);
    $selectClasses = 'mt-1 block w-full glass-input focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm dark:focus:border-indigo-400 dark:focus:ring-indigo-400 text-sm';
@endphp

<form method="GET" action="{{ $action }}" class="glass-panel sm:rounded-lg p-4" role="search">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <x-input-label for="q" :value="__('Search')" />
            <x-text-input id="q" name="q" type="search" class="mt-1 block w-full text-sm" :value="$filters['q'] ?? ''" placeholder="{{ __('Student, staff, or reason') }}" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select id="status" name="status" class="{{ $selectClasses }}">
                <option value="">{{ __('Any status') }}</option>
                @foreach (['pending' => __('Pending'), 'approved' => __('Approved'), 'completed' => __('Completed'), 'rejected' => __('Rejected'), 'cancelled' => __('Cancelled')] as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="date" :value="__('Date')" />
            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full text-sm" :value="$filters['date'] ?? ''" />
        </div>
    </div>

    <div class="mt-4 flex items-center gap-3">
        <x-primary-button class="text-xs px-4 py-2">{{ __('Apply filters') }}</x-primary-button>
        @if (array_filter($filters))
            <a href="{{ $action }}" class="text-sm text-gray-600 dark:text-gray-400 underline hover:text-gray-900 dark:hover:text-gray-100">{{ __('Clear') }}</a>
        @endif
    </div>
</form>