<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreUserRequest;
use App\Http\Requests\Web\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        return view('users.index', [
            'users' => User::query()
                ->with('roles')
                ->when(! $request->user()->hasRole(Role::SUPERADMIN), fn ($query) => $query->whereDoesntHave(
                    'roles',
                    fn ($roles) => $roles->whereIn('name', [Role::ADMIN, Role::SUPERADMIN]),
                ))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        return view('users.create', $this->formData());
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::query()->create(Arr::only($request->validated(), ['name', 'email', 'password']));
        $user->roles()->sync($this->roleIds($request->validated('roles')));

        return redirect()->route('users.show', $user)->with('status', 'User created.');
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);
        $user->load('roles', 'qualifiedSubjects', 'teacherAssignments.academicYear', 'teacherAssignments.section', 'teacherAssignments.subject', 'sectionYearAssignments.academicYear', 'sectionYearAssignments.section');

        return view('users.show', ['user' => $user]);
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return view('users.edit', array_merge(['user' => $user->load('roles')], $this->formData()));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $this->ensureSuperadminSafeguards($request->user(), $user, $data);
        $user->update(array_filter(Arr::only($data, ['name', 'email', 'password', 'is_active']), fn ($value) => $value !== null));
        $user->roles()->sync($this->roleIds($data['roles']));

        return redirect()->route('users.show', $user)->with('status', 'User updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $roleNames = request()->user()?->hasRole(Role::SUPERADMIN)
            ? [Role::SUPERADMIN, Role::ADMIN, Role::ACCOUNTANT, Role::TEACHER]
            : [Role::ACCOUNTANT, Role::TEACHER];

        return ['roles' => Role::query()->whereIn('name', $roleNames)->orderBy('name')->get()];
    }

    /**
     * @param  array<int, string>  $roleNames
     * @return array<int, int>
     */
    private function roleIds(array $roleNames): array
    {
        return collect($roleNames)
            ->map(fn (string $name) => Role::query()->firstOrCreate(['name' => $name])->id)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureSuperadminSafeguards(User $actor, User $user, array $data): void
    {
        if ($user->is($actor) && ! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'You cannot archive your own account.']);
        }

        if (! $user->hasRole(Role::SUPERADMIN)) {
            return;
        }

        $remainsActiveSuperadmin = $data['is_active'] && in_array(Role::SUPERADMIN, $data['roles'], true);

        if ($remainsActiveSuperadmin) {
            return;
        }

        $activeSuperadminCount = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', Role::SUPERADMIN))
            ->count();

        if ($activeSuperadminCount <= 1) {
            throw ValidationException::withMessages(['roles' => 'At least one active Superadmin account is required.']);
        }
    }
}
