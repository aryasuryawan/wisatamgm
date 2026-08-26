<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        $slugs = array_keys(ExpenseCategory::SLUGS);

        return [
            'slug' => fake()->unique()->randomElement($slugs),
            'name' => fn (array $attrs) => ExpenseCategory::SLUGS[$attrs['slug']],
        ];
    }
}
