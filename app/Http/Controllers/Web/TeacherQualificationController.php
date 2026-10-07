<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreTeacherQualificationRequest;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TeacherQualificationController extends Controller
{
    public function index()
    {
        return view('teacher-qualifications.index', [
            'teachers' => $this->teachers()->with('qualifiedSubjects')->get(),
        ]);
    }

    public function create()
    {
        return view('teacher-qualifications.create', [
            'teachers' => $this->teachers()->get(),
            'subjects' => Subject::query()->orderBy('code')->get(),
        ]);
    }

    public function store(StoreTeacherQualificationRequest $request)
    {
        $teacher = User::query()->findOrFail($request->integer('teacher_id'));

        if (! $teacher->hasRole(Role::TEACHER)) {
            throw ValidationException::withMessages(['teacher_id' => 'The selected user must have the Teacher role.']);
        }

        $teacher->qualifiedSubjects()->syncWithoutDetaching([$request->integer('subject_id')]);

        return redirect()->route('teacher-qualifications.index')->with('status', 'Teacher qualification recorded.');
    }

    public function show(User $teacher)
    {
        if (! $teacher->hasRole(Role::TEACHER)) {
            abort(404);
        }

        $teacher->load('qualifiedSubjects');

        return view('teacher-qualifications.show', ['teacher' => $teacher]);
    }

    private function teachers()
    {
        return User::query()->where('is_active', true)->whereHas('roles', fn ($roles) => $roles->where('name', Role::TEACHER))->orderBy('name');
    }
}
