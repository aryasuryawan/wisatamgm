<?php

namespace Tests\Feature\Equipment;

use App\Models\Branch;
use App\Models\EquipmentUnit;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branch;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('owner');

        $this->branch = Branch::factory()->create();
        $category = ProductCategory::factory()->create(['type_slug' => 'sewa-alat']);
        $this->product = Product::factory()->create(['category_id' => $category->id]);
    }

    public function test_admin_can_view_equipment(): void
    {
        EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('equipment.index'))
            ->assertOk();
    }

    public function test_create_equipment(): void
    {
        $data = [
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'code' => 'EQ-001',
            'condition' => 'good',
            'notes' => null,
        ];

        $this->actingAs($this->admin)
            ->post(route('equipment.store'), $data)
            ->assertRedirect();

        $this->assertDatabaseHas('equipment_units', ['code' => 'EQ-001']);
    }

    public function test_update_equipment_page_loads(): void
    {
        $unit = EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('equipment.edit', $unit))
            ->assertOk();
    }

    public function test_update_equipment_saves(): void
    {
        $unit = EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('equipment.update', $unit), [
                'product_id' => $this->product->id,
                'branch_id' => $this->branch->id,
                'code' => $unit->code,
                'condition' => 'fair',
                'status' => 'available',
                'notes' => 'Updated',
            ])
            ->assertRedirect(route('equipment.index'));

        $unit->refresh();
        $this->assertEquals('fair', $unit->condition);
    }

    public function test_delete_equipment(): void
    {
        $unit = EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('equipment.destroy', $unit))
            ->assertRedirect(route('equipment.index'));

        $this->assertDatabaseMissing('equipment_units', ['id' => $unit->id]);
    }

    public function test_add_maintenance(): void
    {
        $unit = EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('equipment.maintenance', $unit), [
                'date' => now()->toDateString(),
                'type' => 'routine',
                'description' => 'Regular checkup',
                'cost' => 50000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('equipment_maintenance_logs', [
            'equipment_unit_id' => $unit->id,
            'type' => 'routine',
        ]);
    }

    public function test_validation_requires_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('equipment.store'), [
                'product_id' => '',
                'branch_id' => '',
                'code' => '',
                'condition' => '',
            ])
            ->assertSessionHasErrors(['product_id', 'branch_id', 'code', 'condition']);
    }

    public function test_code_must_be_unique_in_branch(): void
    {
        EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'code' => 'EQ-DUPLICATE',
        ]);

        $this->actingAs($this->admin)
            ->post(route('equipment.store'), [
                'product_id' => $this->product->id,
                'branch_id' => $this->branch->id,
                'code' => 'EQ-DUPLICATE',
                'condition' => 'good',
            ])
            ->assertSessionHasErrors(['code']);

        $this->assertDatabaseCount('equipment_units', 1);
    }

    public function test_user_without_permission_cannot_view(): void
    {
        $finance = User::factory()->create()->assignRole('finance');

        $this->actingAs($finance)
            ->get(route('equipment.index'))
            ->assertForbidden();
    }

    public function test_maintenance_validation_requires_fields(): void
    {
        $unit = EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('equipment.maintenance', $unit), [
                'date' => '',
                'type' => '',
                'cost' => '',
            ])
            ->assertSessionHasErrors(['date', 'type', 'cost']);
    }
}
