<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreTeacherAssignmentRequest;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class TeacherAssignmentController extends Controller
{
    public function index()
    {
        return view('teacher-assignments.index', [
            'assignments' => TeacherAssignment::query()
                ->with(['academicYear', 'section.grade', 'teacher', 'subject'])
                ->orderByDesc('academic_year_id')
                ->orderBy('section_id')
                ->get(),
        ]);
    }

    public function create()
    {
        return view('teacher-assignments.create', [
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'sections' => Section::query()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
            'teachers' => $this->teachers()->get(),
            'subjects' => Subject::query()->orderBy('code')->get(),
        ]);
    }

    public function store(StoreTeacherAssignmentRequest $request)
    {
        $teacher = User::query()->findOrFail($request->integer('teacher_id'));

        if (! $teacher->hasRole(Role::TEACHER)) {
            throw ValidationException::withMessages(['teacher_id' => 'The selected user must have the Teacher role.']);
        }

        if (! $teacher->qualifiedSubjects()->whereKey($request->integer('subject_id'))->exists()) {
            throw ValidationException::withMessages(['subject_id' => 'The selected teacher is not qualified for this subject.']);
        }

        try {
            $assignment = TeacherAssignment::query()->create($request->validated());
        } catch (QueryException) {
            throw ValidationException::withMessages(['subject_id' => 'This teaching assignment already exists.']);
        }

        return redirect()->route('teacher-assignments.show', $assignment)->with('status', 'Teaching assignment created.');
    }

    public function show(TeacherAssignment $teacherAssignment)
    {
        $teacherAssignment->load(['academicYear', 'section.grade', 'teacher', 'subject']);

        return view('teacher-assignments.show', ['teacherAssignment' => $teacherAssignment]);
    }

    private function teachers()
    {
        return User::query()->where('is_active', true)->whereHas('roles', fn ($roles) => $roles->where('name', Role::TEACHER))->orderBy('name');
    }
}
