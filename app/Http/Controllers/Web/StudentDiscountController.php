<?php

namespace App\Http\Controllers\Web;

use App\Actions\Discounts\ApplyStudentDiscount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreStudentDiscountRequest;
use App\Models\FeeCategory;
use App\Models\Student;

class StudentDiscountController extends Controller
{
    public function create(Student $student)
    {
        $student->load('discounts.feeCategory');

        return view('students.discounts.create', [
            'student' => $student,
            'feeCategories' => FeeCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreStudentDiscountRequest $request, Student $student, ApplyStudentDiscount $applyStudentDiscount)
    {
        $applyStudentDiscount->handle(
            $student,
            FeeCategory::query()->findOrFail($request->integer('fee_category_id')),
            [
                'type' => $request->string('type')->toString(),
                'value' => $request->input('value'),
                'value_type' => $request->input('value_type'),
                'is_active' => $request->boolean('is_active'),
                'starts_on' => $request->input('starts_on'),
                'ends_on' => $request->input('ends_on'),
            ],
            $request->user(),
        );

        return redirect()
            ->route('families.show', $student->family_id)
            ->with('status', 'Discount applied.');
    }
}
