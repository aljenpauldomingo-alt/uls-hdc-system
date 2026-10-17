@php
    $user = Auth::user();
    $isStaff = $user->isCounselor() || $user->isDirector();
    $unreadCount = $user->unreadNotifications()->count();
@endphp

<nav x-data="{ open: false }"
    data-realtime-user-id="{{ $user->id }}"
    class="glass-bar sticky top-0 z-40">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route($user->dashboardRoute()) }}" class="flex items-center gap-2">
                        <x-application-logo class="block shrink-0" />
                        <span class="font-semibold text-gray-800 dark:text-gray-100">ULS HDC</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 lg:-my-px lg:ms-8 lg:flex">
                    <x-nav-link :href="route($user->dashboardRoute())" :active="request()->routeIs($user->dashboardRoute())">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @if ($user->isStudent())
                        <x-nav-link :href="route('student.record.index')" :active="request()->routeIs('student.record.*')">
                            {{ __('My Record') }}
                        </x-nav-link>
                        <x-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                            {{ __('Appointments') }}
                        </x-nav-link>
                    @endif
                    @if ($isStaff)
                        <x-nav-link :href="route('staff.records.index')" :active="request()->routeIs('staff.records.*')">
                            {{ __('Records') }}
                        </x-nav-link>
                        <x-nav-link :href="route('staff.tracker')" :active="request()->routeIs('staff.tracker')">
                            {{ __('Tracker') }}
                        </x-nav-link>
                        <x-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                            {{ __('Appointments') }}
                        </x-nav-link>
                    @endif
                    @if ($user->isDirector())
                        <x-nav-link :href="route('director.counselors.index')" :active="request()->routeIs('director.counselors.*')">
                            {{ __('Counselors') }}
                        </x-nav-link>
                        <x-nav-link :href="route('director.colleges.index')" :active="request()->routeIs('director.colleges.*', 'director.programs.*')">
                            {{ __('College') }}
                        </x-nav-link>
                    @endif
                    <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                        {{ __('Profile') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- User + Theme + Logout -->
            <div class="hidden lg:flex lg:items-center lg:gap-3 lg:ms-6">
                <x-notification-bell :count="$unreadCount" />

                <div class="text-right leading-tight">
                    <div class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ Str::before($user->name, ' ') }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->role->label() }}</div>
                </div>

                <x-theme-toggle />

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-secondary-button type="submit">{{ __('Log Out') }}</x-secondary-button>
                </form>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center gap-2 lg:hidden">
                <x-notification-bell :count="$unreadCount" />
                <button @click="open = ! open" :aria-expanded="open" aria-label="{{ __('Menu') }}" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-white/60 focus:outline-none focus:bg-white/60 focus:text-gray-500 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-gray-300 dark:focus:bg-white/10">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div data-notification-toast role="status" aria-live="polite"
        class="fixed right-4 top-24 z-50 hidden max-w-sm rounded-lg bg-gray-900 px-4 py-3 text-sm text-white shadow-lg">
        <a href="{{ $isStaff ? route('staff.notifications.index') : route('student.notifications.index') }}" data-notification-toast-link>
            <span data-notification-toast-message></span>
        </a>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden pt-2">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route($user->dashboardRoute())" :active="request()->routeIs($user->dashboardRoute())">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if ($user->isStudent())
                <x-responsive-nav-link :href="route('student.record.index')" :active="request()->routeIs('student.record.*')">
                    {{ __('My Record') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                    {{ __('Appointments') }}
                </x-responsive-nav-link>
            @endif
            @if ($isStaff)
                <x-responsive-nav-link :href="route('staff.records.index')" :active="request()->routeIs('staff.records.*')">
                    {{ __('Records') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('staff.tracker')" :active="request()->routeIs('staff.tracker')">
                    {{ __('Tracker') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                    {{ __('Appointments') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('staff.notifications.index')" :active="request()->routeIs('staff.notifications.*')">
                    {{ __('Notifications') }}
                    <span data-unread-count-text @class(['hidden' => $unreadCount === 0])>({{ $unreadCount }})</span>
                </x-responsive-nav-link>
            @endif
            @if ($user->isDirector())
                <x-responsive-nav-link :href="route('director.counselors.index')" :active="request()->routeIs('director.counselors.*')">
                    {{ __('Counselors') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('director.colleges.index')" :active="request()->routeIs('director.colleges.*', 'director.programs.*')">
                    {{ __('Colleges & Programs') }}
                </x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                {{ __('Profile') }}
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-3 border-t border-gray-200/70 dark:border-white/10">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-100">{{ $user->name }}</div>
                <div class="font-medium text-sm text-gray-500 dark:text-gray-400">{{ $user->email }} · {{ $user->role->label() }}</div>
            </div>

            <div class="mt-3 flex items-center gap-3 px-4">
                <x-theme-toggle />
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <x-secondary-button type="submit" class="w-full justify-center">{{ __('Log Out') }}</x-secondary-button>
                </form>
            </div>
        </div>
    </div>
</nav>
