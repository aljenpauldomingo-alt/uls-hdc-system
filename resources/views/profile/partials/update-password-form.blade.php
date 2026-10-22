<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Change Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('After changing it, you will be signed out on your other devices.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6"
        data-confirm="{{ __('Change your password? You will be signed out on your other devices.') }}" data-confirm-button="{{ __('Change password') }}">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-password-input id="update_password_current_password" name="current_password" class="mt-1" :invalid="$errors->updatePassword->has('current_password')" required autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-password-input id="update_password_password" name="password" class="mt-1" :invalid="$errors->updatePassword->has('password')" required minlength="10" autocomplete="new-password" />
            <x-password-rules for="update_password_password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm New Password')" />
            <x-password-input id="update_password_password_confirmation" name="password_confirmation" class="mt-1" :invalid="$errors->updatePassword->has('password_confirmation')" required autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Change password') }}</x-primary-button>
        </div>
    </form>
</section>
