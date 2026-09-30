<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreTermRequest;
use App\Http\Requests\Web\UpdateTermRequest;
use App\Models\AcademicYear;
use App\Models\Term;

class TermController extends Controller
{
    public function index()
    {
        return view('terms.index', [
            'terms' => Term::query()->with('academicYear')->orderBy('academic_year_id')->orderBy('start_date')->get(),
        ]);
    }

    public function create()
    {
        return view('terms.create', $this->formData());
    }

    public function store(StoreTermRequest $request)
    {
        $term = Term::query()->create($request->validated());

        return redirect()->route('terms.show', $term)->with('status', 'Term created.');
    }

    public function show(Term $term)
    {
        $term->load('academicYear');

        return view('terms.show', ['term' => $term]);
    }

    public function edit(Term $term)
    {
        return view('terms.edit', array_merge(['term' => $term], $this->formData()));
    }

    public function update(UpdateTermRequest $request, Term $term)
    {
        $term->update($request->validated());

        return redirect()->route('terms.show', $term)->with('status', 'Term updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return ['academicYears' => AcademicYear::query()->orderByDesc('start_date')->get()];
    }
}
