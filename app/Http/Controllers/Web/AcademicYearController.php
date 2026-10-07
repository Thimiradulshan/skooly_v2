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

        return view('academic-years.index', [
            'academicYears' => AcademicYear::query()
                ->withCount('terms')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderByDesc('start_date')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
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
