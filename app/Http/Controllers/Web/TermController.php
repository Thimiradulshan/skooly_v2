<?php

namespace App\Http\Controllers\Web;

use App\Actions\Academic\SetAcademicRecordArchived;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ArchiveAcademicRecordRequest;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StoreTermRequest;
use App\Http\Requests\Web\UpdateTermRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Term;

class TermController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortOptions = ['name' => 'name', 'start_date' => 'start_date', 'end_date' => 'end_date'];
        $sort = $request->sort($sortOptions);
        $direction = $request->direction('asc');

        $terms = Term::query()
            ->with('academicYear')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('academicYear', fn ($academicYear) => $academicYear->where('name', 'like', "%{$search}%"));
            });

        if ($sort === null) {
            $terms->orderBy('academic_year_id')->orderBy('start_date');
        } else {
            $terms->orderBy($sortOptions[$sort], $direction);
        }

        return view('terms.index', [
            'terms' => $terms
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
        return view('terms.edit', array_merge(['term' => $term], $this->formData($term)));
    }

    public function update(UpdateTermRequest $request, Term $term)
    {
        $term->update($request->validated());

        return redirect()->route('terms.show', $term)->with('status', 'Term updated.');
    }

    public function archive(ArchiveAcademicRecordRequest $request, Term $term, SetAcademicRecordArchived $setArchived)
    {
        $setArchived->handle($term, true, AuditLog::ACTION_TERM_ARCHIVED, $request->user());

        return redirect()->route('terms.show', $term)->with('status', 'Term archived. Existing history remains available.');
    }

    public function restore(ArchiveAcademicRecordRequest $request, Term $term, SetAcademicRecordArchived $setArchived)
    {
        $setArchived->handle($term, false, AuditLog::ACTION_TERM_RESTORED, $request->user());

        return redirect()->route('terms.show', $term)->with('status', 'Term restored.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Term $term = null): array
    {
        return ['academicYears' => AcademicYear::query()->active()->when($term, fn ($query) => $query->orWhereKey($term->academic_year_id))->orderByDesc('start_date')->get()];
    }
}
