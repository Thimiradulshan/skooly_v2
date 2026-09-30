<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\DueItemDiscount;
use App\Models\StudentDueItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DueItemDiscount>
 */
class DueItemDiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_due_item_id' => StudentDueItem::factory(),
            'discount_id' => Discount::factory(),
            'type' => fake()->word(),
            'value' => 10,
            'value_type' => 'amount',
            'amount_applied' => 10,
        ];
    }
}
