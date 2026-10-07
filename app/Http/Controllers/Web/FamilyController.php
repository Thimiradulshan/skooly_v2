<?php

namespace App\Http\Controllers\Web;

use App\Actions\Families\CreateFamily;
use App\Actions\Families\UpdateFamily;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StoreFamilyRequest;
use App\Http\Requests\Web\UpdateFamilyRequest;
use App\Models\Family;

class FamilyController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortOptions = ['family_code' => 'family_code', 'address' => 'address', 'combined_billing' => 'combined_billing_enabled'];
        $sort = $request->sort($sortOptions);
        $direction = $request->direction('asc');

        $families = Family::query()
            ->withCount(['guardians', 'students'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('family_code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('home_contact_no', 'like', "%{$search}%");
                });
            });

        if ($sort !== null) {
            $families->orderBy($sortOptions[$sort], $direction);
        }

        return view('families.index', [
            'families' => $families
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
        return view('families.create');
    }

    public function store(StoreFamilyRequest $request, CreateFamily $createFamily)
    {
        $family = $createFamily->handle(
            [
                'family_code' => $request->string('family_code')->toString(),
                'address' => $request->input('address'),
                'home_contact_no' => $request->input('home_contact_no'),
                'combined_billing_enabled' => $request->boolean('combined_billing_enabled'),
            ],
            $this->guardians($request),
        );

        return redirect()
            ->route('families.show', $family)
            ->with('status', 'Family created.');
    }

    public function show(Family $family)
    {
        $family->load(['guardians', 'students']);

        return view('families.show', ['family' => $family]);
    }

    public function edit(Family $family)
    {
        return view('families.edit', ['family' => $family]);
    }

    public function update(UpdateFamilyRequest $request, Family $family, UpdateFamily $updateFamily)
    {
        $updateFamily->handle($family, [
            'family_code' => $request->string('family_code')->toString(),
            'address' => $request->input('address'),
            'home_contact_no' => $request->input('home_contact_no'),
            'combined_billing_enabled' => $request->boolean('combined_billing_enabled'),
        ]);

        return redirect()
            ->route('families.show', $family)
            ->with('status', 'Family updated.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function guardians(StoreFamilyRequest $request): array
    {
        return array_values(array_filter(
            $request->input('guardians', []),
            fn ($guardian) => is_array($guardian) && filled($guardian['name'] ?? null),
        ));
    }
}
