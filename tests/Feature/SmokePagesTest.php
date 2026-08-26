<?php

namespace Tests\Feature\Schedule;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokePagesTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $this->seed(['RolesPermissionSeeder']);
        $u = User::factory()->create();
        $u->assignRole('owner');

        return $u;
    }

    public function test_customers_create_renders(): void
    {
        $r = $this->actingAs($this->owner())->get(route('customers.create'));
        if ($e = $r->exception ?? null) {
            dump(class_basename($e), $e->getMessage());
        }
        $r->assertOk();
    }

    public function test_products_create_renders(): void
    {
        $this->owner();
        ProductCategory::factory()->create();
        $r = $this->actingAs(User::first())->get(route('products.create'));
        if ($e = $r->exception ?? null) {
            dump(class_basename($e), $e->getMessage());
        }
        $r->assertOk();
    }

    public function test_categories_create_renders(): void
    {
        $r = $this->actingAs($this->owner())->get(route('product-categories.create'));
        if ($e = $r->exception ?? null) {
            dump(class_basename($e), $e->getMessage());
        }
        $r->assertOk();
    }

    public function test_branches_create_renders(): void
    {
        $this->owner();
        Branch::factory()->create();
        $r = $this->actingAs(User::first())->get(route('branches.create'));
        if ($e = $r->exception ?? null) {
            dump(class_basename($e), $e->getMessage());
        }
        $r->assertOk();
    }
}
