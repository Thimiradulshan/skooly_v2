<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StoreSectionYearAssignmentRequest;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionYearAssignment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class SectionYearAssignmentController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();

        return view('section-year-assignments.index', [
            'assignments' => SectionYearAssignment::query()
                ->with(['academicYear', 'section.grade', 'classInCharge'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->whereHas('academicYear', fn ($academicYear) => $academicYear->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('section', fn ($section) => $section->where('name', 'like', "%{$search}%")->orWhereHas('grade', fn ($grade) => $grade->where('name', 'like', "%{$search}%")))
                            ->orWhereHas('classInCharge', fn ($teacher) => $teacher->where('name', 'like', "%{$search}%"));
                    });
                })
                ->orderByDesc('academic_year_id')
                ->orderBy('section_id')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('section-year-assignments.create', [
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'sections' => Section::query()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
            'teachers' => $this->teachers()->get(),
        ]);
    }

    public function store(StoreSectionYearAssignmentRequest $request)
    {
        $teacher = User::query()->findOrFail($request->integer('class_in_charge_id'));

        if (! $teacher->hasRole(Role::TEACHER)) {
            throw ValidationException::withMessages(['class_in_charge_id' => 'The selected user must have the Teacher role.']);
        }

        try {
            $assignment = SectionYearAssignment::query()->create($request->validated());
        } catch (QueryException) {
            throw ValidationException::withMessages(['section_id' => 'This section already has a class-in-charge for the selected academic year.']);
        }

        return redirect()->route('section-year-assignments.show', $assignment)->with('status', 'Class-in-charge assignment created.');
    }

    public function show(SectionYearAssignment $sectionYearAssignment)
    {
        $sectionYearAssignment->load(['academicYear', 'section.grade', 'classInCharge']);

        return view('section-year-assignments.show', ['sectionYearAssignment' => $sectionYearAssignment]);
    }

    private function teachers()
    {
        return User::query()->where('is_active', true)->whereHas('roles', fn ($roles) => $roles->where('name', Role::TEACHER))->orderBy('name');
    }
}
