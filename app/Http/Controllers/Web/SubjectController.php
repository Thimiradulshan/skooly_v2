<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreSubjectRequest;
use App\Http\Requests\Web\UpdateSubjectRequest;
use App\Models\Subject;

class SubjectController extends Controller
{
    public function index()
    {
        return view('subjects.index', [
            'subjects' => Subject::query()
                ->withCount(['qualifiedTeachers', 'teacherAssignments'])
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function create()
    {
        return view('subjects.create');
    }

    public function store(StoreSubjectRequest $request)
    {
        $subject = Subject::query()->create($request->validated());

        return redirect()->route('subjects.show', $subject)->with('status', 'Subject created.');
    }

    public function show(Subject $subject)
    {
        $subject->load(['qualifiedTeachers', 'teacherAssignments.academicYear', 'teacherAssignments.section', 'teacherAssignments.teacher']);

        return view('subjects.show', ['subject' => $subject]);
    }

    public function edit(Subject $subject)
    {
        return view('subjects.edit', ['subject' => $subject]);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject)
    {
        $subject->update($request->validated());

        return redirect()->route('subjects.show', $subject)->with('status', 'Subject updated.');
    }

    public function destroy(Subject $subject)
    {
        if ($subject->teacherAssignments()->exists()) {
            return back()->withErrors(['subject' => 'This subject cannot be deleted while teaching assignments reference it.']);
        }

        $subject->delete();

        return redirect()->route('subjects.index')->with('status', 'Subject deleted.');
    }
}
