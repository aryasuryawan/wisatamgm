<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'expense_category_id' => ExpenseCategory::factory(),
            'user_id' => User::factory(),
            'marketing_campaign_id' => null,
            'ref_type' => null,
            'ref_id' => null,
            'description' => fake()->sentence(4),
            'amount' => fake()->numberBetween(50, 500) * 1000,
            'expense_date' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
        ];
    }

    public function forCampaign(MarketingCampaign $campaign): static
    {
        return $this->state(fn () => [
            'marketing_campaign_id' => $campaign->id,
            'branch_id' => $campaign->branch_id,
        ]);
    }

    public function ref(string $type, int $id): static
    {
        return $this->state(fn () => ['ref_type' => $type, 'ref_id' => $id]);
    }
}
