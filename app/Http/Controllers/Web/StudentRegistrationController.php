<?php

namespace App\Http\Controllers\Web;

use App\Academic\ActiveAcademicYear;
use App\Actions\Students\RegisterStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\RegisterStudentRequest;
use App\Models\Family;
use App\Models\Grade;
use App\Models\Section;

class StudentRegistrationController extends Controller
{
    public function create(Family $family, ActiveAcademicYear $activeAcademicYear)
    {
        $family->load('guardians');

        return view('students.create', [
            'family' => $family,
            'academicYears' => collect([$activeAcademicYear->current()]),
            'grades' => Grade::query()->active()->orderBy('sequence_order')->get(),
            'sections' => Section::query()->active()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
        ]);
    }

    public function store(RegisterStudentRequest $request, Family $family, RegisterStudent $registerStudent)
    {
        $student = $registerStudent->handle(
            $family,
            [
                'name' => $request->string('name')->toString(),
                'dob' => $request->string('dob')->toString(),
                'gender' => $request->string('gender')->toString(),
                'admission_no' => $request->string('admission_no')->toString(),
                'photo_path' => $request->input('photo_path'),
                'status' => $request->input('status'),
            ],
            array_map('intval', (array) $request->input('guardian_ids', [])),
            $this->enrollment($request),
        );

        return redirect()
            ->route('families.show', $family)
            ->with('status', 'Student registered.');
    }

    /**
     * @return array{academic_year_id: int, grade_id: int, section_id: int}|null
     */
    private function enrollment(RegisterStudentRequest $request): ?array
    {
        if (blank($request->input('academic_year_id'))
            || blank($request->input('grade_id'))
            || blank($request->input('section_id'))) {
            return null;
        }

        return [
            'academic_year_id' => (int) $request->input('academic_year_id'),
            'grade_id' => (int) $request->input('grade_id'),
            'section_id' => (int) $request->input('section_id'),
        ];
    }
}
