<?php

namespace App\Providers;

use App\Models\AccessLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers());

        Event::listen(Login::class, function (Login $event): void {
            AccessLog::logAction($event->user->id, AccessLog::ACTION_LOGIN, 'Logged in', request()->ip());
        });
    }
}
