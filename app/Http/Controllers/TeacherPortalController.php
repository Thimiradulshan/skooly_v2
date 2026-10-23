<?php

namespace App\Http\Controllers;

use App\Academic\ActiveAcademicYear;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionYearAssignment;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TeacherPortalController extends Controller
{
    public function index(Request $request, ActiveAcademicYear $activeAcademicYear)
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_active && $user->hasRole(Role::TEACHER), 403);

        try {
            $year = $activeAcademicYear->current();
        } catch (InvalidArgumentException) {
            return view('teacher-portal.dashboard', ['academicYear' => null, 'assignments' => collect(), 'sections' => collect()]);
        }

        return view('teacher-portal.dashboard', [
            'academicYear' => $year,
            'assignments' => TeacherAssignment::query()->with(['section.grade', 'subject'])->where('teacher_id', $user->id)->where('academic_year_id', $year->id)->get(),
            'sections' => SectionYearAssignment::query()->with('section.grade')->where('class_in_charge_id', $user->id)->where('academic_year_id', $year->id)->get(),
        ]);
    }

    public function classes(Request $request, ActiveAcademicYear $activeAcademicYear)
    {
        $user = $this->teacher($request);

        try {
            $year = $activeAcademicYear->current();
        } catch (InvalidArgumentException) {
            return view('teacher-portal.classes', ['academicYear' => null, 'assignments' => collect(), 'classInChargeSections' => collect()]);
        }

        return view('teacher-portal.classes', [
            'academicYear' => $year,
            'assignments' => TeacherAssignment::query()->with(['section.grade', 'subject'])->where('teacher_id', $user->id)->where('academic_year_id', $year->id)->get(),
            'classInChargeSections' => SectionYearAssignment::query()->with('section.grade')->where('class_in_charge_id', $user->id)->where('academic_year_id', $year->id)->get(),
        ]);
    }

    public function subjects(Request $request, ActiveAcademicYear $activeAcademicYear)
    {
        $user = $this->teacher($request);

        try {
            $year = $activeAcademicYear->current();
        } catch (InvalidArgumentException) {
            return view('teacher-portal.subjects', ['academicYear' => null, 'assignments' => collect()]);
        }

        return view('teacher-portal.subjects', [
            'academicYear' => $year,
            'assignments' => TeacherAssignment::query()->with(['section.grade', 'subject'])->where('teacher_id', $user->id)->where('academic_year_id', $year->id)->get(),
        ]);
    }

    public function students(Request $request, ActiveAcademicYear $activeAcademicYear)
    {
        $user = $this->teacher($request);

        try {
            $year = $activeAcademicYear->current();
        } catch (InvalidArgumentException) {
            return view('teacher-portal.students', ['academicYear' => null, 'sections' => collect(), 'enrollments' => collect()]);
        }

        $sectionIds = $this->sectionIds($user, $year->id);
        $query = Enrollment::query()->with(['student', 'grade', 'section'])->where('academic_year_id', $year->id)->whereIn('section_id', $sectionIds);
        if ($request->integer('section_id')) {
            $query->where('section_id', $request->integer('section_id'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('student', fn ($students) => $students->where('name', 'like', "%{$search}%")->orWhere('admission_no', 'like', "%{$search}%"));
        }

        return view('teacher-portal.students', ['academicYear' => $year, 'sections' => Section::query()->with('grade')->whereIn('id', $sectionIds)->get(), 'enrollments' => $query->orderBy('id')->paginate(20)->withQueryString()]);
    }

    public function showStudent(Request $request, Student $student, ActiveAcademicYear $activeAcademicYear)
    {
        $user = $this->teacher($request);

        try {
            $year = $activeAcademicYear->current();
        } catch (InvalidArgumentException) {
            abort(404);
        }

        $enrollment = Enrollment::query()->with(['grade', 'section', 'placements.grade', 'placements.section'])->where('student_id', $student->id)->where('academic_year_id', $year->id)->whereIn('section_id', $this->sectionIds($user, $year->id))->first();
        abort_unless($enrollment !== null, 404);

        return view('teacher-portal.student', compact('student', 'enrollment', 'year'));
    }

    private function sectionIds(User $user, int $academicYearId)
    {
        return TeacherAssignment::query()->where('teacher_id', $user->id)->where('academic_year_id', $academicYearId)->pluck('section_id')
            ->merge(SectionYearAssignment::query()->where('class_in_charge_id', $user->id)->where('academic_year_id', $academicYearId)->pluck('section_id'))->unique();
    }

    private function teacher(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_active && $user->hasRole(Role::TEACHER), 403);

        return $user;
    }
}
