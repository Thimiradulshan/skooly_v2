<?php

namespace App\Http\Controllers\Web;

use App\Actions\Guardians\GrantGuardianPaymentReminderEmailConsent;
use App\Actions\Guardians\LinkGuardianPortalAccount;
use App\Actions\Guardians\RevokeGuardianPaymentReminderEmailConsent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreGuardianRequest;
use App\Http\Requests\Web\UpdateGuardianRequest;
use App\Models\Family;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Http\Request;
use InvalidArgumentException;

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

        $portalUsers = User::query()
            ->whereNotNull('email_verified_at')
            ->where('email', $guardian->email)
            ->where(function ($query) use ($guardian): void {
                $query->whereNull('guardian_id')->orWhere('guardian_id', $guardian->id);
            })
            ->orderBy('name')
            ->get();

        return view('guardians.show', compact('guardian', 'portalUsers'));
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

    public function grantConsent(Request $request, Guardian $guardian, GrantGuardianPaymentReminderEmailConsent $action)
    {
        $request->validate(['authorization_record' => ['required', 'string', 'max:1000']]);

        try {
            $action->handle($guardian, $request->user(), $request->string('authorization_record')->toString());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['authorization_record' => $exception->getMessage()]);
        }

        return back()->with('status', 'Guardian reminder consent recorded. Email verification remains required.');
    }

    public function revokeConsent(Request $request, Guardian $guardian, RevokeGuardianPaymentReminderEmailConsent $action)
    {
        $action->handle($guardian, $request->user());

        return back()->with('status', 'Guardian reminder consent revoked.');
    }

    public function linkPortalAccount(Request $request, Guardian $guardian, LinkGuardianPortalAccount $action)
    {
        $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);

        try {
            $action->handle($guardian, User::query()->findOrFail($request->integer('user_id')), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['user_id' => $exception->getMessage()]);
        }

        return back()->with('status', 'Guardian portal account linked.');
    }
}
