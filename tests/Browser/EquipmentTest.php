<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\EquipmentUnit;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EquipmentTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $admin;
    private Branch $branch;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionSeeder::class);

        $this->branch = Branch::factory()->create();
        $category = ProductCategory::factory()->create(['type_slug' => 'sewa-alat']);
        $this->product = Product::factory()->create(['name' => 'BCD Rental', 'category_id' => $category->id]);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->admin->assignRole('owner');
    }

    public function test_can_view_equipment_list(): void
    {
        EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'code' => 'EQ-001',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('equipment.index'))
                ->assertSee('EQ-001')
                ->assertVisible('@equipment-table');
        });
    }

    public function test_can_create_equipment(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('equipment.create'))
                ->select('product_id', $this->product->id)
                ->select('branch_id', $this->branch->id)
                ->type('code', 'EQ-NEW-1')
                ->select('condition', 'good')
                ->press('@save-equipment')
                ->waitForLocation('/equipment')
                ->assertSee('EQ-NEW-1');
        });
    }

    public function test_can_edit_equipment_condition(): void
    {
        $unit = EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'condition' => 'good',
        ]);

        $this->browse(function (Browser $browser) use ($unit) {
            $browser->loginAs($this->admin)
                ->visit(route('equipment.edit', $unit))
                ->select('condition', 'fair')
                ->press('@save-equipment')
                ->waitForLocation('/equipment');

            $this->assertEquals('fair', $unit->fresh()->condition);
        });
    }

    public function test_can_add_maintenance_log(): void
    {
        $unit = EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
        ]);

        $this->browse(function (Browser $browser) use ($unit) {
            $browser->loginAs($this->admin)
                ->visit(route('equipment.edit', $unit))
                ->within('@maintenance-form', function (Browser $form) {
                    $form->type('date', now()->toDateString())
                        ->select('type', 'routine')
                        ->type('description', 'Regulator checkup')
                        ->type('@input-cost', '75000')
                        ->press('@save-maintenance');
                })
                ->waitForLocation("/equipment/{$unit->id}/edit")
                ->assertSee('Regulator checkup');

            $this->assertDatabaseHas('equipment_maintenance_logs', [
                'equipment_unit_id' => $unit->id,
                'description' => 'Regulator checkup',
            ]);
        });
    }

    public function test_can_filter_by_status(): void
    {
        EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'code' => 'EQ-AVAIL',
            'status' => 'available',
        ]);
        EquipmentUnit::factory()->create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'code' => 'EQ-RENTED',
            'status' => 'rented',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('equipment.index', ['status' => 'rented']))
                ->assertSee('EQ-RENTED')
                ->assertDontSee('EQ-AVAIL');
        });
    }

    public function test_validation_blocks_empty_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('equipment.create'))
                ->press('@save-equipment')
                ->pause(500)
                ->assertPathIs('/equipment/create');
        });

        $this->assertDatabaseCount('equipment_units', 0);
    }
}
