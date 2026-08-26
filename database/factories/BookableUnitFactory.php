<?php

namespace Database\Factories;

use App\Models\BookableUnit;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookableUnit>
 */
class BookableUnitFactory extends Factory
{
    protected $model = BookableUnit::class;

    public function definition(): array
    {
        $product = Product::factory()->create(['base_price' => 450000]);

        return [
            'branch_id' => Branch::factory(),
            'product_id' => $product->id,
            'type' => fake()->randomElement(['room', 'meeting_room', 'camp_site']),
            'name' => 'Unit '.fake()->unique()->bothify('##'),
            'capacity' => fake()->numberBetween(2, 40),
            'base_price' => $product->base_price,
            'is_active' => true,
        ];
    }

    public function room(int $price = 450000): static
    {
        return $this->state(fn () => ['type' => 'room', 'capacity' => 2, 'base_price' => $price]);
    }

    public function meetingRoom(int $price = 2500000): static
    {
        return $this->state(fn () => ['type' => 'meeting_room', 'capacity' => 50, 'base_price' => $price]);
    }

    public function campSite(int $price = 150000): static
    {
        return $this->state(fn () => ['type' => 'camp_site', 'capacity' => 4, 'base_price' => $price]);
    }
}
