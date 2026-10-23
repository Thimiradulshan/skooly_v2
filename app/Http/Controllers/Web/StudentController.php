<?php

namespace App\Http\Controllers\Web;

use App\Actions\Guardians\LinkGuardianToStudent;
use App\Actions\Guardians\UnlinkGuardianFromStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreGuardianStudentLinkRequest;
use App\Http\Requests\Web\StudentDirectoryRequest;
use App\Http\Requests\Web\UpdateStudentRequest;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\PaymentAllocation;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function index(StudentDirectoryRequest $request)
    {
        $filters = $request->validated();
        $search = $request->string('search')->trim()->toString();
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $gradeId = $request->integer('grade_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;
        $status = $request->string('status')->toString();
        $placementAcademicYearId = $academicYearId ?? SchoolSetting::query()->value('active_academic_year_id');

        $students = Student::query()
            ->with([
                'family:id,family_code',
                'guardians:id,name,relationship',
                'enrollments' => function ($query) use ($placementAcademicYearId): void {
                    $query->whereIn('academic_year_id', $placementAcademicYearId === null ? [] : [$placementAcademicYearId])
                        ->with(['academicYear:id,name', 'grade:id,name', 'section:id,name']);
                },
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%")
                        ->orWhereHas('family', fn ($query) => $query->where('family_code', 'like', "%{$search}%"))
                        ->orWhereHas('guardians', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($academicYearId !== null, function ($query) use ($academicYearId, $gradeId, $sectionId): void {
                $query->whereHas('enrollments', function ($query) use ($academicYearId, $gradeId, $sectionId): void {
                    $query->where('academic_year_id', $academicYearId)
                        ->when($gradeId !== null, fn ($query) => $query->where('grade_id', $gradeId))
                        ->when($sectionId !== null, fn ($query) => $query->where('section_id', $sectionId));
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('students.index', [
            'students' => $students,
            'filters' => $filters,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->orderByDesc('id')->get(['id', 'name']),
            'grades' => Grade::query()->orderBy('sequence_order')->orderBy('id')->get(['id', 'name']),
            'sections' => Section::query()->with('grade:id,name')->orderBy('grade_id')->orderBy('name')->get(['id', 'grade_id', 'name']),
            'placementAcademicYear' => $placementAcademicYearId === null
                ? null
                : AcademicYear::query()->find($placementAcademicYearId, ['id', 'name']),
        ]);
    }

    public function show(Student $student)
    {
        $student->load(['family.guardians', 'guardians', 'enrollments.academicYear', 'enrollments.grade', 'enrollments.section', 'enrollments.placements.grade', 'enrollments.placements.section', 'discounts.feeCategory', 'studentFeeSubscriptions.feeCategory', 'studentFeeSubscriptions.academicYear']);

        return view('students.show', ['student' => $student]);
    }

    public function photo(Student $student)
    {
        abort_unless(
            $student->photo_path !== null && Storage::disk('local')->exists($student->photo_path),
            404,
        );

        return Storage::disk('local')->response($student->photo_path, null, [], 'inline');
    }

    public function paymentHistory(Student $student)
    {
        $allocations = PaymentAllocation::query()
            ->whereHas('studentDueItem', fn ($dueItems) => $dueItems->where('student_id', $student->id))
            ->with([
                'payment.receipt',
                'studentDueItem',
                'reversalAllocations.paymentReversal.correctionReceipt',
            ])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('students.payment-history', compact('student', 'allocations'));
    }

    public function edit(Student $student)
    {
        return view('students.edit', ['student' => $student]);
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('student-photos', 'local');
        }

        $student->update($data);

        return redirect()->route('students.show', $student)->with('status', 'Student updated.');
    }

    public function linkGuardian(StoreGuardianStudentLinkRequest $request, Student $student, LinkGuardianToStudent $linkGuardianToStudent)
    {
        $guardian = Guardian::query()->findOrFail($request->integer('guardian_id'));

        try {
            $linked = $linkGuardianToStudent->handle($guardian, $student);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['guardian_id' => $exception->getMessage()]);
        }

        return back()->with('status', $linked ? 'Guardian linked to student.' : 'Guardian is already linked to this student.');
    }

    public function unlinkGuardian(Student $student, Guardian $guardian, UnlinkGuardianFromStudent $unlinkGuardianFromStudent, Request $request)
    {
        try {
            $unlinked = $unlinkGuardianFromStudent->handle($guardian, $student, $request->user());
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['guardian' => $exception->getMessage()]);
        }

        return back()->with('status', $unlinked ? 'Guardian access revoked.' : 'Guardian was not linked to this student.');
    }
}
