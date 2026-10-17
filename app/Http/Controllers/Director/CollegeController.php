<?php

namespace App\Http\Controllers\Director;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Director\CollegeRequest;
use App\Models\AccessLog;
use App\Models\College;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Director-only management of colleges (programs are managed on each college's page).
 */
class CollegeController extends Controller
{
    public function index(): View
    {
        return view('director.colleges.index', [
            'colleges' => College::query()
                ->with([
                    'programs' => fn ($query) => $query->withCount('students')->orderBy('code'),
                    'counselors' => fn ($query) => $this->activeCounselors($query),
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('director.colleges.form', ['college' => new College]);
    }

    public function store(CollegeRequest $request): RedirectResponse
    {
        $college = College::create($request->validated());

        AccessLog::logAction($request->user()->id, AccessLog::ACTION_MANAGE, "Created college: {$college->code}", $request->ip());

        return redirect()
            ->route('director.colleges.edit', $college)
            ->with('status', "College {$college->code} created. You can add its programs below.");
    }

    public function edit(College $college): View
    {
        return view('director.colleges.form', [
            'college' => $college->load([
                'programs' => fn ($query) => $query->withCount('students')->orderBy('code'),
                'counselors' => fn ($query) => $this->activeCounselors($query),
            ]),
        ]);
    }

    public function update(CollegeRequest $request, College $college): RedirectResponse
    {
        $college->update($request->validated());

        AccessLog::logAction($request->user()->id, AccessLog::ACTION_MANAGE, "Updated college: {$college->code}", $request->ip());

        return redirect()
            ->route('director.colleges.edit', $college)
            ->with('status', "Saved college {$college->code}.");
    }

    public function destroy(College $college): RedirectResponse
    {
        if ($college->programs()->exists()) {
            return back()->with('error', "{$college->code} still has programs. Move or delete its programs first.");
        }

        $code = $college->code;
        $college->delete();

        AccessLog::logAction(request()->user()->id, AccessLog::ACTION_MANAGE, "Deleted college: {$code}", request()->ip());

        return redirect()
            ->route('director.colleges.index')
            ->with('status', "College {$code} deleted.");
    }

    /**
     * @param  BelongsToMany<User, College>  $query
     */
    private function activeCounselors($query): void
    {
        $query->where('role', Role::Counselor)->where('is_active', true)->orderBy('last_name');
    }
}
