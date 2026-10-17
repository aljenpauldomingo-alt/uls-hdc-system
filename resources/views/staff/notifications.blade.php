<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Notifications') }}</h2>
            <form method="POST" action="{{ route('staff.notifications.read-all') }}"
                data-mark-all-read
                @class(['hidden' => ! Auth::user()->unreadNotifications()->exists()])>
                @csrf
                <x-secondary-button type="submit">{{ __('Mark all as read') }}</x-secondary-button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <!-- Facebook-style Filter Tabs -->
            <div class="flex items-center gap-2 border-b border-gray-300 dark:border-gray-600 pb-3 mb-4">
                <a href="{{ route('staff.notifications.index', ['filter' => 'all']) }}" 
                   class="rounded-lg px-4 py-2 text-sm font-bold transition-colors {{ $filter === 'all' ? 'bg-hdc-blue text-white shadow-md' : 'bg-white/50 text-gray-700 hover:bg-white dark:bg-slate-800/50 dark:text-gray-300 dark:hover:bg-slate-700' }}">
                    All
                </a>
                <a href="{{ route('staff.notifications.index', ['filter' => 'unread']) }}" 
                   class="rounded-lg px-4 py-2 text-sm font-bold transition-colors {{ $filter === 'unread' ? 'bg-hdc-pink text-white shadow-md' : 'bg-white/50 text-gray-700 hover:bg-white dark:bg-slate-800/50 dark:text-gray-300 dark:hover:bg-slate-700' }}">
                    Unread
                </a>
                <a href="{{ route('staff.notifications.index', ['filter' => 'read']) }}" 
                   class="rounded-lg px-4 py-2 text-sm font-bold transition-colors {{ $filter === 'read' ? 'bg-hdc-blue text-white shadow-md' : 'bg-white/50 text-gray-700 hover:bg-white dark:bg-slate-800/50 dark:text-gray-300 dark:hover:bg-slate-700' }}">
                    Read
                </a>
            </div>

            <div class="glass-panel sm:rounded-lg overflow-hidden"
                data-notification-list
                data-open-url="{{ route('staff.notifications.open', '__notification__') }}">
                @forelse ($notifications as $notification)
                    <a href="{{ route('staff.notifications.open', $notification->id) }}"
                        data-notification-id="{{ $notification->id }}"
                        @class([
                            'flex items-start gap-3 border-b border-gray-100 dark:border-gray-700 px-4 py-4 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700/60',
                            'bg-indigo-50 dark:bg-indigo-500/10' => $notification->unread(),
                        ])>
                        <span @class([
                            'mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full',
                            'bg-indigo-600' => $notification->unread(),
                            'bg-transparent' => $notification->read(),
                        ]) aria-hidden="true"></span>
                        <span class="min-w-0 flex-1">
                            <span @class(['block text-sm text-gray-900 dark:text-gray-100', 'font-semibold' => $notification->unread()])>
                                {{ $notification->data['message'] ?? __('New notification') }}
                            </span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                                {{ $notification->created_at->diffForHumans() }}
                                @if ($notification->unread())
                                    · <span class="font-medium text-indigo-700 dark:text-indigo-300">{{ __('Unread') }}</span>
                                @endif
                            </span>
                        </span>
                    </a>
                @empty
                    <p data-notification-empty-state class="p-6 text-sm text-gray-600 dark:text-gray-400">{{ __('No notifications yet. You will be notified here when a student submits their record.') }}</p>
                @endforelse
            </div>

            @if ($notifications->hasPages())
                <div class="glass-panel sm:rounded-lg px-4 py-3">{{ $notifications->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
