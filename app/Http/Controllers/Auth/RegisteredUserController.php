<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Program;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Public registration is for STUDENTS ONLY. Staff accounts are created by seeders / the director.
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'colleges' => College::query()
                ->with(['programs' => fn ($query) => $query->orderBy('name')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/', 'unique:'.User::class],
            'program_id' => ['required', 'integer', 'exists:'.Program::class.',id'],
            'year_level' => ['required', 'integer', 'between:1,5'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'username.regex' => 'The username may only contain letters, numbers and dashes.',
        ], [
            'program_id' => 'program',
        ]);

        $user = new User([
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'username' => $validated['username'],
            'program_id' => $validated['program_id'],
            'year_level' => $validated['year_level'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Never taken from the request.
        $user->role = Role::Student;
        $user->save();

        event(new Registered($user));

        Auth::login($user);

        return redirect(route($user->dashboardRoute(), absolute: false));
    }
}
