<?php

namespace App\Http\Controllers\Director;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Director\CounselorRequest;
use App\Models\AccessLog;
use App\Models\College;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Director-only management of counselor accounts. No hard delete: counselors are deactivated.
 */
class CounselorController extends Controller
{
    public function index(): View
    {
        return view('director.counselors.index', [
            'counselors' => User::query()
                ->where('role', Role::Counselor)
                ->with(['colleges' => fn ($query) => $query->orderBy('code')])
                ->orderByDesc('is_active')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('director.counselors.form', [
            'counselor' => new User,
            'colleges' => College::orderBy('name')->get(),
        ]);
    }

    public function store(CounselorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $counselor = DB::transaction(function () use ($validated): User {
            $counselor = new User([
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            // Never taken from the request.
            $counselor->role = Role::Counselor;
            $counselor->is_active = true;
            $counselor->must_change_password = true;
            $counselor->email_verified_at = now();
            $counselor->save();

            $counselor->colleges()->sync($validated['colleges']);

            return $counselor;
        });

        AccessLog::logAction($request->user()->id, AccessLog::ACTION_MANAGE, "Created counselor account: {$counselor->name}", $request->ip());

        return redirect()
            ->route('director.counselors.index')
            ->with('status', "Counselor account for {$counselor->name} created. They must change the temporary password on first login.");
    }

    public function edit(User $counselor): View
    {
        $this->ensureCounselor($counselor);

        return view('director.counselors.form', [
            'counselor' => $counselor->load('colleges'),
            'colleges' => College::orderBy('name')->get(),
        ]);
    }

    public function update(CounselorRequest $request, User $counselor): RedirectResponse
    {
        $this->ensureCounselor($counselor);
        $validated = $request->validated();

        DB::transaction(function () use ($counselor, $validated): void {
            $counselor->fill([
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'email' => $validated['email'],
            ]);

            if (filled($validated['password'] ?? null)) {
                $counselor->password = $validated['password'];
                $counselor->must_change_password = true;
            }

            $counselor->save();
            $counselor->colleges()->sync($validated['colleges']);
        });

        AccessLog::logAction($request->user()->id, AccessLog::ACTION_MANAGE, "Updated counselor account: {$counselor->name}", $request->ip());

        return redirect()
            ->route('director.counselors.index')
            ->with('status', filled($validated['password'] ?? null)
                ? "Saved. {$counselor->name} must change the new temporary password on next login."
                : "Saved changes to {$counselor->name}.");
    }

    public function deactivate(User $counselor): RedirectResponse
    {
        return $this->setActive($counselor, false);
    }

    public function reactivate(User $counselor): RedirectResponse
    {
        return $this->setActive($counselor, true);
    }

    private function setActive(User $counselor, bool $active): RedirectResponse
    {
        $this->ensureCounselor($counselor);

        $counselor->is_active = $active;
        $counselor->save();

        AccessLog::logAction(request()->user()->id, AccessLog::ACTION_MANAGE, ($active ? 'Reactivated' : 'Deactivated')." counselor: {$counselor->name}", request()->ip());

        return redirect()
            ->route('director.counselors.index')
            ->with('status', $active
                ? "{$counselor->name} can log in again."
                : "{$counselor->name} has been deactivated and can no longer log in.");
    }

    private function ensureCounselor(User $user): void
    {
        abort_unless($user->isCounselor(), 404);
    }
}
