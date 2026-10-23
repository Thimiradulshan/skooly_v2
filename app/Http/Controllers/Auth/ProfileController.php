<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.profile', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = ['name' => $request->string('name')->toString()];

        if ($request->filled('new_password')) {
            $data['password'] = $request->string('new_password')->toString();
        }

        $user->update($data);

        return redirect()->route('profile.edit')->with('status', 'Profile updated.');
    }
}
