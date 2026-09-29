<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Role::TEACHER,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => Role::ADMIN,
        ]);
    }

    public function accountant(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => Role::ACCOUNTANT,
        ]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => Role::TEACHER,
        ]);
    }
}
