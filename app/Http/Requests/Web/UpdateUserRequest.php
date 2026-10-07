<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('update', $user) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::in($this->allowedRoles())],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function allowedRoles(): array
    {
        return $this->user()?->hasRole(Role::SUPERADMIN)
            ? [Role::SUPERADMIN, Role::ADMIN, Role::ACCOUNTANT, Role::TEACHER]
            : [Role::ACCOUNTANT, Role::TEACHER];
    }
}
