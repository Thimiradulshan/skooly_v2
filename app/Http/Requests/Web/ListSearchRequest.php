<?php

namespace App\Http\Requests\Web;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ListSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([Role::ADMIN]) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'max:50'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * @param  array<string, string>  $options
     */
    public function sort(array $options): ?string
    {
        $sort = $this->string('sort')->toString();

        if ($sort === '') {
            return null;
        }

        if (! array_key_exists($sort, $options)) {
            throw ValidationException::withMessages(['sort' => 'The selected sort option is invalid.']);
        }

        return $sort;
    }

    public function direction(string $default): string
    {
        return $this->string('direction')->toString() ?: $default;
    }
}
