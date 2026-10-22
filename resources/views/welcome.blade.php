<x-guest-layout>
    <div class="text-center">
        <h1 class="text-xl font-semibold text-pink-950 dark:text-white">{{ __('Student Cumulative Record System') }}</h1>
        <p class="mt-2 text-sm text-pink-950 dark:text-pink-50">{{ __('Santiago City') }}</p>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary inline-flex justify-center items-center px-4 py-2 border border-transparent font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2">
                    {{ __('Go to dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-primary inline-flex justify-center items-center px-4 py-2 border border-transparent font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2">
                    {{ __('Log in') }}
                </a>
                <a href="{{ route('register') }}" class="btn-secondary inline-flex justify-center items-center px-4 py-2 font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2">
                    {{ __('Register as a student') }}
                </a>
            @endauth
        </div>
    </div>
</x-guest-layout>
