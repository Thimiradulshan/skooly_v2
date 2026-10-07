<?php

namespace App\Http\Controllers\Web;

use App\Academic\ActiveAcademicYear;
use App\Actions\Fees\CreateFeeStructure;
use App\Actions\Fees\UpdateFeeStructure;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StoreFeeStructureRequest;
use App\Http\Requests\Web\UpdateFeeStructureRequest;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use RuntimeException;

class FeeStructureController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortOptions = ['amount' => 'amount', 'frequency' => 'frequency'];
        $sort = $request->sort($sortOptions);
        $direction = $request->direction('asc');

        $feeStructures = FeeStructure::query()
            ->with(['feeCategory', 'grade', 'academicYear'])
            ->withCount('studentDueItems')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('feeCategory', fn ($feeCategory) => $feeCategory->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('grade', fn ($grade) => $grade->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('academicYear', fn ($academicYear) => $academicYear->where('name', 'like', "%{$search}%"));
                });
            });

        if ($sort !== null) {
            $feeStructures->orderBy($sortOptions[$sort], $direction);
        }

        return view('fee-structures.index', [
            'feeStructures' => $feeStructures
                ->orderBy('id')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(ActiveAcademicYear $activeAcademicYear)
    {
        return view('fee-structures.create', [
            'feeCategories' => FeeCategory::query()->orderBy('name')->get(),
            'grades' => Grade::query()->active()->orderBy('sequence_order')->get(),
            'academicYears' => collect([$activeAcademicYear->current()]),
        ]);
    }

    public function store(StoreFeeStructureRequest $request, CreateFeeStructure $createFeeStructure)
    {
        $createFeeStructure->handle(
            FeeCategory::query()->findOrFail($request->integer('fee_category_id')),
            Grade::query()->active()->findOrFail($request->integer('grade_id')),
            AcademicYear::query()->findOrFail($request->integer('academic_year_id')),
            (string) $request->input('amount'),
            $request->string('frequency')->toString(),
        );

        return redirect()
            ->route('fee-structures.index')
            ->with('status', 'Fee structure created.');
    }

    public function edit(FeeStructure $feeStructure)
    {
        $feeStructure->load(['feeCategory', 'grade', 'academicYear']);

        return view('fee-structures.edit', [
            'feeStructure' => $feeStructure,
            'isLocked' => $feeStructure->studentDueItems()->exists(),
        ]);
    }

    public function update(
        UpdateFeeStructureRequest $request,
        FeeStructure $feeStructure,
        UpdateFeeStructure $updateFeeStructure,
    ) {
        try {
            $updateFeeStructure->handle(
                $feeStructure,
                (string) $request->input('amount'),
                $request->string('frequency')->toString(),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['fee_structure' => $exception->getMessage()]);
        }

        return redirect()
            ->route('fee-structures.index')
            ->with('status', 'Fee structure updated. Existing due item snapshots were not changed.');
    }
}
