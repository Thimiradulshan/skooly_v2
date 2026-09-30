<?php

namespace App\Http\Controllers\Web;

use App\Actions\Fees\CreateFeeCategory;
use App\Actions\Fees\UpdateFeeCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreFeeCategoryRequest;
use App\Http\Requests\Web\UpdateFeeCategoryRequest;
use App\Models\FeeCategory;

class FeeCategoryController extends Controller
{
    public function index()
    {
        return view('fee-categories.index', [
            'feeCategories' => FeeCategory::query()->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('fee-categories.create');
    }

    public function store(StoreFeeCategoryRequest $request, CreateFeeCategory $createFeeCategory)
    {
        $feeCategory = $createFeeCategory->handle([
            'name' => $request->string('name')->toString(),
            'is_recurring' => $request->boolean('is_recurring'),
            'is_opt_in' => $request->boolean('is_opt_in'),
        ]);

        return redirect()
            ->route('fee-categories.index')
            ->with('status', 'Fee category created.');
    }

    public function edit(FeeCategory $feeCategory)
    {
        return view('fee-categories.edit', ['feeCategory' => $feeCategory]);
    }

    public function update(UpdateFeeCategoryRequest $request, FeeCategory $feeCategory, UpdateFeeCategory $updateFeeCategory)
    {
        $updateFeeCategory->handle($feeCategory, [
            'name' => $request->string('name')->toString(),
            'is_recurring' => $request->boolean('is_recurring'),
            'is_opt_in' => $request->boolean('is_opt_in'),
        ]);

        return redirect()
            ->route('fee-categories.index')
            ->with('status', 'Fee category updated.');
    }
}
