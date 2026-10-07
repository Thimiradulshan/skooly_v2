<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StoreGradeRequest;
use App\Http\Requests\Web\UpdateGradeRequest;
use App\Models\Grade;

class GradeController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();

        return view('grades.index', [
            'grades' => Grade::query()
                ->withCount('sections')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('sequence_order')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create()
    {
        return view('grades.create');
    }

    public function store(StoreGradeRequest $request)
    {
        $grade = Grade::query()->create($request->validated());

        return redirect()->route('grades.show', $grade)->with('status', 'Grade created.');
    }

    public function show(Grade $grade)
    {
        $grade->load('sections');

        return view('grades.show', ['grade' => $grade]);
    }

    public function edit(Grade $grade)
    {
        return view('grades.edit', ['grade' => $grade]);
    }

    public function update(UpdateGradeRequest $request, Grade $grade)
    {
        $grade->update($request->validated());

        return redirect()->route('grades.show', $grade)->with('status', 'Grade updated.');
    }
}
