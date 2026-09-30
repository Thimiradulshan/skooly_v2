<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreAcademicYearRequest;
use App\Http\Requests\Web\UpdateAcademicYearRequest;
use App\Models\AcademicYear;

class AcademicYearController extends Controller
{
    public function index()
    {
        return view('academic-years.index', [
            'academicYears' => AcademicYear::query()->withCount('terms')->orderByDesc('start_date')->get(),
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
