<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreEnrollmentPlacementRequest;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use Illuminate\Validation\ValidationException;

class EnrollmentPlacementController extends Controller
{
    public function create(Enrollment $enrollment)
    {
        $enrollment->load(['student', 'academicYear', 'grade', 'section']);

        return view('enrollments.placements.create', [
            'enrollment' => $enrollment,
            'grades' => Grade::query()->with('sections')->orderBy('sequence_order')->get(),
        ]);
    }

    public function store(StoreEnrollmentPlacementRequest $request, Enrollment $enrollment)
    {
        $grade = Grade::query()->findOrFail($request->integer('grade_id'));
        $section = Section::query()->findOrFail($request->integer('section_id'));

        if ($section->grade_id !== $grade->id) {
            throw ValidationException::withMessages(['section_id' => 'The selected section must belong to the selected grade.']);
        }

        $enrollment->placeIn($grade, $section);

        return redirect()->route('students.show', $enrollment->student_id)->with('status', 'Enrollment placement recorded.');
    }
}
