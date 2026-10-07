<?php

namespace App\Http\Controllers\Web;

use App\Actions\Guardians\LinkGuardianToStudent;
use App\Actions\Guardians\UnlinkGuardianFromStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreGuardianStudentLinkRequest;
use App\Http\Requests\Web\UpdateStudentRequest;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function show(Student $student)
    {
        $student->load(['family.guardians', 'guardians', 'enrollments.academicYear', 'enrollments.grade', 'enrollments.section', 'enrollments.placements.grade', 'enrollments.placements.section', 'discounts.feeCategory', 'studentFeeSubscriptions.feeCategory', 'studentFeeSubscriptions.academicYear']);

        return view('students.show', ['student' => $student]);
    }

    public function edit(Student $student)
    {
        return view('students.edit', ['student' => $student]);
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $student->update($request->validated());

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
