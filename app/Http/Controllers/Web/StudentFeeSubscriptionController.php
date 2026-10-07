<?php

namespace App\Http\Controllers\Web;

use App\Actions\Fees\CreateStudentFeeSubscription;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\EndStudentFeeSubscriptionRequest;
use App\Http\Requests\Web\StoreStudentFeeSubscriptionRequest;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\Student;
use App\Models\StudentFeeSubscription;

class StudentFeeSubscriptionController extends Controller
{
    public function index(Student $student)
    {
        return view('students.fee-subscriptions.index', [
            'student' => $student,
            'subscriptions' => $student->studentFeeSubscriptions()->with(['feeCategory', 'academicYear'])->orderByDesc('id')->get(),
        ]);
    }

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

    public function end(EndStudentFeeSubscriptionRequest $request, StudentFeeSubscription $studentFeeSubscription)
    {
        $studentFeeSubscription->update([
            'is_active' => false,
            'ends_on' => $request->string('ends_on')->toString(),
        ]);

        return redirect()->route('students.fee-subscriptions.index', $studentFeeSubscription->student_id)->with('status', 'Fee subscription ended. Existing due item snapshots were not changed.');
    }
}
