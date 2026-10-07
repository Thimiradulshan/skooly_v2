<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreGuardianRequest;
use App\Http\Requests\Web\UpdateGuardianRequest;
use App\Models\Family;
use App\Models\Guardian;

class GuardianController extends Controller
{
    public function create(Family $family)
    {
        return view('guardians.create', ['family' => $family]);
    }

    public function store(StoreGuardianRequest $request, Family $family)
    {
        $guardian = $family->guardians()->create($request->validated());

        return redirect()->route('guardians.show', $guardian)->with('status', 'Guardian added. Link this guardian to students explicitly to grant visibility.');
    }

    public function show(Guardian $guardian)
    {
        $guardian->load(['family', 'students']);

        return view('guardians.show', ['guardian' => $guardian]);
    }

    public function edit(Guardian $guardian)
    {
        return view('guardians.edit', ['guardian' => $guardian]);
    }

    public function update(UpdateGuardianRequest $request, Guardian $guardian)
    {
        $guardian->update($request->validated());

        return redirect()->route('guardians.show', $guardian)->with('status', 'Guardian updated.');
    }
}
