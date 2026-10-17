<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Http\Requests\Director\ProgramRequest;
use App\Models\AccessLog;
use App\Models\College;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function store(ProgramRequest $request): RedirectResponse
    {
        $program = Program::create($request->validated());

        AccessLog::logAction($request->user()->id, AccessLog::ACTION_MANAGE, "Created program: {$program->code}", $request->ip());

        return redirect()
            ->route('director.colleges.edit', $program->college_id)
            ->with('status', "Program {$program->code} added.");
    }

    public function edit(Program $program): View
    {
        return view('director.programs.form', [
            'program' => $program->loadCount('students'),
            'colleges' => College::orderBy('name')->get(),
        ]);
    }

    public function update(ProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->validated());

        AccessLog::logAction($request->user()->id, AccessLog::ACTION_MANAGE, "Updated program: {$program->code}", $request->ip());

        return redirect()
            ->route('director.colleges.edit', $program->college_id)
            ->with('status', "Saved program {$program->code}.");
    }

    public function destroy(Program $program): RedirectResponse
    {
        if ($program->students()->exists()) {
            return back()->with('error', "{$program->code} has enrolled students, so it cannot be deleted.");
        }

        $collegeId = $program->college_id;
        $code = $program->code;
        $program->delete();

        AccessLog::logAction(request()->user()->id, AccessLog::ACTION_MANAGE, "Deleted program: {$code}", request()->ip());

        return redirect()
            ->route('director.colleges.edit', $collegeId)
            ->with('status', "Program {$code} deleted.");
    }
}
