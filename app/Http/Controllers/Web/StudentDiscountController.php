<?php

namespace App\Http\Controllers\Web;

use App\Actions\Discounts\ApplyStudentDiscount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DeactivateStudentDiscountRequest;
use App\Http\Requests\Web\StoreStudentDiscountRequest;
use App\Models\Discount;
use App\Models\FeeCategory;
use App\Models\Student;

class StudentDiscountController extends Controller
{
    public function index(Student $student)
    {
        return view('students.discounts.index', [
            'student' => $student,
            'discounts' => $student->discounts()->with('feeCategory')->orderByDesc('id')->paginate(20),
        ]);
    }

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

    public function deactivate(DeactivateStudentDiscountRequest $request, Discount $discount)
    {
        $discount->update(['is_active' => false]);

        return redirect()->route('students.discounts.index', $discount->student_id)->with('status', 'Discount deactivated. Existing due item snapshots were not changed.');
    }
}
