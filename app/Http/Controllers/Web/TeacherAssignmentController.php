<?php

namespace App\Http\Controllers\Web;

use App\Academic\ActiveAcademicYear;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StoreTeacherAssignmentRequest;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Teachers\CreateTeacherAssignment;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class TeacherAssignmentController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortOptions = ['academic_year' => 'academic_year_id', 'section' => 'section_id', 'teacher' => 'teacher_id', 'subject' => 'subject_id'];
        $sort = $request->sort($sortOptions);
        $direction = $request->direction('desc');

        $assignments = TeacherAssignment::query()
            ->with(['academicYear', 'section.grade', 'teacher', 'subject'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('academicYear', fn ($academicYear) => $academicYear->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('section', fn ($section) => $section->where('name', 'like', "%{$search}%")->orWhereHas('grade', fn ($grade) => $grade->where('name', 'like', "%{$search}%")))
                        ->orWhereHas('teacher', fn ($teacher) => $teacher->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('subject', fn ($subject) => $subject->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
                });
            });

        if ($sort === null) {
            $assignments->orderByDesc('academic_year_id')->orderBy('section_id');
        } else {
            $assignments->orderBy($sortOptions[$sort], $direction);
        }

        return view('teacher-assignments.index', [
            'assignments' => $assignments
                ->orderBy('id')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(ActiveAcademicYear $activeAcademicYear)
    {
        return view('teacher-assignments.create', [
            'academicYears' => collect([$activeAcademicYear->current()]),
            'sections' => Section::query()->active()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
            'teachers' => $this->teachers()->get(),
            'subjects' => Subject::query()->orderBy('code')->get(),
        ]);
    }

    public function store(StoreTeacherAssignmentRequest $request, CreateTeacherAssignment $createTeacherAssignment)
    {
        try {
            $assignment = $createTeacherAssignment->handle(
                AcademicYear::query()->findOrFail($request->integer('academic_year_id')),
                Section::query()->findOrFail($request->integer('section_id')),
                User::query()->findOrFail($request->integer('teacher_id')),
                Subject::query()->findOrFail($request->integer('subject_id')),
            );
        } catch (InvalidArgumentException $exception) {
            $field = $exception->getMessage() === 'The selected teacher is not qualified for this subject.'
                ? 'subject_id'
                : 'teacher_id';

            return back()->withErrors([$field => $exception->getMessage()]);
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
