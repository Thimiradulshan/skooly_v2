<?php

namespace App\Http\Controllers\Web;

use App\Actions\Fees\CreateFeeStructure;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreFeeStructureRequest;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;

class FeeStructureController extends Controller
{
    public function index()
    {
        return view('fee-structures.index', [
            'feeStructures' => FeeStructure::query()
                ->with(['feeCategory', 'grade', 'academicYear'])
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function create()
    {
        return view('fee-structures.create', [
            'feeCategories' => FeeCategory::query()->orderBy('name')->get(),
            'grades' => Grade::query()->orderBy('sequence_order')->get(),
            'academicYears' => AcademicYear::query()->orderBy('id')->get(),
        ]);
    }

    public function store(StoreFeeStructureRequest $request, CreateFeeStructure $createFeeStructure)
    {
        $createFeeStructure->handle(
            FeeCategory::query()->findOrFail($request->integer('fee_category_id')),
            Grade::query()->findOrFail($request->integer('grade_id')),
            AcademicYear::query()->findOrFail($request->integer('academic_year_id')),
            (string) $request->input('amount'),
            $request->string('frequency')->toString(),
        );

        return redirect()
            ->route('fee-structures.index')
            ->with('status', 'Fee structure created.');
    }
}
