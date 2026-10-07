<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StoreTeacherQualificationRequest;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TeacherQualificationController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortOptions = ['name' => 'name', 'email' => 'email'];
        $sort = $request->sort($sortOptions);
        $direction = $request->direction('asc');

        $teachers = $this->teachers()
            ->with('qualifiedSubjects')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('qualifiedSubjects', fn ($subject) => $subject->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
                });
            });

        if ($sort !== null) {
            $teachers->reorder()->orderBy($sortOptions[$sort], $direction);
        }

        return view('teacher-qualifications.index', [
            'teachers' => $teachers
                ->orderBy('id')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
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
