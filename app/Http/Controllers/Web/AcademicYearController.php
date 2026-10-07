<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StoreAcademicYearRequest;
use App\Http\Requests\Web\UpdateAcademicYearRequest;
use App\Models\AcademicYear;

class AcademicYearController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortOptions = ['name' => 'name', 'start_date' => 'start_date', 'end_date' => 'end_date'];
        $sort = $request->sort($sortOptions);
        $direction = $request->direction('desc');

        $academicYears = AcademicYear::query()
            ->withCount('terms')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"));

        if ($sort === null) {
            $academicYears->orderByDesc('start_date');
        } else {
            $academicYears->orderBy($sortOptions[$sort], $direction);
        }

        return view('academic-years.index', [
            'academicYears' => $academicYears
                ->orderBy('id')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        return view('academic-years.create');
    }

    public function store(StoreAcademicYearRequest $request)
    {
        $academicYear = AcademicYear::query()->create($request->validated());

        return redirect()->route('academic-years.show', $academicYear)->with('status', 'Academic year created.');
    }

    public function show(AcademicYear $academicYear)
    {
        $academicYear->load('terms');

        return view('academic-years.show', ['academicYear' => $academicYear]);
    }

    public function edit(AcademicYear $academicYear)
    {
        return view('academic-years.edit', ['academicYear' => $academicYear]);
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear)
    {
        $academicYear->update($request->validated());

        return redirect()->route('academic-years.show', $academicYear)->with('status', 'Academic year updated.');
    }
}
