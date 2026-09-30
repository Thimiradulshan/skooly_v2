<?php

namespace App\Http\Controllers\Web;

use App\Actions\Fees\CreateStudentFeeSubscription;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreStudentFeeSubscriptionRequest;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\Student;

class StudentFeeSubscriptionController extends Controller
{
    public function create(Student $student)
    {
        $student->load('studentFeeSubscriptions.feeCategory');

        return view('students.fee-subscriptions.create', [
            'student' => $student,
            'feeCategories' => FeeCategory::query()->where('is_opt_in', true)->orderBy('name')->get(),
            'academicYears' => AcademicYear::query()->orderBy('id')->get(),
        ]);
    }

    public function store(StoreStudentFeeSubscriptionRequest $request, Student $student, CreateStudentFeeSubscription $createSubscription)
    {
        $createSubscription->handle(
            $student,
            FeeCategory::query()->findOrFail($request->integer('fee_category_id')),
            AcademicYear::query()->findOrFail($request->integer('academic_year_id')),
            [
                'is_active' => $request->boolean('is_active'),
                'starts_on' => $request->input('starts_on'),
                'ends_on' => $request->input('ends_on'),
            ],
        );

        return redirect()
            ->route('families.show', $student->family_id)
            ->with('status', 'Fee subscription created.');
    }
}
