<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreSectionRequest;
use App\Http\Requests\Web\UpdateSectionRequest;
use App\Models\Grade;
use App\Models\Section;

class SectionController extends Controller
{
    public function index()
    {
        return view('sections.index', [
            'sections' => Section::query()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('sections.create', $this->formData());
    }

    public function store(StoreSectionRequest $request)
    {
        $section = Section::query()->create($request->validated());

        return redirect()->route('sections.show', $section)->with('status', 'Section created.');
    }

    public function show(Section $section)
    {
        $section->load('grade');

        return view('sections.show', ['section' => $section]);
    }

    public function edit(Section $section)
    {
        return view('sections.edit', array_merge(['section' => $section], $this->formData()));
    }

    public function update(UpdateSectionRequest $request, Section $section)
    {
        $section->update($request->validated());

        return redirect()->route('sections.show', $section)->with('status', 'Section updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return ['grades' => Grade::query()->orderBy('sequence_order')->get()];
    }
}
