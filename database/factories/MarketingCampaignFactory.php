<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingCampaign>
 */
class MarketingCampaignFactory extends Factory
{
    protected $model = MarketingCampaign::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => 'Campaign '.fake()->words(2, true),
            'channel' => fake()->randomElement(['meta_ads', 'google_ads', 'instagram', 'flyer']),
            'budget' => fake()->numberBetween(1, 20) * 500000,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
        ];
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn () => ['branch_id' => $branch->id]);
    }
}
