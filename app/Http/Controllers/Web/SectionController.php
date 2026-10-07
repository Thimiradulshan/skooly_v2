<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StoreSectionRequest;
use App\Http\Requests\Web\UpdateSectionRequest;
use App\Models\Grade;
use App\Models\Section;

class SectionController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortOptions = ['name' => 'name', 'capacity' => 'capacity'];
        $sort = $request->sort($sortOptions);
        $direction = $request->direction('asc');

        $sections = Section::query()
            ->with('grade')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('grade', fn ($grade) => $grade->where('name', 'like', "%{$search}%"));
            });

        if ($sort === null) {
            $sections->orderBy('grade_id')->orderBy('name');
        } else {
            $sections->orderBy($sortOptions[$sort], $direction);
        }

        return view('sections.index', [
            'sections' => $sections
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
